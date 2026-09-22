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
     * MDL-INT-022: the history can be walked backwards without repeating messages already shown.
     */
    public function test_the_history_pages_do_not_repeat_messages_after_new_ones_arrive(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Page numbering shifts with every message added, so messages already shown '
            . 'can appear again when scrolling up. Verified in the interface once corrected.'
        );
    }

    /**
     * MDL-INT-037: conversations are removed once the retention period is over.
     */
    public function test_conversations_are_removed_once_the_retention_period_is_over(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] No retention policy exists yet: conversations only disappear with the user, '
            . 'with the course or through a privacy request. Business decision pending with the client.'
        );
    }

    /**
     * MDL-INT-040: using the tutor leaves a trace in the Moodle logs.
     */
    public function test_using_the_tutor_is_recorded_as_a_moodle_event(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] No event is triggered, so the platform keeps no record of who used the '
            . 'tutor, when and in which course.'
        );
    }

    /**
     * MDL-INT-041: the course switch survives backup, restore and duplication.
     */
    public function test_the_course_switch_survives_backup_and_restore(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The plugin takes no part in the course backup, so the restored course '
            . 'comes back with the tutor switched off and no warning.'
        );
    }

    /**
     * MDL-INT-042: the tutor can be switched on for several courses at once.
     */
    public function test_the_tutor_can_be_switched_on_for_several_courses_at_once(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Only the per-course switch exists: there is no activation by category, '
            . 'in bulk, or by default for new courses.'
        );
    }

    /**
     * MDL-INT-043: name, welcome message and institutional instructions can differ per course.
     */
    public function test_the_customisation_can_differ_between_courses(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Name, welcome message and institutional instructions are single values for '
            . 'the whole site. Scope decision pending with the client.'
        );
    }

    /**
     * MDL-INT-044: failures of the AI service reach the administrator inside Moodle.
     */
    public function test_failures_of_the_service_reach_the_administrator_inside_moodle(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Failures only land in the system error log, with no notice or report '
            . 'inside Moodle.'
        );
    }

    /**
     * MDL-E2E-013: the position configurator works with a touch screen and with a keyboard.
     */
    public function test_the_position_configurator_works_with_touch_and_keyboard(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Dragging only answers to a mouse, there is no keyboard alternative and '
            . 'overlaps with fixed controls are not warned about. Checked by hand on a tablet.'
        );
    }

    /**
     * MDL-E2E-021: the answer reaches the browser as the model produces it.
     */
    public function test_the_answer_reaches_the_browser_as_it_is_produced(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Moodle waits for the whole answer and then plays it back at a fixed pace. '
            . 'Measured against the real service, so it has no automated form yet.'
        );
    }

    /**
     * MDL-E2E-025: what the tutor may discuss while an attempt of a quiz is open.
     */
    public function test_the_tutor_scope_during_an_open_quiz_attempt(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The button is hidden inside the quiz, but the quiz still reaches the AI '
            . 'service and the student can ask from another tab. Scope decision pending with the client.'
        );
    }

    /**
     * MDL-E2E-026: the course page tells the teacher the state of the service and the consumption.
     */
    public function test_the_course_page_reports_the_state_of_the_service_and_the_consumption(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The page only shows the switch, so the teacher cannot tell whether there '
            . 'are credits left or how much the tutor has been used in their course.'
        );
    }

    /**
     * SYS-E2E-005: a response time target is defined and measured step by step.
     */
    public function test_a_response_time_target_is_defined_and_measured(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] None of the steps that make up an answer has a committed time, so there '
            . 'is no criterion to accept or reject the performance. Definition pending with the client.'
        );
    }

    /**
     * MDL-E2E-020: the chat offers starting a new conversation or clearing the history.
     */
    public function test_the_chat_offers_a_new_conversation_or_clearing_the_history(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The only way to restart is editing an earlier message, and the history '
            . 'cannot be cleared from the interface even though the server already supports deleting it.'
        );
    }

    /**
     * MDL-E2E-022: validation warnings are shown by the form, not by the tutor.
     */
    public function test_validation_warnings_are_shown_by_the_form(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] Warnings about a message being too long or invalid appear as a bubble of '
            . 'the conversation, as if the tutor had said them.'
        );
    }

    /**
     * MDL-E2E-023: the chat panel is usable on the screen of a phone.
     */
    public function test_the_panel_is_usable_on_a_phone_screen(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The panel has a fixed width with no adaptation to the size of the screen, '
            . 'so on a narrow phone it covers almost the whole page or runs off it.'
        );
    }

    /**
     * MDL-E2E-024: the page content moves aside under any theme.
     */
    public function test_the_page_content_moves_aside_under_any_theme(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The shift depends on a class of the Boost theme, so under another theme '
            . 'the panel can sit on top of the content instead of pushing it aside.'
        );
    }
}
