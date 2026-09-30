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

namespace local_dttutor\local;

/**
 * Telling a quiz that is being sat from an attempt that was merely left open.
 *
 * See MDL-E2E-025 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\open_attempt
 */
final class open_attempt_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * A course with a quiz and a student enrolled in it.
     *
     * @param array $quizoptions Timing of the quiz.
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass} Course, quiz and student.
     */
    private function course_with_a_quiz(array $quizoptions = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', ['course' => $course->id] + $quizoptions);
        $student = $generator->create_and_enrol($course, 'student');
        return [$course, $quiz, $student];
    }

    /**
     * Store an attempt of a quiz.
     *
     * @param \stdClass $quiz
     * @param \stdClass $user
     * @param array $fields Values that override the defaults of the attempt.
     */
    private function add_attempt(\stdClass $quiz, \stdClass $user, array $fields = []): void {
        global $DB;
        $now = time();
        $DB->insert_record('quiz_attempts', (object)($fields + [
            'quiz' => $quiz->id,
            'userid' => $user->id,
            'attempt' => 1,
            'uniqueid' => $DB->count_records('quiz_attempts') + 1,
            'layout' => '1,0',
            'currentpage' => 0,
            'preview' => 0,
            'state' => 'inprogress',
            'timestart' => $now,
            'timefinish' => 0,
            'timemodified' => $now,
            'timemodifiedoffline' => 0,
            'sumgrades' => null,
        ]));
    }

    /**
     * MDL-E2E-025: with no attempt at all, nothing is being sat.
     */
    public function test_no_attempt_is_not_being_sat(): void {
        [$course, , $student] = $this->course_with_a_quiz();

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: a timed quiz is being sat while its time has not run out.
     */
    public function test_a_timed_quiz_within_its_time_is_being_sat(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $this->add_attempt($quiz, $student, ['timestart' => time() - (10 * MINSECS)]);

        $this->assertTrue(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: once the time of a timed quiz is gone, it is no longer being sat.
     */
    public function test_a_timed_quiz_past_its_time_is_not_being_sat(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $this->add_attempt($quiz, $student, [
            'timestart' => time() - (3 * HOURSECS),
            'timemodified' => time() - (3 * HOURSECS),
        ]);

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: the grace period of a timed quiz still counts as sitting it.
     */
    public function test_the_grace_period_still_counts(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz([
            'timelimit' => HOURSECS,
            'overduehandling' => 'graceperiod',
            'graceperiod' => HOURSECS,
        ]);
        $this->add_attempt($quiz, $student, [
            'state' => 'overdue',
            'timestart' => time() - (90 * MINSECS),
            'timemodified' => time() - (90 * MINSECS),
        ]);

        $this->assertTrue(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: a quiz with a closing date is being sat until that date.
     */
    public function test_a_quiz_with_a_closing_date_is_being_sat_until_it(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz(['timeclose' => time() + DAYSECS]);
        $this->add_attempt($quiz, $student, ['timemodified' => time() - (2 * DAYSECS)]);

        $this->assertTrue(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: with no deadline of any kind, recent activity is what tells them apart.
     */
    public function test_with_no_deadline_recent_activity_counts(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz();
        $this->add_attempt($quiz, $student, ['timemodified' => time() - MINSECS]);

        $this->assertTrue(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: an attempt left open weeks ago does not cost the student the tutor.
     *
     * This is why merely being open is not enough: a quiz with no time limit keeps its attempt
     * open until somebody submits it.
     */
    public function test_an_attempt_left_open_long_ago_is_not_being_sat(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz();
        $this->add_attempt($quiz, $student, [
            'timestart' => time() - (21 * DAYSECS),
            'timemodified' => time() - (21 * DAYSECS),
        ]);

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: an attempt already handed in is not being sat.
     */
    public function test_a_finished_attempt_is_not_being_sat(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $this->add_attempt($quiz, $student, ['state' => 'finished', 'timefinish' => time()]);

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: a teacher looking at the preview of a quiz is not sitting it.
     */
    public function test_a_preview_is_not_being_sat(): void {
        [$course, $quiz] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->add_attempt($quiz, $teacher, ['preview' => 1]);

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$teacher->id));
    }

    /**
     * MDL-E2E-025: an attempt in one course says nothing about another.
     */
    public function test_an_attempt_of_another_course_does_not_count(): void {
        [, $quiz, $student] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $other = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($student->id, $other->id, 'student');
        $this->add_attempt($quiz, $student);

        $this->assertFalse(open_attempt::is_being_sat((int)$other->id, (int)$student->id));
    }

    /**
     * MDL-E2E-025: the attempt of one student says nothing about another.
     */
    public function test_the_attempt_of_another_student_does_not_count(): void {
        [$course, $quiz, $student] = $this->course_with_a_quiz(['timelimit' => HOURSECS]);
        $classmate = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_attempt($quiz, $student);

        $this->assertFalse(open_attempt::is_being_sat((int)$course->id, (int)$classmate->id));
    }
}
