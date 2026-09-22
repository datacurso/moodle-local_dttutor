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
 * Every test in this file describes the behaviour the scope requires and is expected to FAIL
 * until the corresponding defect is corrected. See MDL-E2E-017 and MDL-E2E-018 of
 * cases_data/dttutor/dttutor-2.0.9.md, both classified as [Pendiente:fail].
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
     * MDL-E2E-018: a refusal because the licence is not authorised must reach the user as such.
     *
     * [Pendiente:fail] Today every refusal other than the consumption limit is turned into a
     * generic error, so neither the user nor the administrator learns that the problem is the licence.
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
     * MDL-E2E-018: a refusal because there are no credits left must reach the user as such.
     *
     * [Pendiente:fail] Today it is turned into a generic error.
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
     *
     * [Pendiente:fail] Today the failure escapes before anything is written to the browser, so the
     * typing indicator disappears and the user receives neither an answer nor an error.
     */
    public function test_a_provider_that_cannot_be_initialised_still_reaches_the_browser(): void {
        fake_ai_client::bind_unavailable();
        $messages = [['role' => 'user', 'content' => 'Hello']];

        ob_start();
        try {
            handler::run('any-model', $messages, 'student');
        } catch (\Throwable $e) {
            // Swallowed on purpose: the point of the test is what the browser received, and a
            // failure that never reaches the stream is exactly the defect being reported.
            unset($e);
        } finally {
            $output = (string)ob_get_clean();
        }
        $this->resetDebugging();

        $this->assertStringContainsString(
            'event: error',
            $output,
            'Every failure of the service must end in an error event the user can see.'
        );
    }
}
