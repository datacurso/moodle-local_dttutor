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

use local_dttutor\proxy\request_guard;

/**
 * Global and per-course enablement of the tutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\course_config
 */
final class course_config_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Whether the tutor is available for a course, as the request guard sees it.
     *
     * @param int $courseid
     * @return bool
     */
    private function tutor_is_available(int $courseid): bool {
        try {
            request_guard::assert_course_enabled($courseid);
            return true;
        } catch (\moodle_exception $e) {
            return false;
        }
    }

    /**
     * MDL-INT-001: a course with no record of its own has the tutor disabled.
     */
    public function test_a_brand_new_course_starts_with_the_tutor_disabled(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));
    }

    /**
     * MDL-INT-001: reading the configuration of a course without a record creates its default row.
     */
    public function test_reading_an_unconfigured_course_creates_its_default_record(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $record = course_config::get_by_course((int)$course->id);

        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals(0, $record->indexing_enabled);
        $this->assertEquals(1, $DB->count_records('local_dttutor_course_config', ['courseid' => $course->id]));
    }

    /**
     * MDL-INT-001: enabling and disabling the tutor keeps a single record per course.
     */
    public function test_updating_the_course_switch_does_not_duplicate_the_record(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $this->assertTrue(course_config::is_enabled_for_course((int)$course->id));

        course_config::update((int)$course->id, ['indexing_enabled' => 0]);
        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));

        $this->assertEquals(1, $DB->count_records('local_dttutor_course_config', ['courseid' => $course->id]));
    }

    /**
     * MDL-INT-001: the record keeps who changed the switch last.
     */
    public function test_the_record_keeps_the_last_author_of_the_change(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        course_config::update((int)$course->id, ['indexing_enabled' => 1]);

        $record = course_config::get_by_course((int)$course->id);
        $this->assertEquals($teacher->id, $record->usermodified);
        $this->assertGreaterThan(0, $record->timemodified);
    }

    /**
     * MDL-INT-001: only the fields the plugin owns can be written through the update entry point.
     */
    public function test_fields_outside_the_allowed_list_are_ignored(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();

        course_config::update((int)$course->id, [
            'indexing_enabled' => 1,
            'courseid' => $other->id,
        ]);

        $record = course_config::get_by_course((int)$course->id);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals(1, $record->indexing_enabled);
    }

    /**
     * MDL-INT-001: the site switch turned off leaves the tutor unavailable in an enabled course.
     */
    public function test_the_site_switch_off_overrides_an_enabled_course(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        set_config('enabled', 0, 'local_dttutor');

        $this->assertFalse($this->tutor_is_available((int)$course->id));
    }

    /**
     * MDL-INT-001: the course switch turned off leaves the tutor unavailable with the site switch on.
     */
    public function test_the_course_switch_off_keeps_the_tutor_unavailable(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        set_config('enabled', 1, 'local_dttutor');

        $this->assertFalse($this->tutor_is_available((int)$course->id));
    }

    /**
     * MDL-INT-001: the tutor is available only when both switches agree.
     */
    public function test_both_switches_on_make_the_tutor_available(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        set_config('enabled', 1, 'local_dttutor');
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);

        $this->assertTrue($this->tutor_is_available((int)$course->id));
    }

    /**
     * MDL-INT-001: deleting the configuration of a course leaves the others untouched.
     */
    public function test_deleting_the_configuration_only_affects_one_course(): void {
        global $DB;
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        course_config::update((int)$first->id, ['indexing_enabled' => 1]);
        course_config::update((int)$second->id, ['indexing_enabled' => 1]);

        course_config::delete((int)$first->id);

        $this->assertFalse($DB->record_exists('local_dttutor_course_config', ['courseid' => $first->id]));
        $this->assertTrue($DB->record_exists('local_dttutor_course_config', ['courseid' => $second->id]));
    }

    /**
     * MDL-INT-042: new courses start with the tutor on when the administrator asks for it.
     */
    public function test_a_new_course_starts_with_the_tutor_on_when_configured(): void {
        $this->setAdminUser();
        set_config('enabled_by_default', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();

        $this->assertTrue(course_config::is_enabled_for_course((int)$course->id));
    }

    /**
     * MDL-INT-042: with the default off, a new course keeps the tutor off.
     */
    public function test_a_new_course_keeps_the_tutor_off_by_default(): void {
        $this->setAdminUser();
        set_config('enabled_by_default', 0, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));
    }

    /**
     * MDL-INT-042: the teacher of a course can still switch off what the default switched on.
     */
    public function test_the_teacher_can_still_switch_the_tutor_off(): void {
        $this->setAdminUser();
        set_config('enabled_by_default', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();

        course_config::update((int)$course->id, ['indexing_enabled' => 0]);

        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));
    }
}
