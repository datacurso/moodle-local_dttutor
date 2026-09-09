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

    public function test_grades_are_not_sent_by_default(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->create_graded_course();

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Essay', $text);
        $this->assertStringNotContainsString('YOUR GRADES', $text);
        $this->assertStringNotContainsString('85.00 / 100.00', $text);
    }

    public function test_grades_are_sent_when_the_setting_is_enabled(): void {
        $this->resetAfterTest();
        set_config('include_grades', 1, 'local_dttutor');
        [$course, $student] = $this->create_graded_course();

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('YOUR GRADES', $text);
        $this->assertStringContainsString('85.00 / 100.00', $text);
    }
}
