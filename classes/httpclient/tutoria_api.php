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
 * Tutor-IA HTTP client for chat API communication
 *
 * @package    local_dttutor
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\httpclient;

use cache;
use local_dttutor\local\pending_deletion;
use local_dttutor\session_store;
use moodle_exception;

/**
 * HTTP client for Tutor-IA Chat API
 *
 * @package    local_dttutor
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tutoria_api {
    /**
     * Seconds between backend liveness checks for cached sessions.
     *
     * @var int
     */
    private const SESSION_BACKEND_VALIDATION_INTERVAL = 300;

    /** @var int Messages read per page when checking a replay against the session it replaces. */
    private const REPLAY_HISTORY_PAGE_SIZE = 100;

    /** @var int Pages read at most when checking a replay; the browser sends 40 messages at most. */
    private const REPLAY_HISTORY_PAGES = 5;

    /** @var ai_client AI service client (port). */
    private ai_client $client;

    /** @var cache|null Cache instance for storing sessions. */
    private ?cache $cache;

    /**
     * Constructor to initialize the Tutor-IA API client.
     *
     * @param ai_client|null $client AI service client to use; defaults to {@see client_factory::get()}.
     *                               Tests inject a double here to avoid network access.
     * @since Moodle 4.5
     */
    public function __construct(?ai_client $client = null) {
        $this->client = $client ?? client_factory::get();

        try {
            $this->cache = cache::make('local_dttutor', 'sessions');
        } catch (\Exception $e) {
            debugging('Cache initialization failed: ' . get_class($e), DEBUG_DEVELOPER);
            $this->cache = null;
        }
    }

    /**
     * Start a new chat session using V2 endpoint (no MCP, no indexing).
     *
     * Used by chatproxy.php — creates a Redis session without MCP or indexing.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course Module ID (optional).
     * @return array Session data including session_id.
     * @throws moodle_exception If session creation fails.
     * @since Moodle 4.5
     */
    public function start_session_v2(int $courseid, int $userid, ?int $cmid = null): array {
        $cachekey = self::get_session_v2_cache_key($courseid, $userid, $cmid);
        $cached = null;

        if ($this->cache !== null) {
            $cached = $this->cache->get($cachekey);
        }

        if ($cached && $this->is_session_valid($cached)) {
            if (!$this->should_validate_cached_session($cached)) {
                return $cached;
            }

            if (!empty($cached['session_id']) && $this->is_backend_session_alive((string)$cached['session_id'])) {
                $cached['backend_validated_at'] = time();
                if ($this->cache !== null) {
                    $this->cache->set($cachekey, $cached);
                }
                return $cached;
            }

            if ($this->cache !== null) {
                $this->cache->delete($cachekey);
            }
        }

        $requestdata = [
            'course_id' => (string) $courseid,
            'user_id' => (string) $userid,
        ];
        if ($cmid !== null) {
            $requestdata['module_id'] = (string) $cmid;
        }

        $response = $this->client->request('POST', '/chat/start/v2', $requestdata);

        return $this->remember_session($cachekey, $response, $courseid, $userid, $cmid);
    }

    /**
     * Reset a V2 chat session and create a fresh one.
     *
     * Used when the user edits a previous message and the conversation branch changes.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course Module ID (optional).
     * @return array Fresh session data.
     * @throws moodle_exception If session creation fails.
     */
    public function reset_session_v2(int $courseid, int $userid, ?int $cmid = null): array {
        $cachekey = self::get_session_v2_cache_key($courseid, $userid, $cmid);

        if ($this->cache !== null) {
            $cached = $this->cache->get($cachekey);
            if (!empty($cached['session_id'])) {
                try {
                    $this->delete_session((string)$cached['session_id']);
                } catch (\Throwable $e) {
                    debugging('Failed to delete stale Tutor-IA session: ' . get_class($e), DEBUG_DEVELOPER);
                }
            }
            $this->cache->delete($cachekey);
        }

        return $this->start_session_v2($courseid, $userid, $cmid);
    }

    /**
     * Append a message to chat history without queueing for AI processing.
     *
     * Used by chatproxy.php to persist messages after the PHP-side tool loop.
     *
     * @param string $sessionid Session ID.
     * @param string $role Message role: user or assistant.
     * @param string $content Message content.
     * @param array|null $meta Optional metadata.
     * @return array Response with appended status.
     * @throws moodle_exception If the request fails.
     * @since Moodle 4.5
     */
    public function append_message(string $sessionid, string $role, string $content, ?array $meta = null): array {
        $payload = [
            'session_id' => $sessionid,
            'role' => $role,
            'content' => $content,
        ];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }
        return $this->client->request('POST', '/chat/messages/append/v2', $payload);
    }

    /**
     * The answers the tutor gave in a session, as keys that a replay can be checked against.
     *
     * Replaying a conversation into a fresh session takes its messages from the browser, and
     * nothing stops a browser from sending an answer the tutor never gave. Only answers found in
     * the session being replaced are trusted.
     *
     * @param string $sessionid Session being replaced.
     * @return bool[] Answers of the session, keyed by {@see self::answer_key()}.
     * @throws moodle_exception If the history cannot be read.
     */
    public function assistant_answers(string $sessionid): array {
        $answers = [];
        for ($page = 0; $page < self::REPLAY_HISTORY_PAGES; $page++) {
            $response = $this->get_history($sessionid, self::REPLAY_HISTORY_PAGE_SIZE, $page * self::REPLAY_HISTORY_PAGE_SIZE);
            $messages = (array)($response['messages'] ?? []);
            foreach ($messages as $message) {
                if (is_array($message) && ($message['role'] ?? '') === 'assistant') {
                    $answers[self::answer_key((string)($message['content'] ?? ''))] = true;
                }
            }
            if ($messages === [] || empty($response['pagination']['has_more'])) {
                break;
            }
        }
        return $answers;
    }

    /**
     * The messages of a conversation that may be written into a fresh session.
     *
     * What the user wrote is theirs to replay. An answer is replayed only when the tutor really
     * gave it in the session being replaced.
     *
     * @param array $messages Conversation sent by the browser, as role and content.
     * @param bool[] $answers Answers of the session being replaced, keyed by {@see self::answer_key()}.
     * @return array The messages to replay, in their order.
     */
    public static function replayable_messages(array $messages, array $answers): array {
        $replay = [];
        foreach ($messages as $message) {
            $content = (string)$message['content'];
            if (trim($content) === '') {
                continue;
            }
            if ($message['role'] === 'assistant' && !isset($answers[self::answer_key($content)])) {
                continue;
            }
            $replay[] = $message;
        }
        return $replay;
    }

    /**
     * The form in which an answer is compared, whichever side cut it short.
     *
     * The browser sends a message cut to the length the proxy accepts, so both sides are cut the
     * same way before they are compared.
     *
     * @param string $content Text of the answer.
     * @return string
     */
    private static function answer_key(string $content): string {
        return sha1(\core_text::substr(trim($content), 0, \local_dttutor\proxy\request_guard::MAX_MESSAGE_LENGTH));
    }

    /**
     * Get chat history for a session.
     *
     * @param string $sessionid Session ID.
     * @param int $limit Maximum number of messages to return (default: 20).
     * @param int $offset Number of messages to skip for pagination (default: 0).
     * @return array Response with messages array and pagination info.
     * @throws moodle_exception If the request fails.
     * @since Moodle 4.5
     */
    public function get_history(string $sessionid, int $limit = 20, int $offset = 0): array {
        $endpoint = '/chat/history?session_id=' . urlencode($sessionid) .
            '&limit=' . $limit .
            '&offset=' . $offset;
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Delete a chat session, and forget its handle only once the service confirmed it.
     *
     * The local handle goes either way, so the user, the course and the module stop pointing at
     * it. When the service did not confirm the deletion, the identifier is kept among the pending
     * deletions instead, and an ad hoc task asks again until it does: forgetting it would leave
     * the conversation in the service with nothing left to repeat the request.
     *
     * @param string $sessionid Session ID to delete.
     * @return array Response with deletion status.
     * @throws moodle_exception If deletion fails; the session is then pending deletion.
     * @since Moodle 4.5
     */
    public function delete_session(string $sessionid): array {
        try {
            $response = $this->delete_remote_session($sessionid);
        } catch (\Throwable $e) {
            pending_deletion::queue_session($sessionid);
            session_store::forget($sessionid);
            throw $e;
        }

        session_store::forget($sessionid);
        return $response;
    }

    /**
     * Ask the service to delete a session, without touching what Moodle holds about it.
     *
     * A session the service no longer has is reported as deleted: it expired on its own, which
     * is the outcome the deletion wanted.
     *
     * @param string $sessionid Session ID to delete.
     * @return array Response with deletion status.
     * @throws moodle_exception If the service did not confirm the deletion.
     */
    public function delete_remote_session(string $sessionid): array {
        try {
            return $this->client->request('DELETE', '/chat/session/' . rawurlencode($sessionid)) ?? [];
        } catch (\Throwable $e) {
            if (pending_deletion::is_already_gone($e)) {
                return ['deleted' => true, 'already_gone' => true];
            }
            throw $e;
        }
    }

    /**
     * Read the documents a course hands out, so their text can travel with the course knowledge.
     *
     * Nothing in Moodle reads a PDF. The documents are sent once, addressed by their content
     * hash, and the caller keeps what comes back against that hash: a document is read once and
     * never once per question.
     *
     * @param array $files Entries with filename, mimetype, sha1 and content_base64.
     * @param int $charsperfile Characters one document may contribute.
     * @return array Response with the text of each document read, and the reason for each one
     *               that was not.
     * @throws moodle_exception If the request fails.
     * @since Moodle 4.5
     */
    public function extract_material(array $files, int $charsperfile): array {
        if ($files === []) {
            return ['extracted' => [], 'skipped' => []];
        }

        return $this->client->request('POST', '/chat/material/extract', [
            'files' => array_values($files),
            'max_chars_per_file' => $charsperfile,
            'max_chars_total' => $charsperfile * count($files),
        ]) ?? ['extracted' => [], 'skipped' => []];
    }

    /**
     * Delete every conversation of a user, or of a whole course, in the AI service.
     *
     * The service is asked by user and course instead of by session handle, so the
     * conversations Moodle never registered are deleted too. Those exist on sites that used
     * the tutor before the session handles began to be stored, and no list of identifiers
     * held here can name them.
     *
     * @param int|null $userid User whose conversations are deleted, or null for every user of
     *                         the course.
     * @param int|null $courseid Course the deletion is limited to, or null for every course of
     *                           the user.
     * @return array Response with the number of conversations and messages deleted.
     * @throws \coding_exception If neither a user nor a course is given.
     * @throws moodle_exception If the request fails.
     * @since Moodle 4.5
     */
    public function purge_conversations(?int $userid, ?int $courseid = null): array {
        if ($userid === null && $courseid === null) {
            // Without a scope the service would be asked to erase the whole site.
            throw new \coding_exception('A conversation purge needs a user, a course, or both.');
        }

        $payload = [
            'all_users' => $userid === null,
            // Sent even when every user is meant: the client fills an absent field with the
            // current user, and the service would then narrow the deletion to that person.
            'userid' => (string)($userid ?? 0),
        ];
        if ($courseid !== null) {
            $payload['course_id'] = (string)$courseid;
        }

        return $this->client->request('POST', '/chat/sessions/purge', $payload) ?? [];
    }

    /**
     * Drop the cached session handle of a user in a course (or module).
     *
     * The V2 key is cleared so that the next request opens a fresh remote session instead of
     * reusing a handle that was deleted on purpose.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course module ID, if any.
     */
    public function forget_cached_session(int $courseid, int $userid, ?int $cmid = null): void {
        if ($this->cache === null) {
            return;
        }
        $this->cache->delete(self::get_session_v2_cache_key($courseid, $userid, $cmid));
    }

    /**
     * Cache a freshly created session and record its handle durably.
     *
     * @param string $cachekey Cache key for this course/user/module.
     * @param array $response Decoded response of the session creation call.
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course module ID, if any.
     * @return array The response enriched with the local timestamps.
     */
    private function remember_session(string $cachekey, array $response, int $courseid, int $userid, ?int $cmid): array {
        $response['created_at'] = time();
        $response['backend_validated_at'] = time();

        if ($this->cache !== null) {
            $this->cache->set($cachekey, $response);
        }

        if (!empty($response['session_id'])) {
            session_store::upsert($userid, $courseid, $cmid, (string)$response['session_id']);
        }

        return $response;
    }

    /**
     * Check if a cached session is still valid.
     *
     * @param array $session Cached session data.
     * @return bool True if session is still valid.
     */
    private function is_session_valid(array $session): bool {
        if (!isset($session['created_at']) || !isset($session['session_ttl_seconds'])) {
            return false;
        }

        $elapsed = time() - $session['created_at'];
        $ttl = $session['session_ttl_seconds'];

        return $elapsed < ($ttl - 3600); // 1 hour margin.
    }

    /**
     * Determine if cached session should be revalidated against backend.
     *
     * @param array $session Cached session payload.
     * @return bool True when validation is needed.
     */
    private function should_validate_cached_session(array $session): bool {
        if (empty($session['backend_validated_at'])) {
            return true;
        }

        $lastvalidated = (int)$session['backend_validated_at'];
        return (time() - $lastvalidated) >= self::SESSION_BACKEND_VALIDATION_INTERVAL;
    }

    /**
     * Build the Moodle cache key for V2 chat sessions.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course module ID, if any.
     * @return string Cache key.
     */
    private static function get_session_v2_cache_key(int $courseid, int $userid, ?int $cmid = null): string {
        $cachekey = "session_v2_{$courseid}_{$userid}";
        if ($cmid !== null) {
            $cachekey .= "_{$cmid}";
        }

        return $cachekey;
    }

    /**
     * Check whether a cached session still exists on backend storage.
     *
     * @param string $sessionid Session ID.
     * @return bool True when backend recognizes the session.
     */
    private function is_backend_session_alive(string $sessionid): bool {
        try {
            // Lightweight existence check.
            $this->get_history($sessionid, 1, 0);
            return true;
        } catch (\Throwable $e) {
            debugging('Cached Tutor-IA session is stale: ' . get_class($e), DEBUG_DEVELOPER);
            return false;
        }
    }
}
