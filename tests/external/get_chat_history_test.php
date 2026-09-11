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
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;
use local_dttutor\session_store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Tests for the get_chat_history external function.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\external\get_chat_history
 */
final class get_chat_history_test extends \advanced_testcase {
    /**
     * Bind a fake AI client in the DI container.
     *
     * @return fake_ai_client
     */
    private function fake_remote_api(): fake_ai_client {
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        return $fake;
    }

    /**
     * Create a course (tutor enabled globally and, optionally, for the course) with one enrolled student logged in.
     *
     * @param bool $courseenabled Whether the tutor is enabled for the course.
     * @return array{0: \stdClass, 1: \stdClass} The course and the student.
     */
    private function enrolled_student_in_course(bool $courseenabled): array {
        set_config('enabled', 1, 'local_dttutor');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        course_config::update($course->id, ['indexing_enabled' => (int)$courseenabled]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        return [$course, $student];
    }

    /**
     * Execute the function and clean the return value against its declared structure.
     *
     * @param int $courseid
     * @param int|null $cmid
     * @return array
     */
    private function execute(int $courseid, ?int $cmid = null): array {
        $result = get_chat_history::execute($courseid, $cmid);
        return \core_external\external_api::clean_returnvalue(get_chat_history::execute_returns(), $result);
    }

    public function test_tutor_disabled_for_the_course_is_refused(): void {
        $this->resetAfterTest();
        [$course] = $this->enrolled_student_in_course(false);
        $fake = $this->fake_remote_api();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/not available/');
        try {
            get_chat_history::execute((int)$course->id);
        } finally {
            $this->assertSame([], $fake->get_call_signatures());
        }
    }

    public function test_module_from_another_course_is_refused(): void {
        $this->resetAfterTest();
        [$course] = $this->enrolled_student_in_course(true);
        $othercourse = $this->getDataGenerator()->create_course();
        $foreignpage = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);
        $this->fake_remote_api();

        $this->expectException(\moodle_exception::class);
        get_chat_history::execute((int)$course->id, (int)$foreignpage->cmid);
    }

    public function test_without_a_stored_session_returns_an_empty_history_and_makes_no_remote_call(): void {
        $this->resetAfterTest();
        [$course] = $this->enrolled_student_in_course(true);
        $fake = $this->fake_remote_api();

        $result = $this->execute((int)$course->id);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['total_messages']);
        $this->assertSame([], $result['messages']);
        $this->assertFalse($result['pagination']['has_more']);
        $this->assertArrayNotHasKey('notice', $result);
        $this->assertSame([], $fake->get_call_signatures(), 'A read must not open a remote session');
    }

    public function test_with_a_stored_session_returns_the_remote_history(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_course(true);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        session_store::upsert((int)$student->id, (int)$course->id, (int)$page->cmid, 'remote-42');
        $fake = $this->fake_remote_api();
        $fake->enqueue([
            'success' => true,
            'session_id' => 'remote-42',
            'total_messages' => 1,
            'messages' => [['id' => 'm1', 'role' => 'user', 'content' => 'Hello', 'timestamp' => 1700000000]],
            'pagination' => ['limit' => 20, 'offset' => 0, 'has_more' => false],
        ]);

        $result = $this->execute((int)$course->id, (int)$page->cmid);

        $this->assertTrue($result['success']);
        $this->assertSame('remote-42', $result['session_id']);
        $this->assertCount(1, $result['messages']);
        $this->assertSame('Hello', $result['messages'][0]['content']);
        $this->assertSame(['GET /chat/history?session_id=remote-42&limit=20&offset=0'], $fake->get_call_signatures());
    }

    public function test_remote_failure_returns_an_empty_history_with_a_notice(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->enrolled_student_in_course(true);
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-gone');
        $fake = $this->fake_remote_api();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));

        $result = $this->execute((int)$course->id);
        $this->assertDebuggingCalled();

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['messages']);
        $this->assertSame(0, $result['total_messages']);
        $this->assertSame(get_string('error_history_unavailable', 'local_dttutor'), $result['notice']);
        $this->assertStringNotContainsString('error_unexpected', json_encode($result));
    }
}
