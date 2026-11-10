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
 * Tests for the course knowledge pre-loader: what each user sees, and how it is reused.
 *
 * @package    local_dttutor
 * @category   test
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
     * The due date of an assignment as the course knowledge writes it, on the first of January.
     *
     * The whole line is matched, not the year alone: the identifiers of the course and its
     * activities are in the same text, and one such as 203000 holds the year 2030.
     *
     * @param int $year Year of the due date.
     * @return string
     */
    private function due_on_new_year(int $year): string {
        $timestamp = mktime(0, 0, 0, 1, 1, $year);
        return 'due: ' . userdate($timestamp);
    }

    /**
     * MDL-INT-010: the course knowledge is worked out once and reused by the next questions.
     */
    public function test_the_course_knowledge_is_reused_between_questions(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student, $assign] = $this->create_course_with_a_dated_assignment();
        $first = context_preloader::build($course->id, (int)$student->id);
        $this->assertStringContainsString($this->due_on_new_year(2030), $first);

        // Changed behind the course cache, so nothing tells the plugin the course moved on.
        $DB->set_field('assign', 'duedate', mktime(0, 0, 0, 1, 1, 2035), ['id' => $assign->id]);

        $second = context_preloader::build($course->id, (int)$student->id);
        $this->assertStringContainsString(
            $this->due_on_new_year(2030),
            $second,
            'The knowledge gathered for this user had to be reused instead of gathered again.'
        );
        $this->assertStringNotContainsString($this->due_on_new_year(2035), $second);
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
        $this->assertStringContainsString($this->due_on_new_year(2035), $text);
        $this->assertStringNotContainsString($this->due_on_new_year(2030), $text);
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
}
