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

namespace local_dttutor\event;

use local_dttutor\course_config;
use local_dttutor\proxy\request_guard;

/**
 * The trace the platform keeps of the use of the tutor.
 *
 * See MDL-INT-040 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\event\tutor_used
 */
final class tutor_used_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * A course with the tutor on and a student logged in.
     *
     * @return array{0: \stdClass, 1: \stdClass}
     */
    private function ready_course(): array {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        set_config('enabled', 1, 'aiprovider_datacurso');
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        return [$course, $student];
    }

    /**
     * MDL-INT-040: an accepted query leaves a record with who, when and where.
     */
    public function test_an_accepted_query_is_recorded(): void {
        [$course, $student] = $this->ready_course();

        $sink = $this->redirectEvents();
        request_guard::authorize([
            'messages' => [['role' => 'user', 'content' => 'When is the essay due?']],
            'context' => ['course_id' => (int)$course->id],
        ]);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(tutor_used::class, $event);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals($student->id, $event->userid);
        $this->assertEquals(\context_course::instance((int)$course->id)->id, $event->contextid);
        $this->assertGreaterThan(0, $event->timecreated);
    }

    /**
     * MDL-INT-040: the record says nothing about what was asked.
     */
    public function test_the_record_does_not_carry_the_question(): void {
        [$course] = $this->ready_course();

        $sink = $this->redirectEvents();
        request_guard::authorize([
            'messages' => [['role' => 'user', 'content' => 'A very private question']],
            'context' => ['course_id' => (int)$course->id],
        ]);
        $events = $sink->get_events();
        $sink->close();

        $event = reset($events);
        $this->assertStringNotContainsString('A very private question', json_encode($event->get_data()));
        $this->assertStringNotContainsString('A very private question', $event->get_description());
    }

    /**
     * MDL-INT-040: a query that is refused leaves no record of use.
     */
    public function test_a_refused_query_is_not_recorded(): void {
        [$course] = $this->ready_course();
        set_config('enabled', 0, 'local_dttutor');

        $sink = $this->redirectEvents();
        try {
            request_guard::authorize([
                'messages' => [['role' => 'user', 'content' => 'Hello']],
                'context' => ['course_id' => (int)$course->id],
            ]);
        } catch (\moodle_exception $e) {
            unset($e);
        }
        $events = $sink->get_events();
        $sink->close();

        $this->assertSame([], $events);
    }

    /**
     * MDL-INT-040: the record names the activity when the question comes from one.
     */
    public function test_the_record_names_the_activity(): void {
        [$course] = $this->ready_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $sink = $this->redirectEvents();
        request_guard::authorize([
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'context' => ['course_id' => (int)$course->id, 'activity_id' => (int)$page->cmid],
        ]);
        $events = $sink->get_events();
        $sink->close();

        $event = reset($events);
        $this->assertEquals($page->cmid, $event->other['cmid']);
        $this->assertEquals('page', $event->other['modname']);
    }
}
