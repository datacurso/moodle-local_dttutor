<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dttutor\task;

use local_dttutor\event\service_failed;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\fixtures\provider_exception;
use local_dttutor\httpclient\ai_client;
use local_dttutor\local\pending_deletion;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');
require_once(__DIR__ . '/../fixtures/provider_exception.php');

/**
 * Tests for the ad hoc task that asks the AI service again for a deletion it did not confirm.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\task\delete_remote_conversation
 */
final class delete_remote_conversation_test extends \advanced_testcase {
    /**
     * Reset the database after every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * A task for one session, with the attempts it has left.
     *
     * @param string $remotesessionid Session identifier in the AI service.
     * @param int $attemptsavailable Attempts the task has left.
     * @return delete_remote_conversation
     */
    private function task(
        string $remotesessionid,
        int $attemptsavailable = delete_remote_conversation::ATTEMPTS
    ): delete_remote_conversation {
        $task = new delete_remote_conversation();
        $task->set_custom_data(['remotesessionid' => $remotesessionid, 'userid' => null, 'courseid' => null]);
        $task->set_attempts_available($attemptsavailable);
        return $task;
    }

    /**
     * Bind a fake client answering with the given responses.
     *
     * @param array|\Throwable ...$responses Responses, or exceptions to throw.
     * @return fake_ai_client
     */
    private function fake(array|\Throwable ...$responses): fake_ai_client {
        $fake = new fake_ai_client();
        foreach ($responses as $response) {
            $fake->enqueue($response);
        }
        \core\di::set(ai_client::class, $fake);
        return $fake;
    }

    /**
     * DTT-PRIV-004: once the service is back, the queued deletion is confirmed and leaves the queue.
     */
    public function test_the_cron_repeats_a_queued_deletion(): void {
        global $DB;
        pending_deletion::queue_session('sess-1');
        $DB->set_field('task_adhoc', 'nextruntime', 0, ['classname' => '\\' . delete_remote_conversation::class]);
        $fake = $this->fake(['deleted' => true]);

        $this->expectOutputRegex('/Remote deletion confirmed/');
        $this->runAdhocTasks(delete_remote_conversation::class);

        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
        $this->assertSame([], pending_deletion::pending());
    }

    /**
     * DTT-PRIV-004: a failure is thrown back to the cron, which keeps the task and tries again later.
     */
    public function test_a_failure_is_left_to_the_cron_to_retry(): void {
        $this->fake(new provider_exception('httperror', 502));

        $this->expectOutputRegex('/not confirmed \(http_502\), attempt 1/');
        try {
            $this->task('sess-1')->execute();
            $this->fail('A deletion the service did not confirm must fail the task.');
        } catch (\moodle_exception $e) {
            $this->assertSame('httperror', $e->errorcode);
        }
    }

    /**
     * DTT-PRIV-004: while the provider cannot be built, the deletion waits instead of being lost.
     */
    public function test_the_deletion_waits_while_the_provider_is_unavailable(): void {
        fake_ai_client::bind_unavailable();

        $this->expectOutputRegex('/not confirmed \(provider_unavailable\)/');
        $this->expectException(\Throwable::class);
        $this->task('sess-1')->execute();
    }

    /**
     * DTT-PRIV-004: a session the service no longer has counts as deleted.
     */
    public function test_a_session_the_service_no_longer_has_counts_as_deleted(): void {
        $this->fake(new provider_exception('httperror', 404));

        $this->expectOutputRegex('/Remote deletion confirmed/');
        $this->task('sess-1')->execute();
    }

    /**
     * DTT-PRIV-004: on its last attempt the task hands the deletion on instead of giving it up.
     */
    public function test_the_last_attempt_hands_the_deletion_to_a_new_task(): void {
        $this->fake(new provider_exception('curlerror', 28));
        $before = time();

        $this->expectOutputRegex('/handed to a new task/');
        $this->task('sess-1', 1)->execute();

        $tasks = \core\task\manager::get_adhoc_tasks(delete_remote_conversation::class);
        $this->assertCount(1, $tasks);
        $next = reset($tasks);
        $this->assertSame(
            ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null],
            pending_deletion::scope_of($next->get_custom_data())
        );
        $this->assertSame(delete_remote_conversation::ATTEMPTS, $next->get_attempts_available());
        $this->assertSame(DAYSECS, (int)$next->get_fail_delay());
        $this->assertGreaterThanOrEqual($before + DAYSECS, $next->get_next_run_time());
    }

    /**
     * DTT-PRIV-004: a deletion that stays stuck is reported, without naming the person or the session.
     */
    public function test_a_deletion_that_stays_stuck_is_reported(): void {
        fake_ai_client::bind_unavailable();
        $sink = $this->redirectEvents();
        $alertat = delete_remote_conversation::ATTEMPTS - delete_remote_conversation::ALERT_AFTER_ATTEMPTS + 1;

        foreach ([$alertat + 1, $alertat, $alertat - 1] as $attemptsavailable) {
            try {
                $this->task('sess-1', $attemptsavailable)->execute();
            } catch (\Throwable $e) {
                // Every attempt fails: the cron would try again.
                $this->assertNotEmpty(get_class($e));
            }
        }
        $this->expectOutputRegex('/attempt ' . delete_remote_conversation::ALERT_AFTER_ATTEMPTS . '/');

        $events = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof service_failed
        ));
        $this->assertCount(1, $events);
        $this->assertSame(service_failed::OPERATION_DELETION, $events[0]->other['operation']);
        $this->assertSame(pending_deletion::PROVIDER_UNAVAILABLE, $events[0]->other['reason']);
        $debug = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(1, $debug);
        $this->assertStringContainsString('REMOTE_DELETION_OVERDUE', $debug[0]->message);
        $this->assertStringNotContainsString('sess-1', $debug[0]->message);
    }

    /**
     * DTT-PRIV-004: the retries no longer depend on a scheduled task of the plugin.
     */
    public function test_no_scheduled_task_is_left_for_the_retries(): void {
        $tasks = \core\task\manager::load_scheduled_tasks_for_component('local_dttutor');

        $classes = array_map(static fn($task) => get_class($task), $tasks);
        $this->assertNotContains('local_dttutor\task\retry_pending_deletions', $classes);
    }
}
