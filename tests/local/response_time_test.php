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
 * The time an answer takes, measured step by step against the time the site committed to.
 *
 * See SYS-E2E-005 of cases_data/dttutor/dttutor-2.0.10.md. Until there was a committed time there
 * was no criterion to accept or reject the performance of the tutor; these tests guard the
 * criterion and the measurement that is checked against it.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\response_time
 */
final class response_time_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * SYS-E2E-005: a site that says nothing still has a committed time.
     */
    public function test_a_site_that_configured_nothing_has_the_default_target(): void {
        $this->assertSame(response_time::DEFAULT_TARGET_SECONDS, response_time::target_seconds());
    }

    /**
     * SYS-E2E-005: the site decides how long an answer may take.
     */
    public function test_the_site_decides_the_target(): void {
        set_config('response_target_seconds', 8, 'local_dttutor');

        $this->assertSame(8, response_time::target_seconds());
        $this->assertTrue(response_time::is_late(8001));
        $this->assertFalse(response_time::is_late(8000));
    }

    /**
     * SYS-E2E-005: committing to no time leaves the measurement without a verdict.
     */
    public function test_a_target_of_zero_never_calls_an_answer_late(): void {
        set_config('response_target_seconds', 0, 'local_dttutor');

        $this->assertSame(0, response_time::target_seconds());
        $this->assertFalse(response_time::is_late(600000));
    }

    /**
     * SYS-E2E-005: a target cannot be negative, however it was written.
     */
    public function test_a_negative_target_is_read_as_none(): void {
        set_config('response_target_seconds', -5, 'local_dttutor');

        $this->assertSame(0, response_time::target_seconds());
    }

    /**
     * SYS-E2E-005: every step is measured on its own, in the order it happened.
     */
    public function test_the_steps_are_measured_one_by_one_and_in_order(): void {
        $timing = new response_time();
        $timing->step('session');
        $timing->step('knowledge');
        $timing->step('answer');
        $timing->step('persist');

        $this->assertSame(['session', 'knowledge', 'answer', 'persist'], array_keys($timing->steps()));
        foreach ($timing->steps() as $step => $elapsed) {
            $this->assertIsInt($elapsed, "The step {$step} has to be measured in milliseconds.");
            $this->assertGreaterThanOrEqual(0, $elapsed);
        }
    }

    /**
     * SYS-E2E-005: what is recorded is durations, and the total is one of them.
     */
    public function test_an_answer_within_the_target_is_recorded_for_developers(): void {
        set_config('response_target_seconds', 20, 'local_dttutor');
        $timing = new response_time();
        $timing->step('session');

        $timing->record();

        $this->assertDebuggingCalled();
    }

    /**
     * SYS-E2E-005: an answer that goes over the committed time is recorded as a failure.
     *
     * A site that nobody watches finds out from its own error log, the same way it finds out
     * about a service that stopped answering.
     */
    public function test_an_answer_over_the_target_is_recorded_as_late(): void {
        set_config('response_target_seconds', 1, 'local_dttutor');

        $this->assertTrue(response_time::is_late(1001));
        $this->assertFalse(response_time::is_late(999));
    }

    /**
     * SYS-E2E-005: the measurement carries durations and nothing else.
     */
    public function test_the_measurement_never_carries_anything_but_durations(): void {
        $timing = new response_time();
        $timing->step('session');
        $timing->step('answer');

        foreach ($timing->steps() as $elapsed) {
            $this->assertIsInt($elapsed);
        }
        $this->assertIsInt($timing->elapsed());
    }
}
