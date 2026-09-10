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

namespace local_dttutor\hook;

/**
 * Tests for the visibility rules of the floating chat widget.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\hook\chat_hook
 */
final class chat_hook_test extends \advanced_testcase {
    public function test_enrolled_student_can_use_the_tutor(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->assertTrue(chat_hook::can_use_tutor((int)$course->id, (int)$student->id));
    }

    public function test_authenticated_user_without_enrolment_cannot_use_the_tutor(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $visitor = $this->getDataGenerator()->create_user();

        $this->assertFalse(chat_hook::can_use_tutor((int)$course->id, (int)$visitor->id));
    }

    public function test_site_administrator_can_use_the_tutor_without_enrolment(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->assertTrue(chat_hook::can_use_tutor((int)$course->id, (int)get_admin()->id));
    }

    public function test_defaults_to_the_current_user(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->assertTrue(chat_hook::can_use_tutor((int)$course->id));
    }
}
