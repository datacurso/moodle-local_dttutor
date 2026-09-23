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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * The switch of the tutor across a backup and a restore of the course.
 *
 * See MDL-INT-041 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class backup_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Back a course up and restore it into a brand new one, as duplicating does.
     *
     * @param \stdClass $course
     * @return int Id of the restored course.
     */
    private function backup_and_restore(\stdClass $course): int {
        global $CFG, $USER;

        $backupid = 'dttutor-backup-' . $course->id;
        $controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            (int)$USER->id
        );
        $controller->execute_plan();
        $result = $controller->get_results();
        $controller->destroy();

        $path = make_backup_temp_directory($backupid);
        $result['backup_destination']->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);

        $newcourseid = \restore_dbops::create_new_course($course->fullname, $course->shortname . '_copy', 1);
        $restore = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            (int)$USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $restore->execute_precheck();
        $restore->execute_plan();
        $restore->destroy();

        unset($CFG->keeptempdirectoriesonbackup);
        return (int)$newcourseid;
    }

    /**
     * MDL-INT-041: a course with the tutor on comes back with the tutor on.
     */
    public function test_the_tutor_stays_on_after_a_restore(): void {
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);

        $restoredid = $this->backup_and_restore($course);

        $this->assertTrue(course_config::is_enabled_for_course($restoredid));
    }

    /**
     * MDL-INT-041: a course with the tutor off comes back with the tutor off.
     */
    public function test_the_tutor_stays_off_after_a_restore(): void {
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 0]);

        $restoredid = $this->backup_and_restore($course);

        $this->assertFalse(course_config::is_enabled_for_course($restoredid));
    }

    /**
     * MDL-INT-041: the conversations of the users are not copied into the new course.
     */
    public function test_the_conversations_are_not_copied(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-1');

        $restoredid = $this->backup_and_restore($course);

        $this->assertSame(0, $DB->count_records('local_dttutor_session', ['courseid' => $restoredid]));
        $this->assertSame(1, $DB->count_records('local_dttutor_session', ['courseid' => (int)$course->id]));
    }
}
