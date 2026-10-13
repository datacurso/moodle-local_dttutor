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

use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\fixtures\provider_exception;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\task\delete_remote_conversation;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');
require_once(__DIR__ . '/../fixtures/provider_exception.php');

/**
 * Tests for the deletions kept until the AI service confirms them (Mindfree DTT-PRIV-004).
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\pending_deletion
 * @covers     \local_dttutor\httpclient\tutoria_api
 */
final class pending_deletion_test extends \advanced_testcase {
    /**
     * Reset the database after every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * An API answering with the given responses, one per request.
     *
     * @param array|\Throwable ...$responses Responses, or exceptions to throw.
     * @return array{0: tutoria_api, 1: fake_ai_client}
     */
    private function api(array|\Throwable ...$responses): array {
        $fake = new fake_ai_client();
        foreach ($responses as $response) {
            $fake->enqueue($response);
        }
        return [new tutoria_api($fake), $fake];
    }

    /**
     * DTT-PRIV-004: a session is queued once, as an ad hoc task that waits before its first try.
     */
    public function test_a_session_is_queued_once(): void {
        $before = time();
        pending_deletion::queue_session('sess-1');
        pending_deletion::queue_session('sess-1');

        $this->assertSame([['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null]], pending_deletion::pending());
        $tasks = \core\task\manager::get_adhoc_tasks(delete_remote_conversation::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertSame(delete_remote_conversation::ATTEMPTS, $task->get_attempts_available());
        $this->assertGreaterThanOrEqual($before + pending_deletion::FIRST_DELAY, $task->get_next_run_time());
        // The task does not run as the person: a deleted user could not be run as.
        $this->assertEmpty($task->get_userid());
    }

    /**
     * DTT-PRIV-004: neither an empty session nor a purge without a scope is queued.
     */
    public function test_a_deletion_without_a_scope_is_never_queued(): void {
        pending_deletion::queue_session('');
        pending_deletion::queue_purge(null, null);

        $this->assertSame([], pending_deletion::pending());
    }

    /**
     * DTT-PRIV-004: purges of different scopes are kept apart.
     */
    public function test_purges_of_different_scopes_are_kept_apart(): void {
        pending_deletion::queue_purge(42, null);
        pending_deletion::queue_purge(42, 7);
        pending_deletion::queue_purge(null, 7);
        pending_deletion::queue_purge(42, 7);

        $this->assertEqualsCanonicalizing([
            ['remotesessionid' => null, 'userid' => 42, 'courseid' => null],
            ['remotesessionid' => null, 'userid' => 42, 'courseid' => 7],
            ['remotesessionid' => null, 'userid' => null, 'courseid' => 7],
        ], pending_deletion::pending());
    }

    /**
     * DTT-PRIV-004: the scope carried by a task is read back as it was queued, and nothing else.
     */
    public function test_the_scope_is_read_back_from_the_task(): void {
        $this->assertSame(
            ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null],
            pending_deletion::scope_of((object)['remotesessionid' => 'sess-1', 'userid' => 5, 'courseid' => 6])
        );
        $this->assertSame(
            ['remotesessionid' => null, 'userid' => 5, 'courseid' => null],
            pending_deletion::scope_of((object)['remotesessionid' => null, 'userid' => '5', 'courseid' => null])
        );
        $this->assertNull(pending_deletion::scope_of(null));
        $this->assertNull(pending_deletion::scope_of((object)['remotesessionid' => '']));
    }

    /**
     * DTT-PRIV-004: a session is deleted through the session endpoint.
     */
    public function test_a_session_is_asked_for_through_its_endpoint(): void {
        [$api, $fake] = $this->api(['deleted' => true]);

        pending_deletion::attempt($api, ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null]);

        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
    }

    /**
     * DTT-PRIV-004: a purge is asked for with its scope.
     */
    public function test_a_purge_is_asked_for_with_its_scope(): void {
        [$api, $fake] = $this->api(['deleted_sessions' => 3]);

        pending_deletion::attempt($api, ['remotesessionid' => null, 'userid' => null, 'courseid' => 7]);

        $this->assertSame(['POST /chat/sessions/purge'], $fake->get_call_signatures());
        $this->assertTrue($fake->calls[0]['body']['all_users']);
        $this->assertSame('7', $fake->calls[0]['body']['course_id']);
    }

    /**
     * DTT-PRIV-004: a session the service no longer has counts as deleted.
     */
    public function test_a_session_the_service_no_longer_has_counts_as_deleted(): void {
        [$api] = $this->api(new provider_exception('httperror', 404));

        pending_deletion::attempt($api, ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null]);

        $this->assertTrue(pending_deletion::is_already_gone(new provider_exception('httperror', 404)));
        $this->assertFalse(pending_deletion::is_already_gone(new provider_exception('httperror', 503)));
    }

    /**
     * DTT-PRIV-004: a failure the service reports is not taken for a deletion.
     */
    public function test_a_failure_is_not_taken_for_a_deletion(): void {
        [$api] = $this->api(new provider_exception('httperror', 502));

        $this->expectException(\moodle_exception::class);
        pending_deletion::attempt($api, ['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null]);
    }

    /**
     * DTT-PRIV-004: deleting a session forgets its handle only with a copy kept.
     */
    public function test_deleting_a_session_forgets_its_handle_only_with_a_copy_kept(): void {
        \local_dttutor\session_store::upsert(3, 7, null, 'sess-1');
        [$api] = $this->api(new provider_exception('curlerror', 28));

        try {
            $api->delete_session('sess-1');
            $this->fail('A deletion the service did not confirm must not look confirmed.');
        } catch (\moodle_exception $e) {
            $this->assertSame('curlerror', $e->errorcode);
        }

        $this->assertNull(\local_dttutor\session_store::get_remote_session_id(3, 7, null));
        $this->assertSame([['remotesessionid' => 'sess-1', 'userid' => null, 'courseid' => null]], pending_deletion::pending());
    }
}
