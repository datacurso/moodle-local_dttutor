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
 * Tests for the availability and progress the course knowledge pre-loader sends.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class context_preloader_progress_test extends \advanced_testcase {
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
