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

use aiprovider_datacurso\httpclient\ai_services_api;
use cache;
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

    /** @var ai_services_api AI services API client instance. */
    private ai_services_api $aiservice;

    /** @var cache|null Cache instance for storing sessions. */
    private ?cache $cache;

    /**
     * Constructor to initialize the Tutor-IA API client.
     *
     * @since Moodle 4.5
     */
    public function __construct() {
        $this->aiservice = new ai_services_api();

        try {
            $this->cache = cache::make('local_dttutor', 'sessions');
        } catch (\Exception $e) {
            debugging('Cache initialization failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $this->cache = null;
        }
    }

    /**
     * Start a new chat session or retrieve an existing one from cache.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param int|null $cmid Course Module ID (optional, for module context).
     * @return array Session data including session_id, ready status, and TTL.
     * @throws moodle_exception If the session creation fails.
     * @since Moodle 4.5
     */
    public function start_session(int $courseid, int $userid, ?int $cmid = null): array {
        $cachekey = "session_{$courseid}_{$userid}";
        if ($cmid !== null) {
            $cachekey .= "_{$cmid}";
        }
        $cached = null;

        if ($this->cache !== null) {
            $cached = $this->cache->get($cachekey);
        }

        if ($cached && $this->is_session_valid($cached)) {
            // Validate cached session against backend only at intervals.
            // This avoids an extra backend round-trip on every cache hit while still
            // recovering from stale sessions after backend/Redis restarts.
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

            // Cached session is stale; clear it and create a new one.
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

        $response = $this->aiservice->request('POST', '/chat/start', $requestdata);

        $response['created_at'] = time();
        $response['backend_validated_at'] = time();

        if ($this->cache !== null) {
            $this->cache->set($cachekey, $response);
        }

        return $response;
    }

    /**
     * Send a message to an existing chat session.
     *
     * @param string $sessionid Session ID.
     * @param string $content Message content.
     * @param array $meta Optional metadata (includes cmid if in module context).
     * @return array Response indicating if message was enqueued.
     * @throws moodle_exception If sending fails.
     * @since Moodle 4.5
     */
    public function send_message(string $sessionid, string $content, array $meta = []): array {
        global $USER;
        return $this->aiservice->request('POST', '/chat/message', [
            'session_id' => $sessionid,
            'content' => $content,
            'meta' => $meta,
            'user_id' => $USER->id,
        ]);
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

        $response = $this->aiservice->request('POST', '/chat/start/v2', $requestdata);

        $response['created_at'] = time();
        $response['backend_validated_at'] = time();

        if ($this->cache !== null) {
            $this->cache->set($cachekey, $response);
        }

        return $response;
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
                    debugging('Failed to delete stale Tutor-IA session: ' . $e->getMessage(), DEBUG_DEVELOPER);
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
        return $this->aiservice->request('POST', '/chat/messages/append/v2', $payload);
    }

    /**
     * Build the SSE stream URL for a chat session.
     *
     * @param string $sessionid Session ID.
     * @return string Full stream URL with session parameter.
     * @since Moodle 4.5
     */
    public function get_stream_url(string $sessionid): string {
        return $this->aiservice->get_streaming_url_for_session($sessionid);
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
        return $this->aiservice->request('GET', $endpoint);
    }

    /**
     * Delete a chat session.
     *
     * @param string $sessionid Session ID to delete.
     * @return array Response with deletion status.
     * @throws moodle_exception If deletion fails.
     * @since Moodle 4.5
     */
    public function delete_session(string $sessionid): array {
        return $this->aiservice->request('DELETE', '/chat/session/' . $sessionid);
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
            debugging('Cached Tutor-IA session is stale: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
