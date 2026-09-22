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
}
