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
 * Gaps of the course knowledge handed to the AI service, as listed in the scope.
 *
 * The privacy defect is written with the behaviour the scope requires and FAILS until corrected.
 * The remaining cases are gaps pending development and are skipped until the feature exists.
 * See MDL-INT-009 and MDL-INT-011 to MDL-INT-016 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class course_knowledge_gaps_test extends \advanced_testcase {
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->libdir . '/gradelib.php');
    }

    /**
     * A course with one graded assignment, an enrolled student and grade sending switched on.
     *
     * @param float $grade Grade awarded to the student.
     * @return array{0: \stdClass, 1: \stdClass, 2: \grade_item} Course, student and grade item.
     */
    private function course_with_a_graded_student(float $grade): array {
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
     * MDL-INT-009: a grade hidden by the teacher must not travel to the AI service.
     *
     * [Pendiente:fail] Today the state of the grade is never checked, so a student could learn
     * through the chat a mark their teacher has not published yet.
     */
    public function test_a_grade_hidden_by_the_teacher_is_not_sent(): void {
        [$course, $student, $gradeitem] = $this->course_with_a_graded_student(73.00);
        $gradeitem->set_hidden(1, true);

        $knowledge = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertStringNotContainsString(
            '73.00',
            $knowledge,
            'A hidden grade must not be part of the information handed to the AI service.'
        );
    }

    /**
     * MDL-INT-009: a grade hidden for one student only must not travel to the AI service either.
     *
     * [Pendiente:fail] Same defect as above, seen through an individually hidden grade.
     */
    public function test_a_grade_hidden_for_a_single_student_is_not_sent(): void {
        [$course, $student, $gradeitem] = $this->course_with_a_graded_student(64.00);
        $grade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $student->id], true);
        $grade->set_hidden(1);

        $knowledge = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertStringNotContainsString(
            '64.00',
            $knowledge,
            'A grade hidden for this student must not be part of the information handed to the AI service.'
        );
    }

    /**
     * MDL-INT-011: the grades of the user are read in one go instead of activity by activity.
     */
    public function test_the_grades_of_the_user_are_read_in_one_go(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Grades are still read one activity at a time on every message. '
            . 'Pending the grouped lookup described in the scope.'
        );
    }

    /**
     * MDL-INT-012: the course knowledge sent on each message has a bounded size.
     */
    public function test_the_course_knowledge_sent_on_each_message_is_bounded(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The whole list of visible activities is sent with no limit. '
            . 'Pending a defined bound (number of activities, relevance or maximum size).'
        );
    }

    /**
     * MDL-INT-013: dates of every activity type reach the AI service.
     */
    public function test_dates_of_every_activity_type_are_sent(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Lesson, workshop and videoconference dates, and the closing date of a '
            . 'database, are not recognised yet.'
        );
    }

    /**
     * MDL-INT-014: attempt duration and time limits reach the AI service.
     */
    public function test_attempt_duration_and_time_limits_are_sent(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The maximum duration of a quiz attempt and the time limit of an '
            . 'assignment are not sent yet.'
        );
    }

    /**
     * MDL-INT-015: activities the student cannot open yet are announced with their condition.
     */
    public function test_activities_not_open_yet_are_announced_with_their_condition(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] A restricted activity the student sees greyed out never reaches the '
            . 'AI service, so the tutor answers that the course does not have it.'
        );
    }

    /**
     * MDL-INT-016: the content of the resources and the progress of the student reach the AI service.
     */
    public function test_resource_content_and_student_progress_are_sent(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The tutor knows names, dates and maximum grades but neither the content '
            . 'of the resources nor the progress of the student. Scope decision pending with the client.'
        );
    }
}
