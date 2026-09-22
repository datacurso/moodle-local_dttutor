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
 * AI Chat Proxy — SSE streaming handler for dttutor.
 *
 * Performs a single chat completion call against the Datacurso AI proxy and
 * streams the answer to the browser. The model has no tools: it answers from
 * the pre-authorised course knowledge injected in the system message.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

use local_dttutor\httpclient\ai_client;
use local_dttutor\httpclient\client_factory;

/**
 * SSE streaming handler.
 */
class handler {
    /** @var array Refusals of the AI service, mapped to the message the user is given. */
    private const REFUSALS = [
        'license_not_allowed' => 'error_license_not_allowed',
        'tokens_not_sufficient' => 'error_insufficient_tokens',
    ];

    /**
     * Run a single streaming completion and relay it to the client.
     *
     * @param string $model    Model name.
     * @param array  $messages Conversation messages (system message first).
     * @param string $role     User role (already resolved), used for logging only.
     * @return string|null The final response text, or null on error.
     */
    public static function run(string $model, array $messages, string $role): ?string {
        \local_dttutor_log('RUN_START', ['model' => $model, 'role' => $role, 'user_message_count' => count($messages)]);

        echo ": thinking\n\n";
        self::flush();

        try {
            $response = self::call_ai_api_buffer($model, $messages);
        } catch (\Throwable $e) {
            // The provider could not even be built (no licence key, licence store unreachable).
            // The stream is already open, so the failure has to leave through it.
            \local_dttutor_log('AI_API_UNAVAILABLE', ['exception' => get_class($e)], true);
            $response = ['type' => 'error', 'error' => get_string('error_unexpected', 'local_dttutor')];
        }

        if ($response['type'] === 'error') {
            // The client only dispatches frames that carry an explicit event name.
            echo self::format_sse_event('error', ['error' => $response['error']]);
            self::flush();
            return null;
        }

        // Friendly notice (e.g. rate limit reached): stream it as a normal assistant bubble.
        if ($response['type'] === 'notice') {
            self::stream_sse_content($response['message']);
            return $response['message'];
        }

        // The text already left as the model produced it; only the end of the answer is left to say.
        echo self::format_sse_event('done', []);
        self::flush();
        return $response['content'];
    }

    /**
     * Describe an outgoing request for the log without any conversation content.
     *
     * Logging is metadata only (SEC-004): counts and lengths, never message text.
     *
     * @param  string $model    Model name.
     * @param  array  $messages Conversation messages.
     * @return array
     */
    public static function summarize_request_for_log(string $model, array $messages): array {
        $length = 0;
        foreach ($messages as $message) {
            if (isset($message['content']) && is_string($message['content'])) {
                $length += mb_strlen($message['content']);
            }
        }
        return [
            'model'          => $model,
            'messages_count' => count($messages),
            'content_length' => $length,
        ];
    }

    /**
     * Describe a model answer for the log without the answer itself.
     *
     * @param  string $text The answer text.
     * @return array
     */
    public static function summarize_response_for_log(string $text): array {
        $length = mb_strlen($text);
        return [
            'length'                      => $length,
            'estimated_completion_tokens' => (int)($length / 4),
        ];
    }

    /**
     * Build the chat completion payload sent to the AI proxy.
     *
     * The payload deliberately carries no tool definitions.
     *
     * @param  string $model    Model name.
     * @param  array  $messages Conversation messages.
     * @param  int    $userid   The requesting user's id.
     * @param  string $siteid   Anonymous site identifier, as reported by the AI client.
     * @return array
     */
    public static function build_payload(string $model, array $messages, int $userid, string $siteid): array {
        return [
            'model'          => $model,
            'messages'       => $messages,
            'stream'         => true,
            'max_tokens'     => 16384,
            'userid'         => (string)$userid,
            'site_id'        => $siteid,
            'stream_options' => ['include_usage' => true],
        ];
    }

