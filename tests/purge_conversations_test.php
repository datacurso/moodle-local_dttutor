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

namespace local_dttutor;

use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\local\pending_deletion;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/fake_ai_client.php');

/**
 * Deletion of every conversation of a person, including the ones Moodle never registered.
 *
 * See API-CTR-005 and MDL-INT-036 of cases_data/dttutor/dttutor-2.0.10.md. A site that used the
 * tutor before the session handles began to be stored holds conversations no row here can name,
 * so a suppression request is sent to the AI service by user and course instead.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\httpclient\tutoria_api::purge_conversations
 * @covers     \local_dttutor\session_store::purge_remote_conversations
 */
final class purge_conversations_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Bind a fake HTTP layer in the DI container.
     *
     * @param array|\Throwable ...$responses Responses handed out one per request.
     * @return fake_ai_client
     */
    private function fake_remote_api(array|\Throwable ...$responses): fake_ai_client {
        $fake = new fake_ai_client();
        foreach ($responses as $response) {
            $fake->enqueue($response);
        }
        \core\di::set(ai_client::class, $fake);
        return $fake;
    }

    /**
     * Insert a stored session row.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $remoteid
     */
    private function add_session(int $userid, int $courseid, string $remoteid): void {
        global $DB;
        $now = time();
        $DB->insert_record(session_store::TABLE, (object)[
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => 0,
            'remotesessionid' => $remoteid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * API-CTR-005: the deletion is asked for by person, not by conversation.
     */
    public function test_a_person_is_erased_from_every_course_at_once(): void {
        $fake = new fake_ai_client();
        $fake->enqueue(['deleted' => true, 'deleted_sessions' => 4, 'deleted_messages' => 31]);
        $api = new tutoria_api($fake);

        $response = $api->purge_conversations(42, null);

        $this->assertSame(['POST /chat/sessions/purge'], $fake->get_call_signatures());
        $this->assertSame(['all_users' => false, 'userid' => '42'], $fake->calls[0]['body']);
        $this->assertSame(4, $response['deleted_sessions']);
    }

    /**
     * API-CTR-005: the deletion is asked for by person, not by conversation.
     */
    public function test_a_course_narrows_the_scope_of_the_deletion(): void {
        $fake = new fake_ai_client();
        $api = new tutoria_api($fake);

        $api->purge_conversations(42, 7);

        $this->assertSame(
            ['all_users' => false, 'userid' => '42', 'course_id' => '7'],
            $fake->calls[0]['body']
        );
    }

    /**
     * API-CTR-005: the deletion is asked for by person, not by conversation.
     */
    public function test_a_whole_course_names_no_person(): void {
        $fake = new fake_ai_client();
        $api = new tutoria_api($fake);

        $api->purge_conversations(null, 7);

        $this->assertSame(
            ['all_users' => true, 'userid' => '0', 'course_id' => '7'],
            $fake->calls[0]['body']
        );
    }

    /**
     * API-CTR-005: a deletion without a scope would erase the site.
     */
    public function test_a_request_without_a_person_or_a_course_is_refused(): void {
        $fake = new fake_ai_client();
        $api = new tutoria_api($fake);

        $this->expectException(\coding_exception::class);
        try {
            $api->purge_conversations(null, null);
        } finally {
            $this->assertSame([], $fake->get_call_signatures());
        }
    }

    /**
     * API-CTR-005: an empty response body is not a count.
     */
    public function test_a_response_without_a_count_reports_nothing_deleted(): void {
        $this->fake_remote_api([]);

        $this->assertSame(0, session_store::purge_remote_conversations(42, null));
    }

    /**
     * API-CTR-005: the platform records how many conversations were deleted.
     */
    public function test_the_number_of_deleted_conversations_is_reported_back(): void {
        $this->fake_remote_api(['deleted' => true, 'deleted_sessions' => 9, 'deleted_messages' => 72]);

        $this->assertSame(9, session_store::purge_remote_conversations(42, 7));
    }

    /**
     * API-CTR-005: a deletion without a scope would erase the site.
     */
    public function test_an_empty_scope_never_reaches_the_service(): void {
        $fake = $this->fake_remote_api();

        $this->assertNull(session_store::purge_remote_conversations(null, null));
        $this->assertSame([], $fake->get_call_signatures());
    }

    /**
     * API-CTR-005: a service that answers with an error is not a completed deletion.
     */
    public function test_a_failing_service_is_reported_as_not_asked(): void {
        $this->fake_remote_api(new \moodle_exception('error_unexpected', 'local_dttutor'));

        $this->assertNull(session_store::purge_remote_conversations(42, null));
        $this->assertDebuggingCalled();
    }

    /**
     * DTT-PRIV-004: a deletion by user and course the service did not confirm is kept, once.
     */
    public function test_a_failed_purge_is_kept_pending_with_its_scope(): void {
        global $DB;
        $this->fake_remote_api(
            new \moodle_exception('error_unexpected', 'local_dttutor'),
            new \moodle_exception('error_unexpected', 'local_dttutor')
        );

        session_store::purge_remote_conversations(42, 7);
        session_store::purge_remote_conversations(42, 7);
        $this->assertDebuggingCalledCount(2);

        $this->assertSame([['remotesessionid' => null, 'userid' => 42, 'courseid' => 7]], pending_deletion::pending());
    }

    /**
     * DTT-PRIV-004: a course-wide deletion keeps every user in its scope.
     */
    public function test_a_failed_course_purge_is_kept_for_every_user(): void {
        global $DB;
        fake_ai_client::bind_unavailable();

        session_store::purge_remote_conversations(null, 7);
        $this->assertDebuggingCalled();

        $this->assertSame([['remotesessionid' => null, 'userid' => null, 'courseid' => 7]], pending_deletion::pending());
    }

    /**
     * API-CTR-005: a service that cannot be reached is not a completed deletion.
     */
    public function test_an_unreachable_service_is_reported_as_not_asked(): void {
        fake_ai_client::bind_unavailable();

        $this->assertNull(session_store::purge_remote_conversations(42, null));
        $this->assertDebuggingCalled();
    }

    /**
     * API-CTR-005: what the service already erased is not deleted twice.
     */
    public function test_the_known_sessions_are_only_dropped_locally_after_a_purge(): void {
        global $DB;
        $fake = $this->fake_remote_api();
        $this->add_session(42, 7, 'sess-one');
        $this->add_session(42, 8, 'sess-two');

        session_store::purge('userid = :userid', ['userid' => 42], false);

        $this->assertSame([], $fake->get_call_signatures());
        $this->assertSame(0, $DB->count_records(session_store::TABLE));
    }

    /**
     * API-CTR-005: when the service could not be asked, the known sessions are still deleted.
     */
    public function test_the_known_sessions_are_deleted_one_by_one_when_the_purge_failed(): void {
        global $DB;
        $fake = $this->fake_remote_api();
        $this->add_session(42, 7, 'sess-one');
        $this->add_session(42, 8, 'sess-two');

        session_store::purge('userid = :userid', ['userid' => 42], true);

        $this->assertEqualsCanonicalizing(
            ['DELETE /chat/session/sess-one', 'DELETE /chat/session/sess-two'],
            $fake->get_call_signatures()
        );
        $this->assertSame(0, $DB->count_records(session_store::TABLE));
        $this->assertSame([], pending_deletion::pending());
    }

    /**
     * DTT-PRIV-004: of the known sessions, only the ones the service did not confirm wait.
     */
    public function test_only_the_unconfirmed_sessions_are_kept_pending(): void {
        global $DB;
        $this->fake_remote_api(['deleted' => true], new \moodle_exception('error_unexpected', 'local_dttutor'));
        $this->add_session(42, 7, 'sess-one');
        $this->add_session(42, 8, 'sess-two');

        session_store::purge('userid = :userid', ['userid' => 42], true);
        $this->assertDebuggingCalled();

        $this->assertSame(0, $DB->count_records(session_store::TABLE));
        $this->assertSame([['remotesessionid' => 'sess-two', 'userid' => null, 'courseid' => null]], pending_deletion::pending());
    }
}
