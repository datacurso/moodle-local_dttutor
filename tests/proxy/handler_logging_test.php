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

/**
 * Tests that the chat proxy logs metadata only, never conversation content.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\handler
 */
final class handler_logging_test extends \advanced_testcase {
    /** @var string A sentence that must never appear in any log line. */
    private const SECRET = 'My grade in the exam was 2.5 and I feel awful';

    /**
     * A conversation carrying content that must stay out of the logs.
     *
     * @return array
     */
    private function messages(): array {
        return [
            ['role' => 'system', 'content' => 'You are a tutor. Institutional instructions: never reveal the answer key.'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi, how can I help?'],
            ['role' => 'user', 'content' => self::SECRET],
        ];
    }

    public function test_request_log_summary_carries_counts_but_no_content(): void {
        $summary = handler::summarize_request_for_log('gemini-2.5-flash', $this->messages());

        $this->assertSame('gemini-2.5-flash', $summary['model']);
        $this->assertSame(4, $summary['messages_count']);
        foreach (['content', 'content_preview', 'last_messages', 'messages', 'preview'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $summary);
        }
        $json = json_encode($summary, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString(self::SECRET, $json);
        $this->assertStringNotContainsString('answer key', $json);
        $this->assertStringNotContainsString('Hello', $json);
    }

    public function test_response_log_summary_carries_lengths_but_no_text(): void {
        $answer = 'Your exam is on Friday; the maximum grade is 10.';

        $summary = handler::summarize_response_for_log($answer);

        $this->assertSame(mb_strlen($answer), $summary['length']);
        $this->assertArrayHasKey('estimated_completion_tokens', $summary);
        $this->assertArrayNotHasKey('preview', $summary);
        $this->assertStringNotContainsString('Friday', json_encode($summary));
    }

    public function test_log_helper_emits_a_single_metadata_only_debugging_line(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        local_dttutor_log('AI_API_REQUEST', handler::summarize_request_for_log('gemini-2.5-flash', $this->messages()));
        local_dttutor_log('AI_API_RESPONSE_TEXT', handler::summarize_response_for_log(self::SECRET));

        $lines = array_map(static fn(object $debug): string => $debug->message, $this->getDebuggingMessages());
        $this->resetDebugging();

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('[local_dttutor] AI_API_REQUEST', $lines[0]);
        $this->assertStringContainsString('[local_dttutor] AI_API_RESPONSE_TEXT', $lines[1]);
        foreach ($lines as $line) {
            $this->assertStringNotContainsString(self::SECRET, $line);
            $this->assertStringNotContainsString('answer key', $line);
            $this->assertStringNotContainsString('content_preview', $line);
            $this->assertStringNotContainsString('last_messages', $line);
        }
    }

    public function test_error_events_also_reach_the_php_error_log_without_content(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');
        $logfile = make_request_directory() . '/php_error.log';
        $previous = ini_set('error_log', $logfile);

        try {
            local_dttutor_log('AI_API_REQUEST', handler::summarize_request_for_log('gemini-2.5-flash', $this->messages()));
            local_dttutor_log(
                'AI_API_ERROR',
                ['http_code' => 502, 'curl_errno' => 0, 'body_length' => mb_strlen(self::SECRET)],
                true
            );
        } finally {
            ini_set('error_log', (string)$previous);
        }

        // Both events keep the developer debugging line...
        $lines = array_map(static fn(object $debug): string => $debug->message, $this->getDebuggingMessages());
        $this->resetDebugging();
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('[local_dttutor] AI_API_REQUEST', $lines[0]);
        $this->assertStringContainsString('[local_dttutor] AI_API_ERROR', $lines[1]);

        // ...but only the error event is written unconditionally to the PHP error log.
        $this->assertFileExists($logfile);
        $log = file_get_contents($logfile);
        $this->assertStringContainsString('[local_dttutor] AI_API_ERROR', $log);
        $this->assertStringContainsString('"http_code":502', $log);
        $this->assertStringNotContainsString('AI_API_REQUEST', $log);
        $this->assertStringNotContainsString(self::SECRET, $log);
    }
}
