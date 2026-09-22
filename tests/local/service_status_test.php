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

use local_dttutor\event\service_failed;
use local_dttutor\event\tutor_used;

/**
 * What the course page and the settings page can say about the service.
 *
 * See MDL-E2E-026 and MDL-INT-044 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\service_status
 */
final class service_status_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Turn on a log the reports can read, which a bare test site does not have.
     */
    private function enable_the_log(): void {
        $this->preventResetByRollback();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        get_log_manager(true);
    }

    /**
     * MDL-E2E-026: with the provider disabled the page says the service is not available.
     */
    public function test_the_service_is_reported_as_unavailable_without_the_provider(): void {
        unset_config('enabled', 'aiprovider_datacurso');

        $status = service_status::get();

        $this->assertFalse($status['available']);
        $this->assertNull($status['credits']);
    }

    /**
     * MDL-E2E-026: with the provider enabled the page says the service is available.
     */
    public function test_the_service_is_reported_as_available_with_the_provider(): void {
        set_config('enabled', 1, 'aiprovider_datacurso');

        $status = service_status::get();

        $this->assertTrue($status['available']);
    }

    /**
     * MDL-E2E-026: the use of the tutor in a course is counted from the logs of the platform.
     */
    public function test_the_use_of_the_tutor_in_a_course_is_counted(): void {
        $this->enable_the_log();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        foreach ([$course, $course, $other] as $where) {
            tutor_used::create([
                'context' => \context_course::instance((int)$where->id),
                'other' => ['cmid' => 0, 'modname' => ''],
            ])->trigger();
        }

        $this->assertSame(2, service_status::get_course_usage((int)$course->id));
        $this->assertSame(1, service_status::get_course_usage((int)$other->id));
    }

    /**
     * MDL-E2E-026: a course nobody asked about reports no use.
     */
    public function test_a_course_with_no_questions_reports_none(): void {
        $this->enable_the_log();
        $course = $this->getDataGenerator()->create_course();

        $this->assertSame(0, service_status::get_course_usage((int)$course->id));
    }

    /**
     * MDL-INT-044: the failures of the service are there for the administrator to see.
     */
    public function test_the_failures_of_the_service_are_counted(): void {
        $this->enable_the_log();

        $this->assertSame(0, service_status::get_recent_failures());

        service_failed::record('license_not_allowed');
        service_failed::record('transport');

        $this->assertSame(2, service_status::get_recent_failures());
    }
}
