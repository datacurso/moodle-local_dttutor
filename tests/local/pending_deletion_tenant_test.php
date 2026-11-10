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

namespace local_dttutor\local;

use local_dttutor\httpclient\provider_config;
use local_dttutor\session_store;
use local_dttutor\task\delete_remote_conversation;

/**
 * The deletions kept for later carry the user whose tenant licence they go with on Workplace.
 *
 * Only Workplace has tenants, so every test is skipped elsewhere.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\pending_deletion
 */
final class pending_deletion_tenant_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        if (!provider_config::has_tenants()) {
            $this->markTestSkipped('Tenants exist only on Moodle Workplace with a tenant-aware provider.');
        }
        $this->resetAfterTest();
    }

    /**
     * Owner kept in the custom data of every queued deletion task, oldest first.
     *
     * @return array<int|null>
     */
    private function queued_owners(): array {
        $tasks = \core\task\manager::get_adhoc_tasks(delete_remote_conversation::class);
        ksort($tasks);
        return array_values(array_map(
            static fn($task) => pending_deletion::owner_of($task->get_custom_data()),
            $tasks
        ));
    }

    /**
     * A session queued without an owner takes the user it is stored for.
     */
    public function test_a_session_takes_the_owner_it_is_stored_for(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        session_store::upsert((int)$user->id, (int)$course->id, null, 'sess-owned');

        pending_deletion::queue_session('sess-owned');

        $this->assertSame([(int)$user->id], $this->queued_owners());
        $this->assertSame(
            [['remotesessionid' => 'sess-owned', 'userid' => null, 'courseid' => null]],
            pending_deletion::pending()
        );
    }

    /**
     * A session given its owner keeps it; one that is no longer stored has none.
     */
    public function test_a_session_keeps_the_owner_given_or_has_none(): void {
        pending_deletion::queue_session('sess-given', 42);
        pending_deletion::queue_session('sess-unknown');

        $this->assertSame([42, null], $this->queued_owners());
    }

    /**
     * A purge goes with the user it deletes, or with the owner given for a course.
     */
    public function test_a_purge_goes_with_its_user_or_the_owner_given(): void {
        pending_deletion::queue_purge(5, null);
        pending_deletion::queue_purge(null, 7, 9);
        pending_deletion::queue_purge(null, 8);

        $this->assertSame([5, 9, null], $this->queued_owners());
    }

    /**
     * The owner is read back from the custom data, and absent data names none.
     */
    public function test_the_owner_is_read_from_the_custom_data(): void {
        $scope = ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null];

        $this->assertSame($scope + ['ownerid' => 3], pending_deletion::task_data($scope, 3));
        $this->assertSame($scope, pending_deletion::task_data($scope, null));
        $this->assertSame(3, pending_deletion::owner_of((object)($scope + ['ownerid' => '3'])));
        $this->assertNull(pending_deletion::owner_of((object)$scope));
        $this->assertNull(pending_deletion::owner_of(null));
    }
}
