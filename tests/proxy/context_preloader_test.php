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
 * Tests for the course knowledge pre-loader.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class context_preloader_test extends \advanced_testcase {
    /**
     * MDL-INT-007: per-user visibility of the information sent.
     */
    public function test_hidden_module_is_omitted_for_student_and_listed_for_teacher(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Visible page']);
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden page', 'visible' => 0]);
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');

        // The student is built first on purpose: a course-wide cache would leak this result to the teacher.
        $studenttext = context_preloader::build($course->id, (int)$student->id);
        $teachertext = context_preloader::build($course->id, (int)$teacher->id);

        $this->assertStringContainsString('Visible page', $studenttext);
        $this->assertStringNotContainsString('Hidden page', $studenttext);
        $this->assertStringContainsString('Visible page', $teachertext);
        $this->assertStringContainsString('Hidden page', $teachertext);
    }

    /**
     * MDL-INT-007: per-user visibility of the information sent.
     */
    public function test_availability_restricted_module_is_omitted_for_student(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enableavailability = 1;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $availability = json_encode([
            'op' => '&',
            'c' => [['type' => 'date', 'd' => '>=', 't' => time() + WEEKSECS]],
            'showc' => [false],
        ]);
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Future page',
            'availability' => $availability,
        ]);
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');

        $studenttext = context_preloader::build($course->id, (int)$student->id);
        $teachertext = context_preloader::build($course->id, (int)$teacher->id);

        $this->assertStringNotContainsString('Future page', $studenttext);
        $this->assertStringContainsString('Future page', $teachertext);
    }

    /**
     * MDL-INT-006: course knowledge handed to the AI service.
     */
    public function test_knowledge_block_does_not_mention_tools(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'A page']);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('COURSE KNOWLEDGE', $text);
        $this->assertStringNotContainsStringIgnoringCase('tool', $text);
    }

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
     * A course with one assignment due on a known date and an enrolled student.
     *
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass} Course, student and assignment.
     */
    private function create_course_with_a_dated_assignment(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $generator->create_module('assign', [
            'course' => $course->id,
            'duedate' => mktime(0, 0, 0, 1, 1, 2030),
        ]);
        $student = $generator->create_and_enrol($course, 'student');
        return [$course, $student, $assign];
    }

    /**
     * MDL-INT-010: the course knowledge is worked out once and reused by the next questions.
     */
    public function test_the_course_knowledge_is_reused_between_questions(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student, $assign] = $this->create_course_with_a_dated_assignment();
        $this->assertStringContainsString('2030', context_preloader::build($course->id, (int)$student->id));

        // Changed behind the course cache, so nothing tells the plugin the course moved on.
        $DB->set_field('assign', 'duedate', mktime(0, 0, 0, 1, 1, 2035), ['id' => $assign->id]);

        $this->assertStringContainsString(
            '2030',
            context_preloader::build($course->id, (int)$student->id),
            'The knowledge gathered for this user had to be reused instead of gathered again.'
        );
    }

    /**
     * MDL-INT-010: a change in the course is reflected in the next question, with no cache to clear.
     */
    public function test_a_change_in_the_course_reaches_the_next_question(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student, $assign] = $this->create_course_with_a_dated_assignment();
        context_preloader::build($course->id, (int)$student->id);

        $DB->set_field('assign', 'duedate', mktime(0, 0, 0, 1, 1, 2035), ['id' => $assign->id]);
        rebuild_course_cache($course->id, true);

        $text = context_preloader::build($course->id, (int)$student->id);
        $this->assertStringContainsString('2035', $text);
        $this->assertStringNotContainsString('2030', $text);
    }

    /**
     * MDL-INT-010: what is reused belongs to one user and is not handed to another.
     */
    public function test_what_is_reused_is_not_shared_between_users(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden page', 'visible' => 0]);
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');

        context_preloader::build($course->id, (int)$student->id);
        context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Hidden page', context_preloader::build($course->id, (int)$teacher->id));
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

    /**
     * MDL-INT-012: the list of activities is bounded and the omission is announced.
     */
    public function test_the_list_of_activities_is_bounded(): void {
        $this->resetAfterTest();
        set_config('max_activities', 3, 'local_dttutor');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        for ($i = 1; $i <= 5; $i++) {
            $generator->create_module('page', ['course' => $course->id, 'name' => 'Page ' . $i]);
        }
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertSame(3, substr_count($text, '[page]'));
        $this->assertStringContainsString('2 further activities are not listed', $text);
    }

    /**
     * MDL-INT-012: without a configured limit the default one applies.
     */
    public function test_a_course_below_the_limit_lists_everything(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Only page']);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Only page', $text);
        $this->assertStringNotContainsString('further activities are not listed', $text);
    }

    /**
     * MDL-INT-013: the dates of a lesson reach the AI service.
     */
    public function test_the_dates_of_a_lesson_are_sent(): void {
        $this->resetAfterTest();
        // Both generators reach the file API, which refuses to work with nobody logged in.
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('lesson', [
            'course' => $course->id,
            'name' => 'Guided reading',
            'available' => mktime(0, 0, 0, 1, 1, 2030),
            'deadline' => mktime(0, 0, 0, 2, 1, 2030),
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('opens:', $text);
        $this->assertStringContainsString('due:', $text);
        $this->assertStringContainsString('2030', $text);
    }

    /**
     * MDL-INT-013: the dates of a workshop reach the AI service.
     */
    public function test_the_dates_of_a_workshop_are_sent(): void {
        $this->resetAfterTest();
        // Both generators reach the file API, which refuses to work with nobody logged in.
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('workshop', [
            'course' => $course->id,
            'name' => 'Peer review',
            'submissionstart' => mktime(0, 0, 0, 3, 1, 2030),
            'submissionend' => mktime(0, 0, 0, 4, 1, 2030),
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('submissions open:', $text);
        $this->assertStringContainsString('submissions close:', $text);
    }

    /**
     * MDL-INT-013: a database sends its closing date and not only the opening one.
     */
    public function test_the_closing_date_of_a_database_is_sent(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('data', [
            'course' => $course->id,
            'name' => 'Shared glossary',
            'timeavailablefrom' => mktime(0, 0, 0, 5, 1, 2030),
            'timeavailableto' => mktime(0, 0, 0, 6, 1, 2030),
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('available from:', $text);
        $this->assertStringContainsString('available until:', $text);
    }

    /**
     * MDL-INT-014: the time the student has to finish an activity reaches the AI service.
     */
    public function test_the_time_limit_of_an_activity_is_sent(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Timed quiz', 'timelimit' => 5400]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('time limit:', $text);
        $this->assertStringContainsString(format_time(5400), $text);
    }

    /**
     * MDL-INT-014: an activity with no time limit says nothing about it.
     */
    public function test_an_activity_without_a_time_limit_says_nothing(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('quiz', ['course' => $course->id, 'name' => 'Open quiz']);
        $student = $generator->create_and_enrol($course, 'student');

        $this->assertStringNotContainsString(
            'time limit:',
            context_preloader::build($course->id, (int)$student->id)
        );
    }

    /**
     * MDL-INT-015: an activity the student cannot open yet is announced with its condition.
     */
    public function test_an_activity_not_open_yet_is_announced_with_its_condition(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enableavailability = 1;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $future = time() + WEEKSECS;
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Locked page',
            'availability' => json_encode((object)[
                'op' => '&',
                'c' => [(object)['type' => 'date', 'd' => '>=', 't' => $future]],
                'showc' => [true],
            ]),
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Locked page', $text);
        $this->assertStringContainsString('not available yet:', $text);
    }

    /**
     * MDL-INT-015: an activity hidden from the student is not announced at all.
     */
    public function test_a_hidden_activity_is_not_announced(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden page', 'visible' => 0]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringNotContainsString('Hidden page', $text);
        $this->assertStringNotContainsString('not available yet:', $text);
    }

    /**
     * MDL-INT-016: whether the student has submitted an assignment reaches the AI service.
     */
    public function test_the_submission_state_of_the_student_is_sent(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Essay']);
        $student = $generator->create_and_enrol($course, 'student');

        $this->assertStringContainsString(
            'Essay: not submitted',
            context_preloader::build($course->id, (int)$student->id)
        );

        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // No cache to clear: what the user has done is worked out on every question.
        $text = context_preloader::build($course->id, (int)$student->id);
        $this->assertStringContainsString('Essay: submitted', $text);
        $this->assertStringNotContainsString('not submitted', $text);
    }

    /**
     * MDL-INT-016: whether the student completed an activity reaches the AI service.
     */
    public function test_the_completion_state_of_the_student_is_sent(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $generator->create_module('page', [
            'course' => $course->id,
            'name' => 'Read me',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $this->assertStringContainsString(
            'Read me: not completed',
            context_preloader::build($course->id, (int)$student->id)
        );
    }

    /**
     * MDL-INT-016: an activity that tracks nothing says nothing about progress.
     */
    public function test_an_activity_without_tracking_says_nothing_about_progress(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Just a page']);
        $student = $generator->create_and_enrol($course, 'student');

        $this->assertStringNotContainsString(
            'YOUR PROGRESS',
            context_preloader::build($course->id, (int)$student->id)
        );
    }
}
