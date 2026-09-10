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

use local_dttutor\fixtures\fake_ai_services_api;
use local_dttutor\httpclient\tutoria_api;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/fake_ai_services_api.php');

/**
 * Tests for the course and user deletion observers.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\observer
 */
final class observer_test extends \advanced_testcase {
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
     * Insert a stored session row.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $remoteid
     */
    private function add_session(int $userid, int $courseid, string $remoteid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_dttutor_session', (object)[
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => 0,
            'remotesessionid' => $remoteid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_deleting_a_course_removes_its_config_and_sessions(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $studenta = $generator->create_and_enrol($course, 'student');
        $studentb = $generator->create_and_enrol($course, 'student');
        course_config::update($course->id, ['indexing_enabled' => 1]);
        course_config::update($othercourse->id, ['indexing_enabled' => 1]);
        $this->add_session((int)$studenta->id, (int)$course->id, 'sess-a');
        $this->add_session((int)$studentb->id, (int)$course->id, 'sess-b');
        $this->add_session((int)$studenta->id, (int)$othercourse->id, 'sess-a-other');
        $fake = $this->fake_remote_api();

        delete_course($course->id, false);

        $this->assertFalse($DB->record_exists('local_dttutor_course_config', ['courseid' => $course->id]));
        $this->assertTrue($DB->record_exists('local_dttutor_course_config', ['courseid' => $othercourse->id]));
        $this->assertSame(0, $DB->count_records('local_dttutor_session', ['courseid' => $course->id]));
        $this->assertSame(1, $DB->count_records('local_dttutor_session', ['courseid' => $othercourse->id]));
        $this->assertEqualsCanonicalizing(
            ['DELETE /chat/session/sess-a', 'DELETE /chat/session/sess-b'],
            $fake->get_call_signatures()
        );
    }

    public function test_deleting_a_user_removes_their_sessions(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $other = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-student');
        $this->add_session((int)$student->id, (int)$othercourse->id, 'sess-student-other');
        $this->add_session((int)$other->id, (int)$course->id, 'sess-other');
        $fake = $this->fake_remote_api();

        delete_user($student);

        $this->assertSame(0, $DB->count_records('local_dttutor_session', ['userid' => $student->id]));
        $this->assertSame(1, $DB->count_records('local_dttutor_session', ['userid' => $other->id]));
        $this->assertEqualsCanonicalizing(
            ['DELETE /chat/session/sess-student', 'DELETE /chat/session/sess-student-other'],
            $fake->get_call_signatures()
        );
    }

    public function test_remote_failure_does_not_block_course_deletion(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-a');
        $fake = $this->fake_remote_api();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));

        delete_course($course->id, false);

        $this->assertDebuggingCalled();
        $this->assertSame(0, $DB->count_records('local_dttutor_session'));
        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }

    public function test_course_deletion_completes_when_the_remote_client_cannot_be_built(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-a');
        // No DI binding and no license key: constructing the real client throws.
        unset_config('licensekey', 'aiprovider_datacurso');

        delete_course($course->id, false);

        $this->assertDebuggingCalled();
        $this->assertSame(0, $DB->count_records('local_dttutor_session'));
        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }
}
