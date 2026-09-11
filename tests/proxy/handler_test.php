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
 * Tests for the chat proxy streaming handler.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\handler
 */
final class handler_test extends \advanced_testcase {
    public function test_payload_has_no_tool_definitions(): void {
        $this->resetAfterTest();
        $messages = [
            ['role' => 'system', 'content' => 'You are a tutor.'],
            ['role' => 'user', 'content' => 'Hello'],
        ];

        $payload = handler::build_payload('gemini-2.5-flash', $messages, 42, 'fake-site');

        $this->assertArrayNotHasKey('tools', $payload);
        $this->assertArrayNotHasKey('tool_choice', $payload);
        $this->assertSame('gemini-2.5-flash', $payload['model']);
        $this->assertSame($messages, $payload['messages']);
        $this->assertTrue($payload['stream']);
        $this->assertSame('42', $payload['userid']);
        $this->assertSame('fake-site', $payload['site_id']);
    }

    public function test_payload_is_serialisable_without_tool_keys_anywhere(): void {
        $this->resetAfterTest();

        $payload = handler::build_payload('gemini-2.5-flash', [['role' => 'user', 'content' => 'Hi']], 7, 'fake-site');
        $json = json_encode($payload);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('"tools"', $json);
        $this->assertStringNotContainsString('tool_choice', $json);
    }

    public function test_error_frame_carries_an_explicit_error_event_and_json_data(): void {
        $frame = handler::format_sse_event('error', ['error' => 'ai_api_error']);

        $this->assertStringStartsWith("event: error\n", $frame);
        $this->assertStringEndsWith("\n\n", $frame);
        $this->assertSame(1, preg_match('/^data: (.*)$/m', $frame, $matches));
        $this->assertSame(['error' => 'ai_api_error'], json_decode($matches[1], true));
    }

    public function test_token_frame_keeps_the_token_event(): void {
        $frame = handler::format_sse_event('token', ['t' => 'Hola ñ']);

        $this->assertSame("event: token\ndata: {\"t\":\"Hola ñ\"}\n\n", $frame);
    }

    public function test_done_frame_has_an_empty_json_object(): void {
        $this->assertSame("event: done\ndata: {}\n\n", handler::format_sse_event('done', []));
    }

    public function test_request_headers_carry_the_license_key_and_the_service_id_in_order(): void {
        $headers = handler::build_request_headers(new fake_ai_client());

        $this->assertSame([
            'Content-Type: application/json',
            'License-Key: fake-license',
            'X-Service-Id: local_dttutor',
        ], $headers);
    }

    public function test_request_headers_append_every_rate_limit_header_after_the_service_id(): void {
        $client = new fake_ai_client();
        $client->set_rate_limit_headers(['X-Rate-Limit: 20', 'X-Rate-Window: 3600']);

        $headers = handler::build_request_headers($client);

        $this->assertSame([
            'Content-Type: application/json',
            'License-Key: fake-license',
            'X-Service-Id: local_dttutor',
            'X-Rate-Limit: 20',
            'X-Rate-Window: 3600',
        ], $headers);
    }

    public function test_request_headers_omit_the_license_key_when_none_is_configured(): void {
        $client = new fake_ai_client();
        $client->set_license_key('');

        $headers = handler::build_request_headers($client);

        $this->assertSame(['Content-Type: application/json', 'X-Service-Id: local_dttutor'], $headers);
    }

