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

use local_dttutor\event\service_failed;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\fixtures\provider_exception;
use local_dttutor\httpclient\tutoria_api;

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
     * Make every pending deletion due now.
     */
    private function make_all_due(): void {
        global $DB;
        $DB->set_field(pending_deletion::TABLE, 'nextattempt', 0);
    }

    /**
     * DTT-PRIV-004: a session is kept once with its reason.
     */
    public function test_a_session_is_kept_once_with_its_reason(): void {
        global $DB;

        pending_deletion::queue_session('sess-1', new provider_exception('httperror', 503));
        pending_deletion::queue_session('sess-1', new provider_exception('curlerror', 28));

        $row = $DB->get_record(pending_deletion::TABLE, ['remotesessionid' => 'sess-1'], '*', MUST_EXIST);
        $this->assertSame(1, $DB->count_records(pending_deletion::TABLE));
        $this->assertSame('http_503', $row->lasterror);
        $this->assertEquals(1, $row->attempts);
        $this->assertNull($row->userid);
        $this->assertNull($row->courseid);
        $this->assertGreaterThan(time(), (int)$row->nextattempt);
    }

    /**
     * DTT-PRIV-004: a purge without a scope is never kept.
     */
    public function test_a_purge_without_a_scope_is_never_kept(): void {
        global $DB;

        pending_deletion::queue_purge(null, null, 'transport');

        $this->assertSame(0, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: purges of different scopes are kept apart.
     */
    public function test_purges_of_different_scopes_are_kept_apart(): void {
        global $DB;

        pending_deletion::queue_purge(42, null, 'transport');
        pending_deletion::queue_purge(42, 7, 'transport');
        pending_deletion::queue_purge(null, 7, 'transport');
        pending_deletion::queue_purge(42, 7, 'transport');

        $this->assertSame(3, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: a confirmed session deletion leaves nothing pending.
     */
    public function test_a_confirmed_session_deletion_leaves_nothing_pending(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        $this->make_all_due();
        [$api, $fake] = $this->api(['deleted' => true]);

        $result = pending_deletion::retry_due($api, time());

        $this->assertSame(['confirmed' => 1, 'failed' => 0], $result);
        $this->assertSame(['DELETE /chat/session/sess-1'], $fake->get_call_signatures());
        $this->assertSame(0, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: a session the service no longer has counts as deleted.
     */
    public function test_a_session_the_service_no_longer_has_counts_as_deleted(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        $this->make_all_due();
        [$api] = $this->api(new provider_exception('httperror', 404));

        $result = pending_deletion::retry_due($api, time());

        $this->assertSame(['confirmed' => 1, 'failed' => 0], $result);
        $this->assertSame(0, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: a pending purge is asked with its scope.
     */
    public function test_a_pending_purge_is_asked_with_its_scope(): void {
        global $DB;
        pending_deletion::queue_purge(null, 7, 'transport');
        $this->make_all_due();
        [$api, $fake] = $this->api(['deleted_sessions' => 3]);

        pending_deletion::retry_due($api, time());

        $this->assertSame(['POST /chat/sessions/purge'], $fake->get_call_signatures());
        $this->assertTrue($fake->calls[0]['body']['all_users']);
        $this->assertSame('7', $fake->calls[0]['body']['course_id']);
        $this->assertSame(0, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: a failure keeps the deletion and waits longer.
     */
    public function test_a_failure_keeps_the_deletion_and_waits_longer(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        $this->make_all_due();
        [$api] = $this->api(new provider_exception('httperror', 502));

        $before = time();
        $result = pending_deletion::retry_due($api, $before);

        $this->assertSame(['confirmed' => 0, 'failed' => 1], $result);
        $row = $DB->get_record(pending_deletion::TABLE, ['remotesessionid' => 'sess-1'], '*', MUST_EXIST);
        $this->assertEquals(2, $row->attempts);
        $this->assertSame('http_502', $row->lasterror);
        $this->assertGreaterThanOrEqual($before + pending_deletion::delay_after(2), (int)$row->nextattempt);
    }

    /**
     * DTT-PRIV-004: a deletion that is not due is not tried.
     */
    public function test_a_deletion_that_is_not_due_is_not_tried(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        [$api, $fake] = $this->api();

        $result = pending_deletion::retry_due($api, time());

        $this->assertSame(['confirmed' => 0, 'failed' => 0], $result);
        $this->assertSame([], $fake->get_call_signatures());
        $this->assertSame(1, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: without a client every due deletion waits.
     */
    public function test_without_a_client_every_due_deletion_waits(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        $this->make_all_due();

        $result = pending_deletion::retry_due(null, time());

        $this->assertSame(['confirmed' => 0, 'failed' => 1], $result);
        $this->assertSame(
            pending_deletion::PROVIDER_UNAVAILABLE,
            $DB->get_field(pending_deletion::TABLE, 'lasterror', ['remotesessionid' => 'sess-1'])
        );
    }

    /**
     * DTT-PRIV-004: a deletion that stays stuck is reported once.
     */
    public function test_a_deletion_that_stays_stuck_is_reported_once(): void {
        global $DB;
        pending_deletion::queue_session('sess-1', 'transport');
        $DB->set_field(pending_deletion::TABLE, 'attempts', pending_deletion::ALERT_AFTER_ATTEMPTS - 1);
        $sink = $this->redirectEvents();

        $this->make_all_due();
        pending_deletion::retry_due(null, time());
        $this->make_all_due();
        pending_deletion::retry_due(null, time());

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
        // Reporting it does not give it up: it is still asked for.
        $this->assertSame(1, $DB->count_records(pending_deletion::TABLE));
    }

    /**
     * DTT-PRIV-004: the wait doubles up to a day.
     */
    public function test_the_wait_doubles_up_to_a_day(): void {
        $this->assertSame(pending_deletion::FIRST_DELAY, pending_deletion::delay_after(1));
        $this->assertSame(2 * pending_deletion::FIRST_DELAY, pending_deletion::delay_after(2));
        $this->assertSame(4 * pending_deletion::FIRST_DELAY, pending_deletion::delay_after(3));
        $this->assertSame(pending_deletion::MAX_DELAY, pending_deletion::delay_after(40));
    }

    /**
     * DTT-PRIV-004: deleting a session forgets its handle only with a copy kept.
     */
    public function test_deleting_a_session_forgets_its_handle_only_with_a_copy_kept(): void {
        global $DB;
        \local_dttutor\session_store::upsert(3, 7, null, 'sess-1');
        [$api] = $this->api(new provider_exception('curlerror', 28));

        try {
            $api->delete_session('sess-1');
            $this->fail('A deletion the service did not confirm must not look confirmed.');
        } catch (\moodle_exception $e) {
            $this->assertSame('curlerror', $e->errorcode);
        }

        $this->assertNull(\local_dttutor\session_store::get_remote_session_id(3, 7, null));
        $this->assertSame('transport', $DB->get_field(pending_deletion::TABLE, 'lasterror', ['remotesessionid' => 'sess-1']));
    }
}
