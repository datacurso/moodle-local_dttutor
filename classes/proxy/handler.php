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

/**
 * AI Chat Proxy — tool-calling loop and SSE streaming handler for dttutor.
 *
 * Simplified for dttutor: only 3 base tools (call_webservice, ws_search,
 * ws_describe). The tool loop runs entirely in PHP — no external gateway
 * validation needed.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

use local_dttutor\agent\tool_executor;
use aiprovider_datacurso\httpclient\ai_services_api;

/**
 * SSE tool-calling loop handler.
 */
class handler {
    /**
     * Run the tool-calling loop using native OpenAI function calling.
     *
     * @param string $model    Model name.
     * @param array  $messages Conversation messages.
     * @param string $role     User role (already resolved).
     * @return string|null The final response text, or null on error.
     */
    public static function run(string $model, array $messages, string $role): ?string {
        \local_dttutor_log('RUN_START', ['model' => $model, 'role' => $role, 'user_message_count' => count($messages)]);

        $tools = \local_dttutor_get_tool_definitions($role);
        $toolrepeatcounts = [];

        for ($i = 0; $i < 50; $i++) {
            echo ": thinking\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            $response = self::call_ai_api_buffer($model, $messages, $tools);

            if ($response['type'] === 'error') {
                echo "data: {\"error\":\"ai_api_error\"}\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                return null;
            }

            if ($response['type'] === 'text') {
                \local_dttutor_log('AI_RESPONSE_TEXT', [
                    'iteration' => $i,
                    'preview' => mb_substr($response['content'], 0, 200),
                ]);
                self::stream_sse_content($response['content']);
                return $response['content'];
            }

            // Type 'tool_calls': execute each one and collect results.
            $toolcalls = $response['tool_calls'];
            \local_dttutor_log('AI_TOOL_CALLS', [
                'iteration' => $i,
                'tool_calls_count' => count($toolcalls),
                'tool_names' => array_map(fn($tc) => $tc['function']['name'] ?? '?', $toolcalls),
            ]);

            // Add the assistant message with tool_calls to conversation history.
            $messages[] = [
                'role'       => 'assistant',
                'content'    => null,
                'tool_calls' => $toolcalls,
            ];

            foreach ($toolcalls as $tc) {
                $name    = $tc['function']['name'];
                $tcid    = $tc['id'];
                $argsraw = $tc['function']['arguments'];
                $args    = is_array($argsraw) ? $argsraw : (json_decode($argsraw, true) ?? []);

                $callkey = $name . '|' . self::stable_tool_args_signature($args);
                $toolrepeatcounts[$callkey] = ($toolrepeatcounts[$callkey] ?? 0) + 1;

                // Notify the client that a tool is being executed.
                echo "data: " . json_encode([
                    '_dttutor_tool_executing' => ['tool' => $name, 'args' => $args],
                ], JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                if ($toolrepeatcounts[$callkey] > 5) {
                    $result = json_encode([
                        'error' => 'loop_detected',
                        'tool' => $name,
                        'detail' => 'Tool loop detected: ' . $name . ' called with same arguments '
                            . $toolrepeatcounts[$callkey] . ' times.',
                    ]);
                    \local_dttutor_log('LOOP_DETECTED', ['tool' => $name, 'args' => $args, 'count' => $toolrepeatcounts[$callkey]]);
                } else {
                    try {
                        $result = tool_executor::execute($name, json_encode($args));
                    } catch (\Throwable $e) {
                        $result = json_encode([
                            'error' => 'tool_failed',
                            'tool'  => $name,
                            'detail' => get_class($e) . ': ' . $e->getMessage(),
                        ]);
                    }
                }

                // Send result summary to client.
                echo "data: " . json_encode([
                    '_dttutor_tool_result' => [
                        'tool'    => $name,
                        'summary' => self::summarize_tool_result($name, $result),
                    ],
                ], JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Inject tool result as role=tool message (OpenAI standard format).
                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $tcid,
                    'content'      => $result,
                ];

                \local_dttutor_log('TOOL_RESULT', [
                    'tool' => $name,
                    'summary' => self::summarize_tool_result($name, $result),
                ]);
            }
        }

        \local_dttutor_log('RUN_FALLBACK_ITERATIONS_EXCEEDED', ['iteration_count' => 50]);
        $fallback = 'I apologize, but I was unable to complete this request. Please try rephrasing your question.';
        self::stream_sse_content($fallback);
        return $fallback;
    }

    /**
     * Build a deterministic signature for tool arguments.
     *
     * @param  array $args Tool arguments.
     * @return string
     */
    private static function stable_tool_args_signature(array $args): string {
        $normalized = self::sort_recursive($args);
        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE));
    }

    /**
     * Recursively sort associative arrays for stable hashing.
     *
     * @param  mixed $value Value to normalize (array or scalar).
     * @return mixed        The normalized value.
     */
    private static function sort_recursive($value) {
        if (!is_array($value)) {
            return $value;
        }
        $islist = array_keys($value) === range(0, count($value) - 1);
        if ($islist) {
            return array_map(static fn($v) => self::sort_recursive($v), $value);
        }
        ksort($value);
        foreach ($value as $k => $v) {
            $value[$k] = self::sort_recursive($v);
        }
        return $value;
    }

    /**
     * Call the AI API with streaming (buffered).
     *
     * Sends the request to the Datacurso AI proxy which holds the actual
     * API key server-side. The client's License-Key (from aiprovider_datacurso)
     * is used for authentication.
     *
     * @param  string $model    Model name.
     * @param  array  $messages Conversation messages.
     * @param  array  $tools    OpenAI-format tool definitions.
     * @return array            ['type'=>'text', ...] or ['type'=>'tool_calls', ...] or ['type'=>'error', ...]
     */
    private static function call_ai_api_buffer(string $model, array $messages, array $tools = []): array {
        // Use ai_services_api to get the base URL and License-Key.
        // The base URL is configured in aiprovider_datacurso — no hardcoded URLs here.
        global $USER;

        $aiservice      = new ai_services_api();
        $baseurl        = rtrim($aiservice->get_base_url(), '/');
        $apiurl         = $baseurl . '/provider/chat/completions';
        $licensekey     = get_config('aiprovider_datacurso', 'licensekey') ?: '';

        $payload = [
            'model'      => $model,
            'messages'   => $messages,
            'stream'     => true,
            'max_tokens' => 16384,
            'userid'     => (int)$USER->id,
            'stream_options' => ['include_usage' => true],
        ];
        if (!empty($tools)) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = 'auto';
        }

        // Log conversation context (last 3 messages, truncated).
        $contextpreview = array_map(static fn(array $msg): array => [
            'role' => $msg['role'] ?? '?',
            'content_preview' => isset($msg['content']) && is_string($msg['content'])
                ? mb_substr($msg['content'], 0, 150)
                : (isset($msg['tool_calls']) ? 'tool_calls:' . count($msg['tool_calls']) : ''),
            'tool_call_id' => $msg['tool_call_id'] ?? null,
        ], array_slice($messages, -3));
        \local_dttutor_log('AI_API_REQUEST', [
            'model' => $model,
            'messages_count' => count($messages),
            'last_messages' => $contextpreview,
        ]);

        $headers = [
            'Content-Type: application/json',
        ];
        if ($licensekey !== '') {
            $headers[] = 'License-Key: ' . $licensekey;
        }

        $buffer = '';
        $ch = curl_init($apiurl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$buffer) {
                $buffer .= $data;
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $httpcode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($httpcode !== 200 || empty($buffer)) {
            $bufpreview = substr($buffer, 0, 500);
            \local_dttutor_log('AI_API_ERROR', [
                'http_code' => $httpcode,
                'curl_error' => $curlerror,
                'preview' => $bufpreview,
            ]);
            return ['type' => 'error', 'error' => 'ai_api_error'];
        }

        $textbuf = '';
        $toolcallsacc = [];
        $finishreason = '';

        $lines = explode("\n", $buffer);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'data: ')) {
                continue;
            }
            $raw = trim(substr($line, 6));
            if ($raw === '[DONE]') {
                break;
            }
            $json = json_decode($raw, true);
            if (!$json) {
                continue;
            }
            $delta = $json['choices'][0]['delta'] ?? [];
            $finishreason = $json['choices'][0]['finish_reason'] ?? $finishreason;

            if (isset($delta['content']) && $delta['content'] !== null) {
                $textbuf .= $delta['content'];
            }
            if (!empty($delta['tool_calls'])) {
                foreach ($delta['tool_calls'] as $tc) {
                    $idx = $tc['index'] ?? 0;
                    if (!isset($toolcallsacc[$idx])) {
                        $toolcallsacc[$idx] = [
                            'id'       => $tc['id'] ?? '',
                            'type'     => $tc['type'] ?? 'function',
                            'function' => ['name' => '', 'arguments' => ''],
                        ];
                    }
                    if (!empty($tc['id'])) {
                        $toolcallsacc[$idx]['id'] = $tc['id'];
                    }
                    if (!empty($tc['function']['name'])) {
                        $toolcallsacc[$idx]['function']['name'] = $tc['function']['name'];
                    }
                    if (isset($tc['function']['arguments'])) {
                        $arg = $tc['function']['arguments'];
                        if (is_array($arg)) {
                            $toolcallsacc[$idx]['function']['arguments'] = json_encode($arg, JSON_UNESCAPED_UNICODE);
                        } else {
                            $toolcallsacc[$idx]['function']['arguments'] .= $arg;
                        }
                    }
                }
            }
        }

        if (!empty($toolcallsacc) || $finishreason === 'tool_calls') {
            ksort($toolcallsacc);
            $toolcalls = array_values($toolcallsacc);
            foreach ($toolcalls as &$tc) {
                if (empty($tc['id'])) {
                    $tc['id'] = 'call_' . substr(md5($tc['function']['name'] . microtime()), 0, 12);
                }
            }
            unset($tc);

            \local_dttutor_log('AI_API_RESPONSE_TOOL_CALLS', [
                'function_count' => count($toolcalls),
                'functions' => array_map(fn($tc) => $tc['function']['name'], $toolcalls),
            ]);
            return ['type' => 'tool_calls', 'tool_calls' => $toolcalls];
        }

        if (empty($textbuf) && !empty($buffer)) {
            \local_dttutor_log('AI_API_RESPONSE_EMPTY', ['buffer_preview' => substr($buffer, 0, 500)]);
        }

        $completionestimated = (int)(mb_strlen($textbuf) / 4);
        \local_dttutor_log('AI_API_RESPONSE_TEXT', [
            'length' => mb_strlen($textbuf),
            'estimated_completion_tokens' => $completionestimated,
            'preview' => mb_substr($textbuf, 0, 200),
        ]);

        return ['type' => 'text', 'content' => $textbuf];
    }

    /**
     * Stream text content to the client as SSE events.
     *
     * Emits 'token' events with {"t":"..."} and a 'done' event when complete.
     * Matches the existing Python backend SSE format for backward compatibility.
     *
     * @param string $content The text to stream.
     */
    private static function stream_sse_content(string $content): void {
        if (empty($content)) {
            echo "event: done\ndata: {}\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
            return;
        }

        $chunks = mb_str_split($content, 15);
        foreach ($chunks as $chunk) {
            echo "event: token\ndata: " . json_encode(['t' => $chunk], JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
            usleep(25000);
        }

        echo "event: done\ndata: {}\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Summarize a tool result for the client SSE event.
     *
     * @param  string $name   The tool name.
     * @param  string $result The raw JSON result returned by the tool.
     * @return string         A short human-readable summary.
     */
    private static function summarize_tool_result(string $name, string $result): string {
        $decoded = json_decode($result, true);
        if (!$decoded) {
            return 'Invalid result';
        }
        if (isset($decoded['error'])) {
            return 'Error: ' . $decoded['error'];
        }
        return match ($name) {
            'ws_search' => ($decoded['count'] ?? count($decoded['functions'] ?? $decoded) ?? 0) . ' functions found',
            'ws_describe' => 'Function details retrieved',
            'call_webservice' => 'Web service executed successfully',
            default => 'Done',
        };
    }
}