    public function test_rate_limited_response_becomes_a_notice_with_the_retry_time(): void {
        $resetat = time() + 120;
        $body = json_encode(['detail' => 'rate_limit_exceeded', 'reset_at' => $resetat]);

        $response = handler::classify_response(403, 0, $body);

        $retryat = userdate($resetat, get_string('strftimedatetime', 'langconfig'));
        $this->assertSame([
            'type' => 'notice',
            'message' => get_string('error_ratelimit_exceeded', 'local_dttutor', $retryat),
        ], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_RATE_LIMITED {"reset_at":' . $resetat . '}');
    }

    public function test_rate_limited_response_without_reset_time_is_a_notice_with_an_empty_retry_time(): void {
        $response = handler::classify_response(403, 0, json_encode(['detail' => 'rate_limit_exceeded']));

        $this->assertSame([
            'type' => 'notice',
            'message' => get_string('error_ratelimit_exceeded', 'local_dttutor', ''),
        ], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_RATE_LIMITED {"reset_at":0}');
    }

    public function test_forbidden_response_with_another_detail_is_a_generic_error(): void {
        $body = json_encode(['detail' => 'license_invalid']);

        $response = handler::classify_response(403, 0, $body);

        $this->assertSame(['type' => 'error', 'error' => 'ai_api_error'], $response);
        $this->assertDebuggingCalled(
            '[local_dttutor] AI_API_ERROR {"http_code":403,"curl_errno":0,"body_length":' . strlen($body) . '}'
        );
    }

    public function test_forbidden_response_with_malformed_json_is_a_generic_error(): void {
        $response = handler::classify_response(403, 0, '{"detail": "rate_limit_exceeded"');

        $this->assertSame(['type' => 'error', 'error' => 'ai_api_error'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_ERROR {"http_code":403,"curl_errno":0,"body_length":32}');
    }

    public function test_server_error_is_a_generic_error(): void {
        $response = handler::classify_response(500, 0, 'Internal Server Error');

        $this->assertSame(['type' => 'error', 'error' => 'ai_api_error'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_ERROR {"http_code":500,"curl_errno":0,"body_length":21}');
    }

    public function test_ok_status_with_an_empty_body_is_a_generic_error(): void {
        $response = handler::classify_response(200, 0, '');

        $this->assertSame(['type' => 'error', 'error' => 'ai_api_error'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_ERROR {"http_code":200,"curl_errno":0,"body_length":0}');
    }

    public function test_transport_failure_is_a_generic_error(): void {
        $response = handler::classify_response(0, CURLE_OPERATION_TIMEDOUT, '');

        $this->assertSame(['type' => 'error', 'error' => 'ai_api_error'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_ERROR {"http_code":0,"curl_errno":28,"body_length":0}');
    }

    public function test_sse_body_is_concatenated_into_text(): void {
        $body = "data: {\"choices\":[{\"delta\":{\"content\":\"Hel\"}}]}\n\n"
            . "data: {\"choices\":[{\"delta\":{\"content\":\"lo\"}}]}\n\n"
            . "data: [DONE]\n\n";

        $response = handler::classify_response(200, 0, $body);

        $this->assertSame(['type' => 'text', 'content' => 'Hello'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_RESPONSE_TEXT {"length":5,"estimated_completion_tokens":1}');
    }

    public function test_sse_frames_after_done_and_non_json_frames_are_ignored(): void {
        $body = "data: {\"choices\":[{\"delta\":{\"role\":\"assistant\"}}]}\n\n"
            . "data: not json\n\n"
            . ": keep-alive\n\n"
            . "data: {\"choices\":[{\"delta\":{\"content\":\"Hi\"}}]}\n\n"
            . "data: [DONE]\n\n"
            . "data: {\"choices\":[{\"delta\":{\"content\":\"late\"}}]}\n\n";

        $response = handler::classify_response(200, 0, $body);

        $this->assertSame(['type' => 'text', 'content' => 'Hi'], $response);
        $this->assertDebuggingCalled('[local_dttutor] AI_API_RESPONSE_TEXT {"length":2,"estimated_completion_tokens":0}');
    }

    public function test_ok_body_without_content_deltas_is_empty_text_and_logs_response_empty(): void {
        $body = "data: {\"choices\":[{\"delta\":{}}]}\n\ndata: [DONE]\n\n";

        $response = handler::classify_response(200, 0, $body);

        $this->assertSame(['type' => 'text', 'content' => ''], $response);
        $this->assertDebuggingCalledCount(2, [
            '[local_dttutor] AI_API_RESPONSE_EMPTY {"http_code":200,"body_length":' . strlen($body) . '}',
            '[local_dttutor] AI_API_RESPONSE_TEXT {"length":0,"estimated_completion_tokens":0}',
        ]);
    }
}
