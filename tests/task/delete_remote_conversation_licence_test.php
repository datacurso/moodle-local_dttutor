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
 * Tests for a deletion asked for while the site has no licence for the AI provider.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\task\delete_remote_conversation
 * @covers     \local_dttutor\local\pending_deletion
 */
final class delete_remote_conversation_licence_test extends \advanced_testcase {
    /** @var array Scope of the deletion every test asks for. */
    private const SCOPE = ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null];

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
     * @param int $attemptsavailable Attempts the task has left.
     * @return delete_remote_conversation
     */
    private function task(int $attemptsavailable = delete_remote_conversation::ATTEMPTS): delete_remote_conversation {
        $task = new delete_remote_conversation();
        $task->set_custom_data(self::SCOPE);
        $task->set_attempts_available($attemptsavailable);
        return $task;
    }

    /**
     * Assert that exactly one task carries the deletion on, a day from now and with every attempt.
     *
     * @param int $before Time taken before the task ran.
     */
    private function assert_handed_to_one_waiting_task(int $before): void {
        $tasks = \core\task\manager::get_adhoc_tasks(delete_remote_conversation::class);
        $this->assertCount(1, $tasks);
        $next = reset($tasks);
        $scope = pending_deletion::scope_of($next->get_custom_data());
        $this->assertSame(self::SCOPE, $scope);
        $this->assertSame(delete_remote_conversation::ATTEMPTS, $next->get_attempts_available());
        $this->assertSame(0, (int)$next->get_fail_delay());
        $this->assertGreaterThanOrEqual($before + DAYSECS, $next->get_next_run_time());
    }

    /**
     * Ways the provider says that the site has no licence to use it.
     *
     * @return array[]
     */
    public static function missing_licences(): array {
        return [
            'no licence key' => ['invalidlicensekey'],
            'no enabled provider instance' => ['instance_disabled'],
            'enabled provider instance without a key' => ['licensekey_missing'],
        ];
    }

    /**
     * A provider that cannot build its client for lack of a licence does not fail the task.
     *
     * @dataProvider missing_licences
     * @param string $errorcode Error code the provider throws.
     */
    public function test_a_missing_licence_does_not_fail_the_task(string $errorcode): void {
        fake_ai_client::bind_failing(new provider_exception($errorcode));
        $before = time();

        $this->expectOutputRegex('/waits for a licence of the AI provider \(' . $errorcode . '\)/');
        $this->task()->execute();

        $this->assert_handed_to_one_waiting_task($before);
    }

    /**
     * A licence key missing when the request is sent makes the deletion wait the same way.
     */
    public function test_a_licence_missing_at_request_time_does_not_fail_the_task(): void {
        $fake = new fake_ai_client();
        $fake->enqueue(new provider_exception('invalidlicensekey'));
        \core\di::set(ai_client::class, $fake);
        $before = time();

        $this->expectOutputRegex('/waits for a licence of the AI provider \(invalidlicensekey\)/');
        $this->task()->execute();

        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
        $this->assert_handed_to_one_waiting_task($before);
    }

    /**
     * Waiting for a licence is not reported as a stuck deletion, whatever attempt the task is on.
     */
    public function test_waiting_for_a_licence_is_not_reported_as_stuck(): void {
        fake_ai_client::bind_failing(new provider_exception('invalidlicensekey'));
        $sink = $this->redirectEvents();
        $alertat = delete_remote_conversation::ATTEMPTS - delete_remote_conversation::ALERT_AFTER_ATTEMPTS + 1;

        $this->expectOutputRegex('/waits for a licence/');
        $this->task($alertat)->execute();

        $events = array_filter($sink->get_events(), static fn($event) => $event instanceof service_failed);
        $this->assertSame([], $events);
        $this->assertDebuggingNotCalled();
    }

    /**
     * On its last attempt the task hands the deletion on once, not twice.
     */
    public function test_the_last_attempt_without_licence_hands_the_deletion_on_once(): void {
        fake_ai_client::bind_failing(new provider_exception('invalidlicensekey'));
        $before = time();

        $this->expectOutputRegex('/waits for a licence/');
        $this->task(1)->execute();

        $this->assert_handed_to_one_waiting_task($before);
    }

    /**
     * Through the cron, the run without a licence is not a failure and the deletion is kept.
     */
    public function test_the_cron_keeps_the_deletion_without_recording_a_failure(): void {
        global $DB;
        pending_deletion::queue_session('sess-1');
        $DB->set_field('task_adhoc', 'nextruntime', 0, ['classname' => '\\' . delete_remote_conversation::class]);
        fake_ai_client::bind_failing(new provider_exception('invalidlicensekey'));
        $before = time();

        $this->expectOutputRegex('/waits for a licence/');
        $this->runAdhocTasks(delete_remote_conversation::class);

        $this->assert_handed_to_one_waiting_task($before);
        $this->assertSame([self::SCOPE], pending_deletion::pending());
    }

    /**
     * Once the licence is configured, the waiting deletion is confirmed and leaves the queue.
     */
    public function test_the_deletion_goes_through_once_the_licence_is_back(): void {
        global $DB;
        fake_ai_client::bind_failing(new provider_exception('invalidlicensekey'));
        $this->task()->execute();
        $DB->set_field('task_adhoc', 'nextruntime', 0, ['classname' => '\\' . delete_remote_conversation::class]);
        $fake = new fake_ai_client();
        $fake->enqueue(['deleted' => true]);
        \core\di::set(ai_client::class, $fake);

        $this->expectOutputRegex('/Remote deletion confirmed/');
        $this->runAdhocTasks(delete_remote_conversation::class);

        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
        $this->assertSame([], pending_deletion::pending());
    }

    /**
     * A licence the service refuses is a failure of the service: it is still retried and fails.
     */
    public function test_a_licence_the_service_refuses_still_fails_the_task(): void {
        $fake = new fake_ai_client();
        $fake->enqueue(new provider_exception('license_not_allowed'));
        \core\di::set(ai_client::class, $fake);

        $this->expectOutputRegex('/not confirmed \(license_not_allowed\), attempt 1/');
        $this->expectException(\moodle_exception::class);
        $this->task()->execute();
    }
}
