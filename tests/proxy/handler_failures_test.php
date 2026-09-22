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

use local_dttutor\fixtures\fake_ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * What the user is told when the AI service refuses or cannot be reached.
 *
 * See MDL-E2E-017 and MDL-E2E-018 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\handler
 */
final class handler_failures_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The text the user would end up seeing for a classified response.
     *
     * @param array $classified Result of the response classification.
     * @return string
     */
    private function user_facing_text(array $classified): string {
        return (string)($classified['message'] ?? $classified['error'] ?? '');
    }

    /**
     * MDL-E2E-018: a refusal because the licence is not authorised reaches the user as such.
     */
    public function test_a_licence_refusal_is_told_to_the_user_as_a_licence_problem(): void {
        $body = json_encode(['detail' => 'license_not_allowed']);

        $classified = handler::classify_response(403, 0, $body);
        $this->resetDebugging();

        $this->assertStringContainsString(
            get_string('error_license_not_allowed', 'local_dttutor'),
            $this->user_facing_text($classified),
            'A licence refusal must carry its own message instead of a generic error.'
        );
    }

    /**
     * MDL-E2E-018: a refusal because there are no credits left reaches the user as such.
     */
    public function test_a_credit_refusal_is_told_to_the_user_as_a_credit_problem(): void {
        $body = json_encode(['detail' => 'tokens_not_sufficient']);

        $classified = handler::classify_response(403, 0, $body);
        $this->resetDebugging();

        $this->assertStringContainsString(
            get_string('error_insufficient_tokens', 'local_dttutor'),
            $this->user_facing_text($classified),
            'A refusal for lack of credits must carry its own message instead of a generic error.'
        );
    }

    /**
     * MDL-E2E-017: a provider that cannot be initialised still ends in a visible error.
     */
    public function test_a_provider_that_cannot_be_initialised_still_reaches_the_browser(): void {
        fake_ai_client::bind_unavailable();
        $messages = [['role' => 'user', 'content' => 'Hello']];

        // Two buffers: the handler flushes the inner one as it writes, the outer one is read here.
        ob_start();
        ob_start();
        handler::run('any-model', $messages, 'student');
        ob_end_flush();
        $output = (string)ob_get_clean();
        $this->resetDebugging();

        $this->assertStringContainsString(
            'event: error',
            $output,
            'Every failure of the service must end in an error event the user can see.'
        );
        $this->assertStringNotContainsString('event: token', $output);
    }

    /**
     * MDL-E2E-018: a refusal the administrator cannot act on stays generic.
     */
    public function test_an_unknown_refusal_stays_generic(): void {
        $classified = handler::classify_response(403, 0, json_encode(['detail' => 'something_else']));
        $this->resetDebugging();

        $this->assertSame('ai_api_error', $this->user_facing_text($classified));
    }
}
