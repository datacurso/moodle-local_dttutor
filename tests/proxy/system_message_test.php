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

use local_dttutor\course_config;

/**
 * Tests for the system message builder.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\system_message
 */
final class system_message_test extends \advanced_testcase {
    public function test_system_message_does_not_reference_web_service_tools(): void {
        $this->resetAfterTest();
        $context = ['course_id' => 5, 'course_name' => 'Algebra', 'location' => 'course', 'page_url' => 'x'];

        $message = system_message::build('student', $context, "COURSE KNOWLEDGE:\n- Course: Algebra\n");

        $this->assertSame('system', $message['role']);
        foreach (['ws_search', 'ws_describe', 'call_webservice', 'web service', 'tool'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $message['content']);
        }
        $this->assertStringContainsString('COURSE KNOWLEDGE', $message['content']);
    }

    public function test_system_message_instructs_to_admit_missing_information(): void {
        $this->resetAfterTest();

        $message = system_message::build('student', ['course_id' => 5], '');

        $this->assertStringContainsStringIgnoringCase('not available', $message['content']);
    }

    public function test_system_message_never_contains_the_users_fullname(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Ximena', 'lastname' => 'Quintanilla']);
        $this->setUser($user);
        // The client used to post its own page context, including the user's name; none of it may reach the prompt.
        $context = [
            'course_id' => 5,
            'course_name' => 'Algebra',
            'location' => 'course',
            'page_url' => 'https://example.com/course/view.php?id=5',
            'page_title' => 'Course: Algebra',
            'user_fullname' => fullname($user),
            'user_id' => $user->id,
        ];

        $message = system_message::build('student', $context, "COURSE KNOWLEDGE:\n- Course: Algebra\n");

        $this->assertStringNotContainsString(fullname($user), $message['content']);
        $this->assertStringNotContainsString('Ximena', $message['content']);
        $this->assertStringNotContainsString('Quintanilla', $message['content']);
    }

    public function test_page_context_helper_no_longer_exists(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        $this->assertFalse(function_exists('local_dttutor_get_page_context'));
    }

    public function test_system_message_contains_only_the_site_level_prompt(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        set_config('custom_prompt', 'SITE RULE: always greet politely.', 'local_dttutor');
        // There is no per-course prompt any more: a stray custom_prompt key is ignored by the model.
        course_config::update((int)$course->id, ['custom_prompt' => 'COURSE RULE: only discuss chapter 3.']);

        $message = system_message::build('student', ['course_id' => (int)$course->id], '');

        $content = $message['content'];
        $this->assertStringContainsString('Institutional custom instructions', $content);
        $this->assertSame(1, substr_count($content, 'SITE RULE: always greet politely.'), 'The site prompt must appear once');
        $this->assertStringNotContainsString('COURSE RULE', $content);
        $this->assertStringNotContainsString('Course custom instructions', $content);
    }

    public function test_system_message_has_no_prompt_section_when_the_site_prompt_is_empty(): void {
        $this->resetAfterTest();
        unset_config('custom_prompt', 'local_dttutor');

        $message = system_message::build('student', ['course_id' => 5], '');

        $this->assertStringNotContainsString('custom instructions', $message['content']);
    }

    public function test_location_hint_is_included_for_a_known_location(): void {
        $this->resetAfterTest();
        $context = ['course_id' => 5, 'course_name' => 'Algebra', 'location' => 'course'];

        $message = system_message::build('student', $context, '');

        $this->assertStringContainsString(get_string('ctx_loc_course', 'local_dttutor') . ' "Algebra"', $message['content']);
        $this->assertStringContainsString('- Location: course', $message['content']);
    }

    public function test_no_location_hint_for_an_unknown_location(): void {
        $this->resetAfterTest();

        $message = system_message::build('student', ['course_id' => 5, 'location' => 'unknown'], '');

        $this->assertStringContainsString('- Location: unknown', $message['content']);
        foreach (['course', 'activity', 'gradebook', 'admin', 'dashboard', 'messages', 'profile', 'calendar', 'files'] as $key) {
            $this->assertStringNotContainsString(get_string('ctx_loc_' . $key, 'local_dttutor'), $message['content']);
        }
    }
}