    /**
     * Call the AI API with streaming (buffered).
     *
     * Sends the request to the Datacurso AI proxy which holds the actual
     * API key server-side. The client's License-Key (configured in the AI provider)
     * is used for authentication.
     *
     * @param  string $model    Model name.
     * @param  array  $messages Conversation messages.
     * @return array            ['type'=>'text', ...], ['type'=>'notice', ...] or ['type'=>'error', ...]
     */
    private static function call_ai_api_buffer(string $model, array $messages): array {
        // The base URL, site id and rate limit come from the AI client port — no hardcoded URLs here.
        global $USER;

        $client  = client_factory::get();
        $baseurl = rtrim($client->get_base_url(), '/');
        $apiurl  = $baseurl . '/provider/chat/completions';

        $payload = self::build_payload($model, $messages, (int)$USER->id, $client->get_site_id());

        \local_dttutor_log('AI_API_REQUEST', self::summarize_request_for_log($model, $messages));

        $headers = self::build_request_headers($client);

        $buffer = '';
        $pending = '';
        $streamed = '';

        $ch = curl_init($apiurl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$buffer, &$pending, &$streamed) {
                $buffer .= $data;

                // Only a successful answer is forwarded as it arrives. Anything else is kept back
                // so that it can be classified once the request is over and told properly.
                if ((int)curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
                    $streamed .= self::stream_chunk($data, $pending);
                }

                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $httpcode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerrno = curl_errno($ch);
        curl_close($ch);

        if ((int)$httpcode === 200 && $streamed !== '') {
            \local_dttutor_log('AI_API_RESPONSE_TEXT', self::summarize_response_for_log($streamed));
            return ['type' => 'text', 'content' => $streamed];
        }

        return self::classify_response((int)$httpcode, $curlerrno, $buffer);
    }

    /**
     * Forward the complete lines of a fragment of the answer, and report the text they carried.
     *
     * The service writes the answer in pieces that do not respect line boundaries, so whatever is
     * left half written stays in $pending until the rest of it arrives.
     *
     * @param string $chunk Fragment just received.
     * @param string $pending Carries the half-written line between fragments.
     * @return string The text forwarded out of this fragment.
     */
    public static function stream_chunk(string $chunk, string &$pending): string {
        $pending .= $chunk;

        $text = '';
        while (($breakat = strpos($pending, "\n")) !== false) {
            $line = trim(substr($pending, 0, $breakat));
            $pending = substr($pending, $breakat + 1);

            if (!str_starts_with($line, 'data: ')) {
                continue;
            }

            $raw = trim(substr($line, 6));
            if ($raw === '[DONE]') {
                $pending = '';
                break;
            }

            $json = json_decode($raw, true);
            if (!$json) {
                continue;
            }

            $delta = $json['choices'][0]['delta']['content'] ?? null;
            if ($delta === null || $delta === '') {
                continue;
            }

            $text .= $delta;
            echo self::format_sse_event('token', ['t' => $delta]);
            self::flush();
        }

        return $text;
    }

    /**
     * Build the HTTP header lines sent with the chat completion request.
     *
     * @param  ai_client $client The AI client port providing the license key and rate limit.
     * @return string[] "Name: value" lines, in the order they are sent.
     */
    public static function build_request_headers(ai_client $client): array {
        $licensekey = $client->get_license_key();

        $headers = [
            'Content-Type: application/json',
        ];
        if ($licensekey !== '') {
            $headers[] = 'License-Key: ' . $licensekey;
        }
        // Declare the real service so the AI service applies the per-plugin rate limit for
        // dttutor (the path /provider/chat/completions would otherwise resolve to the
        // provider itself), and forward the configured limit/window for local_dttutor.
        $headers[] = 'X-Service-Id: local_dttutor';
        foreach ($client->get_rate_limit_headers() as $rlheader) {
            $headers[] = $rlheader;
        }

        return $headers;
    }

    /**
     * Turn the raw outcome of the chat completion request into the handler response.
     *
     * Logs the same metadata-only events as before the extraction: AI_API_RATE_LIMITED,
     * AI_API_ERROR, AI_API_RESPONSE_EMPTY and AI_API_RESPONSE_TEXT.
     *
     * @param  int    $httpcode  HTTP status code (0 when the transport failed).
     * @param  int    $curlerrno cURL error number (0 on success).
     * @param  string $buffer    Raw response body.
     * @return array            ['type'=>'text', ...], ['type'=>'notice', ...] or ['type'=>'error', ...]
     */
    public static function classify_response(int $httpcode, int $curlerrno, string $buffer): array {
        // Rate limit reached: show the student a clear, friendly message (not a generic error).
        if ($httpcode === 403) {
            $err = json_decode($buffer, true);
            $detail = is_array($err) ? (string)($err['detail'] ?? '') : '';

            if ($detail === 'rate_limit_exceeded') {
                $resetat = (int)($err['reset_at'] ?? 0);
                $retryat = $resetat > 0
                    ? userdate($resetat, get_string('strftimedatetime', 'langconfig'))
                    : '';
                $message = get_string('error_ratelimit_exceeded', 'local_dttutor', $retryat);
                \local_dttutor_log('AI_API_RATE_LIMITED', ['reset_at' => $resetat], true);
                return ['type' => 'notice', 'message' => $message];
            }

            // A refusal the administrator can act on: say which one it is instead of a generic error.
            if (isset(self::REFUSALS[$detail])) {
                \local_dttutor_log('AI_API_REFUSED', ['detail' => $detail], true);
                return ['type' => 'error', 'error' => get_string(self::REFUSALS[$detail], 'local_dttutor')];
            }
        }

        if ($httpcode !== 200 || empty($buffer)) {
            \local_dttutor_log('AI_API_ERROR', [
                'http_code' => $httpcode,
                'curl_errno' => $curlerrno,
                'body_length' => strlen($buffer),
            ], true);
            return ['type' => 'error', 'error' => 'ai_api_error'];
        }

        $textbuf = self::parse_sse_buffer($buffer);

        if (empty($textbuf) && !empty($buffer)) {
            \local_dttutor_log('AI_API_RESPONSE_EMPTY', ['http_code' => $httpcode, 'body_length' => strlen($buffer)], true);
        }

        \local_dttutor_log('AI_API_RESPONSE_TEXT', self::summarize_response_for_log($textbuf));

        return ['type' => 'text', 'content' => $textbuf];
    }

    /**
     * Concatenate the content deltas of a buffered OpenAI-style SSE body.
     *
     * Only "data:" lines are read; frames that are not JSON are skipped and parsing stops
     * at the "[DONE]" sentinel.
     *
     * @param  string $buffer Raw SSE body.
     * @return string The answer text (empty when no delta carried content).
     */
    private static function parse_sse_buffer(string $buffer): string {
        $textbuf = '';
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
            if (isset($delta['content']) && $delta['content'] !== null) {
                $textbuf .= $delta['content'];
            }
        }

        return $textbuf;
    }

    /**
     * Build one Server-Sent Events frame.
     *
     * Every frame carries an explicit "event:" line because the browser client ignores
     * frames without one (token, done and error are dispatched).
     *
     * @param  string $event The event name.
     * @param  array  $data  The payload, serialised as a JSON object ({} when empty).
     * @return string The frame, terminated by the blank line that ends an SSE event.
     */
    public static function format_sse_event(string $event, array $data): string {
        return "event: {$event}\ndata: " . json_encode((object)$data, JSON_UNESCAPED_UNICODE) . "\n\n";
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
            echo self::format_sse_event('done', []);
            self::flush();
            return;
        }

        echo self::format_sse_event('token', ['t' => $content]);
        self::flush();

        echo self::format_sse_event('done', []);
        self::flush();
    }

    /**
     * Flush output buffers so the SSE frame reaches the client immediately.
     */
    private static function flush(): void {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
