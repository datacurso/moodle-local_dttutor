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
 * Tests for the dates and time limits the course knowledge pre-loader sends.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class context_preloader_dates_test extends \advanced_testcase {
    /**
     * MDL-INT-013: the dates of a lesson reach the AI service.
     */
    public function test_the_dates_of_a_lesson_are_sent(): void {
        $this->resetAfterTest();
        // Both generators reach the file API, which refuses to work with nobody logged in.
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $available = mktime(0, 0, 0, 1, 1, 2030);
        $deadline = mktime(0, 0, 0, 2, 1, 2030);
        $generator->create_module('lesson', [
            'course' => $course->id,
            'name' => 'Guided reading',
            'available' => $available,
            'deadline' => $deadline,
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        // The whole dates are matched, not the year alone, which an identifier such as 203000 holds.
        $opens = userdate($available);
        $due = userdate($deadline);
        $this->assertStringContainsString('opens: ' . $opens, $text);
        $this->assertStringContainsString('due: ' . $due, $text);
    }

    /**
     * MDL-INT-013: an activity that could have dates and has none says so.
     */
    public function test_an_activity_without_dates_says_that_none_are_set(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('assign', ['course' => $course->id, 'name' => 'Field report']);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('no dates set', $text);
        $this->assertStringNotContainsString('due:', $text);
    }

    /**
     * MDL-INT-013: an activity of a type that never has dates says nothing about them.
     */
    public function test_an_activity_that_cannot_have_dates_stays_silent_about_them(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Reading material']);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('Reading material', $text);
        $this->assertStringNotContainsString('no dates set', $text);
    }

    /**
     * MDL-INT-013: an activity with its dates set is never announced as having none.
     */
    public function test_an_activity_with_dates_is_not_announced_as_having_none(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_module('assign', [
            'course' => $course->id,
            'name' => 'Field report',
            'duedate' => mktime(0, 0, 0, 5, 1, 2030),
        ]);
        $student = $generator->create_and_enrol($course, 'student');

        $text = context_preloader::build($course->id, (int)$student->id);

        $this->assertStringContainsString('due:', $text);
        $this->assertStringNotContainsString('no dates set', $text);
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
}
