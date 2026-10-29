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

namespace local_dttutor\proxy;

/**
 * Tests for the grades the course knowledge pre-loader sends.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class context_preloader_grades_test extends \advanced_testcase {
    /**
     * Create a course with a graded assignment and an enrolled student holding a grade.
     *
     * @return array [course, student]
     */
    private function create_graded_course(): array {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Essay', 'grade' => 100]);
        $student = $generator->create_and_enrol($course, 'student');
        grade_update('mod/assign', $course->id, 'mod', 'assign', $assign->id, 0, [
            'userid' => $student->id,
            'rawgrade' => 85,
        ]);
        return [$course, $student];
    }

    /**
     * MDL-INT-008: sending the grades of the user only when enabled.
     */
    public function test_grades_are_not_sent_by_default(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->create_graded_course();

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Essay', $text);
        $this->assertStringNotContainsString('YOUR GRADES', $text);
        $this->assertStringNotContainsString('85.00 / 100.00', $text);
    }

    /**
     * MDL-INT-008: sending the grades of the user only when enabled.
     */
    public function test_grades_are_sent_when_the_setting_is_enabled(): void {
        $this->resetAfterTest();
        set_config('include_grades', 1, 'local_dttutor');
        [$course, $student] = $this->create_graded_course();

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('YOUR GRADES', $text);
        $this->assertStringContainsString('85.00 / 100.00', $text);
    }

    /**
     * A course with one graded assignment, an enrolled student and grade sending switched on.
     *
     * @param float $grade Grade awarded to the student.
     * @return array{0: \stdClass, 1: \stdClass, 2: \grade_item} Course, student and grade item.
     */
    private function course_with_a_graded_student(float $grade): array {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        set_config('include_grades', 1, 'local_dttutor');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $generator->create_module('assign', ['course' => $course->id, 'grade' => 100]);
        $student = $generator->create_and_enrol($course, 'student');

        $gradeitem = \grade_item::fetch([
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assign->id,
        ]);
        $gradeitem->update_final_grade($student->id, $grade);

        return [$course, $student, $gradeitem];
    }

    /**
     * MDL-INT-009: a grade the student can already see travels with the rest.
     */
    public function test_a_released_grade_is_sent(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->course_with_a_graded_student(73.00);

        $this->assertStringContainsString('73.00', context_preloader::build($course->id, (int)$student->id));
    }

    /**
     * MDL-INT-009: a grade hidden by the teacher does not travel to the AI service.
     */
    public function test_a_grade_hidden_by_the_teacher_is_not_sent(): void {
        $this->resetAfterTest();
        [$course, $student, $gradeitem] = $this->course_with_a_graded_student(73.00);
        $gradeitem->set_hidden(1, true);

        $this->assertStringNotContainsString(
            '73.00',
            context_preloader::build($course->id, (int)$student->id),
            'A hidden grade must not be part of the information handed to the AI service.'
        );
    }

    /**
     * MDL-INT-009: a grade hidden for one student only does not travel either.
     */
    public function test_a_grade_hidden_for_a_single_student_is_not_sent(): void {
        $this->resetAfterTest();
        [$course, $student, $gradeitem] = $this->course_with_a_graded_student(64.00);
        $grade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $student->id], true);
        $grade->set_hidden(1);

        $this->assertStringNotContainsString(
            '64.00',
            context_preloader::build($course->id, (int)$student->id),
            'A grade hidden for this student must not be part of the information handed to the AI service.'
        );
    }

    /**
     * MDL-INT-009: a grade held until a date is not sent while that date has not arrived.
     */
    public function test_a_grade_held_until_a_future_date_is_not_sent(): void {
        $this->resetAfterTest();
        [$course, $student, $gradeitem] = $this->course_with_a_graded_student(55.00);
        $grade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $student->id], true);
        $grade->set_hidden(time() + DAYSECS);

        $this->assertStringNotContainsString(
            '55.00',
            context_preloader::build($course->id, (int)$student->id)
        );
    }

    /**
     * The extra database reads that including the grades costs in a course of a given size.
     *
     * @param int $activities Gradable activities the course has.
     * @return int
     */
    private function cost_of_including_the_grades(int $activities): int {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        for ($i = 0; $i < $activities; $i++) {
            $assign = $generator->create_module('assign', ['course' => $course->id, 'grade' => 100]);
            $item = \grade_item::fetch([
                'courseid' => $course->id,
                'itemtype' => 'mod',
                'itemmodule' => 'assign',
                'iteminstance' => $assign->id,
            ]);
            $item->update_final_grade($student->id, 50);
        }

        // Warm the course knowledge and the permissions, so that only the grades are measured.
        set_config('include_grades', 0, 'local_dttutor');
        context_preloader::build($course->id, (int)$student->id);
        $before = $DB->perf_get_reads();
        context_preloader::build($course->id, (int)$student->id);
        $without = $DB->perf_get_reads() - $before;

        set_config('include_grades', 1, 'local_dttutor');
        context_preloader::build($course->id, (int)$student->id);
        $before = $DB->perf_get_reads();
        $text = context_preloader::build($course->id, (int)$student->id);
        $with = $DB->perf_get_reads() - $before;

        $this->assertStringContainsString('YOUR GRADES', $text);
        return $with - $without;
    }

    /**
     * MDL-INT-011: including the grades costs the same whatever the number of activities.
     */
    public function test_reading_the_grades_does_not_grow_with_the_number_of_activities(): void {
        $this->resetAfterTest();

        $small = $this->cost_of_including_the_grades(2);
        $large = $this->cost_of_including_the_grades(10);

        $this->assertLessThanOrEqual(
            2,
            $large - $small,
            'The grades of the user must be read for the whole course at once, not activity by activity.'
        );
    }
}
