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
use local_dttutor\local\pending_deletion;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Tests for the task that asks the AI service again for the deletions it did not confirm.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\task\retry_pending_deletions
 */
final class retry_pending_deletions_test extends \advanced_testcase {
    /**
     * DTT-PRIV-004: once the service is back, the pending deletion is confirmed and forgotten.
     */
    public function test_the_task_repeats_a_pending_deletion(): void {
        global $DB;
        $this->resetAfterTest();
        pending_deletion::queue_session('sess-1', 'transport');
        $DB->set_field(pending_deletion::TABLE, 'nextattempt', 0);
        $fake = new fake_ai_client();
        $fake->enqueue(['deleted' => true]);
        \core\di::set(ai_client::class, $fake);

        $this->expectOutputRegex('/confirmed: 1; still pending: 0/');
        (new retry_pending_deletions())->execute();

        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
        $this->assertSame(0, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: while the provider cannot be built, the deletions wait instead of being lost.
     */
    public function test_the_task_keeps_the_deletions_while_the_provider_is_unavailable(): void {
        global $DB;
        $this->resetAfterTest();
        pending_deletion::queue_session('sess-1', 'transport');
        $DB->set_field(pending_deletion::TABLE, 'nextattempt', 0);
        fake_ai_client::bind_unavailable();

        $this->expectOutputRegex('/confirmed: 0; still pending: 1/');
        (new retry_pending_deletions())->execute();

        $this->assertSame(1, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: the task is declared so that it runs on its own.
     */
    public function test_the_task_is_scheduled(): void {
        $tasks = \core\task\manager::load_scheduled_tasks_for_component('local_dttutor');

        $classes = array_map(static fn($task) => get_class($task), $tasks);
        $this->assertContains(retry_pending_deletions::class, $classes);
    }
}
