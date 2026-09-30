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

namespace local_dttutor;

use local_dttutor\external\save_course_config;

/**
 * Scope items with no home of their own: features still to be built and one misleading message.
 *
 * See MDL-INT-022, MDL-INT-037, MDL-INT-040 to MDL-INT-044, MDL-E2E-010, MDL-E2E-013,
 * MDL-E2E-020 to MDL-E2E-026 and SYS-E2E-005 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class scope_gaps_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * MDL-E2E-010: with the chat switched off for the site, the message names that situation.
     */
    public function test_the_refusal_with_the_chat_off_site_wide_names_that_situation(): void {
        $this->setAdminUser();
        set_config('enabled', 0, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        try {
            save_course_config::execute((int)$course->id, true);
            $this->fail('The tutor is switched off for the site, so the request had to be refused.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(get_string('error_tutor_disabled_site', 'local_dttutor'), $e->getMessage());
            $this->assertNotEquals(get_string('error_api_not_configured', 'local_dttutor'), $e->getMessage());
        }
    }

    /**
     * SYS-E2E-005: a response time target is defined and measured step by step.
     *
     * The measurement of the real service belongs to a run against it, which no test can stand in
     * for. What is guarded here is what made that run impossible to judge: that a committed time
     * exists, and that an answer is timed step by step against it.
     */
    public function test_a_response_time_target_is_defined_and_measured(): void {
        $this->assertGreaterThan(0, \local_dttutor\local\response_time::target_seconds());

        $timing = new \local_dttutor\local\response_time();
        $timing->step('session');
        $timing->step('knowledge');
        $timing->step('answer');
        $timing->step('persist');

        $this->assertSame(
            ['session', 'knowledge', 'answer', 'persist'],
            array_keys($timing->steps()),
            'The four steps of an answer are measured apart, because they belong to different owners.'
        );
        $this->assertStringContainsString(
            '$timing->record()',
            file_get_contents(__DIR__ . '/../chatproxy.php'),
            'The proxy has to record what each answer took, or the target has nothing to check.'
        );
    }
}
