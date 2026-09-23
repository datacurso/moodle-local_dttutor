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

/**
 * Tests for the save_course_config external function.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\external\save_course_config
 */
final class save_course_config_test extends \advanced_testcase {
    /**
     * Create a course with the plugin enabled and log an editing teacher in.
     *
     * @return \stdClass The course.
     */
    private function course_with_teacher_logged_in(): \stdClass {
        set_config('enabled', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        return $course;
    }

    /**
     * Execute the function and clean the return value against its declared structure.
     *
     * @param int $courseid
     * @param bool $enabled
     * @return array
     */
    private function execute(int $courseid, bool $enabled): array {
        $result = save_course_config::execute($courseid, $enabled);
        return \core_external\external_api::clean_returnvalue(save_course_config::execute_returns(), $result);
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_teacher_enables_the_tutor(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();

        $result = $this->execute((int)$course->id, true);

        $this->assertTrue($result['success']);
        $this->assertSame(get_string('changessaved'), $result['message']);
        $this->assertTrue(course_config::is_enabled_for_course((int)$course->id));
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_teacher_disables_the_tutor(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);

        $result = $this->execute((int)$course->id, false);

        $this->assertTrue($result['success']);
        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_parameters_are_the_course_the_toggle_and_the_identity(): void {
        $this->assertSame(
            ['courseid', 'enabled', 'tutorname', 'welcomemessage'],
            array_keys(save_course_config::execute_parameters()->keys)
        );
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_return_structure_has_no_enrol_status(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();

        $result = save_course_config::execute((int)$course->id, true);

        $this->assertSame(['success', 'message'], array_keys($result));
        $this->assertSame(['success', 'message'], array_keys(save_course_config::execute_returns()->keys));
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_student_without_course_update_is_refused(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        save_course_config::execute((int)$course->id, true);
    }

    /**
     * MDL-INT-023: saving the course switch.
     */
    public function test_plugin_disabled_site_wide_is_refused(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();
        set_config('enabled', 0, 'local_dttutor');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error_tutor_disabled_site', 'local_dttutor'));
        save_course_config::execute((int)$course->id, true);
    }

    /**
     * MDL-INT-043: the teacher sets the name and the greeting of their own course.
     */
    public function test_teacher_sets_the_identity_of_the_course(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();

        save_course_config::execute((int)$course->id, true, 'Tutor of biology', 'Welcome to biology');

        $this->assertSame('Tutor of biology', course_config::get_setting((int)$course->id, 'tutorname'));
        $this->assertSame('Welcome to biology', course_config::get_setting((int)$course->id, 'welcomemessage'));
    }

    /**
     * MDL-INT-043: switching the tutor on and off never wipes what the course had written.
     *
     * This is the defect that had the per-course prompt removed in 2.0.9: the toggle sent an empty
     * value with every change and erased it. A field the caller leaves out is left alone.
     */
    public function test_the_toggle_does_not_wipe_the_identity_of_the_course(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();
        save_course_config::execute((int)$course->id, true, 'Tutor of biology', 'Welcome to biology');

        save_course_config::execute((int)$course->id, false);
        save_course_config::execute((int)$course->id, true);

        $this->assertSame('Tutor of biology', course_config::get_setting((int)$course->id, 'tutorname'));
        $this->assertSame('Welcome to biology', course_config::get_setting((int)$course->id, 'welcomemessage'));
    }

    /**
     * MDL-INT-043: an empty value is how a teacher goes back to what the site says.
     */
    public function test_an_empty_value_goes_back_to_the_site(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();
        set_config('tutorname', 'Site tutor', 'local_dttutor');
        save_course_config::execute((int)$course->id, true, 'Tutor of biology');

        save_course_config::execute((int)$course->id, true, '');

        $this->assertSame('Site tutor', course_config::get_setting((int)$course->id, 'tutorname'));
    }

    /**
     * MDL-INT-043: whoever cannot edit the course cannot set how the tutor introduces itself.
     */
    public function test_a_student_cannot_set_the_identity(): void {
        $this->resetAfterTest();
        $course = $this->course_with_teacher_logged_in();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $this->expectException(\required_capability_exception::class);
        save_course_config::execute((int)$course->id, true, 'Tutor of my own');
    }
}
