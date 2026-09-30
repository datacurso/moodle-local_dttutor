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

use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Retention of the conversations.
 *
 * See MDL-INT-037 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\task\purge_old_conversations
 */
final class purge_old_conversations_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Store a conversation last used a number of days ago.
     *
     * @param int $daysago
     * @param string $remoteid
     * @param int $userid Owner of the conversation: one row per user, course and activity.
     */
    private function add_conversation(int $daysago, string $remoteid, int $userid = 5): void {
        global $DB;
        $when = time() - ($daysago * DAYSECS);
        $DB->insert_record('local_dttutor_session', (object)[
            'userid' => $userid,
            'courseid' => 7,
            'cmid' => 0,
            'remotesessionid' => $remoteid,
            'timecreated' => $when,
            'timemodified' => $when,
        ]);
    }

    /**
     * MDL-INT-037: conversations past the retention period are removed on both sides.
     */
    public function test_conversations_past_the_period_are_removed(): void {
        global $DB;
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        set_config('retention_days', 30, 'local_dttutor');
        $this->add_conversation(45, 'old-one', 5);
        $this->add_conversation(2, 'recent-one', 6);

        (new purge_old_conversations())->execute();

        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'old-one']));
        $this->assertTrue($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'recent-one']));
        $this->assertSame(['DELETE /chat/session/old-one'], $fake->get_call_signatures());
    }

    /**
     * MDL-INT-037: with no period agreed nothing is removed by age.
     */
    public function test_without_a_period_nothing_is_removed(): void {
        global $DB;
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        set_config('retention_days', 0, 'local_dttutor');
        $this->add_conversation(500, 'very-old');

        (new purge_old_conversations())->execute();

        $this->assertTrue($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'very-old']));
        $this->assertSame([], $fake->get_call_signatures());
    }

    /**
     * MDL-INT-037: a failure of the AI service does not stop the local deletion.
     */
    public function test_a_remote_failure_does_not_stop_the_local_deletion(): void {
        global $DB;
        fake_ai_client::bind_unavailable();
        set_config('retention_days', 10, 'local_dttutor');
        $this->add_conversation(30, 'unreachable');

        (new purge_old_conversations())->execute();

        $this->assertDebuggingCalled();
        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'unreachable']));
    }

    /**
     * MDL-INT-037: the task is declared so that it runs on its own.
     */
    public function test_the_task_is_scheduled(): void {
        $tasks = \core\task\manager::load_scheduled_tasks_for_component('local_dttutor');

        $classes = array_map(static fn($task) => get_class($task), $tasks);
        $this->assertContains(purge_old_conversations::class, $classes);
    }
}
