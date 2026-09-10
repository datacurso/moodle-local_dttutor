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

/**
 * Tests for the installed schema of local_dttutor_course_config after the dead-column cleanup.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\course_config
 */
final class upgrade_schema_test extends \advanced_testcase {
    public function test_course_config_table_has_no_indexing_or_prompt_columns(): void {
        global $DB;

        $columns = array_keys($DB->get_columns('local_dttutor_course_config'));

        foreach (['last_indexed_at', 'indexing_status', 'indexing_task_id', 'indexing_error', 'custom_prompt'] as $dead) {
            $this->assertNotContains($dead, $columns, "Column {$dead} should have been dropped");
        }
        $this->assertEqualsCanonicalizing(
            ['id', 'courseid', 'indexing_enabled', 'timecreated', 'timemodified', 'usermodified'],
            $columns
        );
    }

    public function test_course_config_still_creates_and_updates_rows(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(course_config::is_enabled_for_course((int)$course->id));
        $this->assertTrue(course_config::update((int)$course->id, ['indexing_enabled' => 1]));

        $this->assertTrue(course_config::is_enabled_for_course((int)$course->id));
        $this->assertSame(1, $DB->count_records('local_dttutor_course_config', ['courseid' => $course->id]));
    }
}
