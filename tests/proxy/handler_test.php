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

        $payload = handler::build_payload('gemini-2.5-flash', $messages, 42);

        $this->assertArrayNotHasKey('tools', $payload);
        $this->assertArrayNotHasKey('tool_choice', $payload);
        $this->assertSame('gemini-2.5-flash', $payload['model']);
        $this->assertSame($messages, $payload['messages']);
        $this->assertTrue($payload['stream']);
        $this->assertSame('42', $payload['userid']);
        $this->assertNotEmpty($payload['site_id']);
    }

    public function test_payload_is_serialisable_without_tool_keys_anywhere(): void {
        $this->resetAfterTest();

        $payload = handler::build_payload('gemini-2.5-flash', [['role' => 'user', 'content' => 'Hi']], 7);
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
}
