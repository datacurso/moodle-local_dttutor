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

/**
 * Behaviour that lives in the chat JavaScript and has no unit runner in this plugin.
 *
 * The plugin ships no JavaScript unit runner, and these cases cannot be driven from Behat either
 * without a stubbed AI service, so they stay visible here until one of the two exists.
 * See MDL-UNIT-009, MDL-UNIT-010, MDL-UNIT-012 and MDL-UNIT-013 of
 * cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class frontend_coverage_test extends \advanced_testcase {
    /**
     * MDL-UNIT-009: rich formatting and escaping of the answer of the tutor.
     */
    public function test_rich_formatting_and_escaping_of_the_answer(): void {
        $this->markTestSkipped(
            'Rendered in the browser by the chat module. Needs a JavaScript unit runner, or a stubbed '
            . 'AI service to drive it from Behat.'
        );
    }

    /**
     * MDL-UNIT-010: relative time stamps of the messages.
     */
    public function test_relative_time_stamps_of_the_messages(): void {
        $this->markTestSkipped(
            'Computed in the browser by the chat module. Needs a JavaScript unit runner, or a stubbed '
            . 'AI service to drive it from Behat.'
        );
    }

    /**
     * MDL-UNIT-012: cleaning of the text written by the user before it is sent.
     */
    public function test_the_text_written_by_the_user_reaches_the_service_whole(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The signs for less than and greater than are stripped before sending, so '
            . 'a question about code, formulas or numeric comparisons arrives incomplete. '
            . 'Lives in the chat module: needs a JavaScript unit runner to be checked.'
        );
    }

    /**
     * MDL-UNIT-013: the rule that turns down a message made of a single dot.
     */
    public function test_a_message_made_of_a_single_dot_is_treated_like_any_other(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] A specific rule turns down a single dot with an invalid message warning '
            . 'while any other single character is sent. '
            . 'Lives in the chat module: needs a JavaScript unit runner to be checked.'
        );
    }
}
