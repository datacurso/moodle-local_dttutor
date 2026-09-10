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

namespace local_dttutor\external;

use local_dttutor\course_config;
use local_dttutor\fixtures\fake_ai_services_api;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\session_store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_services_api.php');

/**
 * Tests for the delete_chat_session external function.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\external\delete_chat_session
 * @covers     \local_dttutor\session_store
 */
final class delete_chat_session_test extends \advanced_testcase {
    /**
     * Register a fake HTTP layer in the DI container.
     *
     * @return fake_ai_services_api
     */
    private function fake_remote_api(): fake_ai_services_api {
        $fake = new fake_ai_services_api();
        \core\di::set(tutoria_api::class, new tutoria_api($fake));
        return $fake;
    }

    /**
     * Create an enabled course with one enrolled student and log that student in.
     *
     * @return array{0: \stdClass, 1: \stdClass} The course and the student.
     */
    private function enrolled_student_in_enabled_course(): array {
        set_config('enabled', 1, 'local_dttutor');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        return [$course, $student];
    }

    public function test_tutor_disabled_for_the_course_is_refused(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_enabled_course();
        $this->setAdminUser();
        course_config::update($course->id, ['indexing_enabled' => 0]);
        $this->setUser($student);
        $fake = $this->fake_remote_api();
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-42');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/not available/');
        try {
            delete_chat_session::execute((int)$course->id);
        } finally {
            $this->assertSame([], $fake->get_call_signatures());
        }
    }

    public function test_module_from_another_course_is_refused(): void {
        $this->resetAfterTest();
        [$course] = $this->enrolled_student_in_enabled_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $foreignpage = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);
        $this->fake_remote_api();

        $this->expectException(\moodle_exception::class);
        delete_chat_session::execute((int)$course->id, (int)$foreignpage->cmid);
    }

    public function test_delete_without_a_stored_session_makes_no_remote_call(): void {
        $this->resetAfterTest();
        [$course] = $this->enrolled_student_in_enabled_course();
        $fake = $this->fake_remote_api();

        $result = delete_chat_session::execute((int)$course->id);
        $result = \core_external\external_api::clean_returnvalue(delete_chat_session::execute_returns(), $result);

        $this->assertFalse($result['deleted']);
        $this->assertSame([], $fake->get_call_signatures(), 'No request (in particular no /chat/start) may be issued');
    }

    public function test_delete_with_a_stored_session_issues_exactly_one_delete_and_forgets_the_row(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_enabled_course();
        $fake = $this->fake_remote_api();
        $fake->enqueue(['deleted' => true]);
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-42');
        $cache = \cache::make('local_dttutor', 'sessions');
        $cache->set("session_v2_{$course->id}_{$student->id}", ['session_id' => 'remote-42']);

        $result = delete_chat_session::execute((int)$course->id);

        $this->assertTrue($result['deleted']);
        $this->assertSame(['DELETE /chat/session/remote-42'], $fake->get_call_signatures());
        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-42']));
        $this->assertFalse($cache->get("session_v2_{$course->id}_{$student->id}"));
    }

    public function test_delete_targets_the_module_scoped_session_only(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_enabled_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $fake = $this->fake_remote_api();
        $fake->enqueue(['deleted' => true]);
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-course');
        session_store::upsert((int)$student->id, (int)$course->id, (int)$page->cmid, 'remote-module');

        $result = delete_chat_session::execute((int)$course->id, (int)$page->cmid);

        $this->assertTrue($result['deleted']);
        $this->assertSame(['DELETE /chat/session/remote-module'], $fake->get_call_signatures());
        $this->assertTrue($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-course']));
        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-module']));
    }

    public function test_remote_failure_is_swallowed_and_reported_as_not_deleted(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_enabled_course();
        $fake = $this->fake_remote_api();
        $fake->enqueue(new \moodle_exception('error_api_not_configured', 'local_dttutor'));
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-broken');

        $result = delete_chat_session::execute((int)$course->id);
        $this->assertDebuggingCalledCount(1);

        $this->assertFalse($result['deleted']);
        // The handle is dropped anyway: the remote session is gone or will expire on its own.
        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-broken']));
    }

    public function test_client_construction_failure_is_swallowed_and_drops_the_stored_handle(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_enabled_course();
        // No DI binding and no license key: constructing the real client throws.
        unset_config('licensekey', 'aiprovider_datacurso');
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-unreachable');

        $result = delete_chat_session::execute((int)$course->id);

        $debug = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(1, $debug);
        $this->assertStringContainsString('SESSION_DELETE_REMOTE_UNAVAILABLE', $debug[0]->message);
        $this->assertStringContainsString(\moodle_exception::class, $debug[0]->message);
        $this->assertStringNotContainsString('remote-unreachable', $debug[0]->message);
        $this->assertFalse($result['deleted']);
        // The stored handle is dropped anyway so a later deletion does not chase a dead pointer.
        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-unreachable']));
    }

    public function test_stored_session_lookup_distinguishes_course_and_module_scope(): void {
        $this->resetAfterTest();
        session_store::upsert(3, 7, null, 'course-level');
        session_store::upsert(3, 7, 11, 'module-level');

        $this->assertSame('course-level', session_store::get_remote_session_id(3, 7, null));
        $this->assertSame('module-level', session_store::get_remote_session_id(3, 7, 11));
        $this->assertNull(session_store::get_remote_session_id(3, 8, null));
        $this->assertNull(session_store::get_remote_session_id(4, 7, null));
    }
}
