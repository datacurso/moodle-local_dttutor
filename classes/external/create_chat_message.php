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
 * External API for Tutor-IA chat message creation
 *
 * @package    local_dttutor
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\external;

use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_dttutor\httpclient\tutoria_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
/**
 * Class create_chat_message
 *
 * Creates a chat message and returns streaming URL for Tutor-IA responses.
 *
 * @package    local_dttutor
 * @category   external
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_chat_message extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 4.5
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'message' => new external_value(PARAM_RAW, 'User message', VALUE_REQUIRED),
            'meta' => new external_value(PARAM_RAW, 'Optional metadata (JSON)', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Create chat message and initialize Tutor-IA session.
     *
     * @param int $courseid Course ID.
     * @param string $message User message text.
     * @param string $meta Optional metadata as JSON string.
     * @return array Session data with streaming URL.
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @since Moodle 4.5
     */
    public static function execute($courseid, $message, $meta = '{}'): array {
        global $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'message' => $message,
            'meta' => $meta,
        ]);

        // Check if user is logged in.
        require_login();

        if (!get_config('local_dttutor', 'enabled')) {
            throw new \moodle_exception('error_api_not_configured', 'local_dttutor');
        }

        // Validate course context and permissions.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Verify user has permission to use Tutor-IA.
        require_capability('local/dttutor:use', $context);

        $trimmedmessage = trim($params['message']);
        if (empty($trimmedmessage)) {
            throw new \moodle_exception('error_empty_message', 'local_dttutor');
        }
        if ($trimmedmessage === '.') {
            throw new \moodle_exception('error_invalid_message', 'local_dttutor');
        }

        $tutoriaapi = new tutoria_api();

        $metaarray = json_decode($params['meta'], true);
        if ($metaarray === null) {
            $metaarray = [];
        }

        // Validate total metadata size (100KB limit).
        $metasize = strlen($params['meta']);
        if ($metasize > 102400) {
            throw new \moodle_exception(
                'error_metadata_too_large',
                'local_dttutor',
                '',
                null,
                sprintf('Metadata size: %d bytes exceeds 100KB limit', $metasize)
            );
        }

        // Validate and sanitize selected_text if present.
        if (isset($metaarray['selected_text'])) {
            // Ensure it's a string (not object or array).
            if (!is_string($metaarray['selected_text'])) {
                debugging('selected_text must be string, got: ' . gettype($metaarray['selected_text']), DEBUG_DEVELOPER);
                unset($metaarray['selected_text']);
            } else {
                // Trim whitespace.
                $metaarray['selected_text'] = trim($metaarray['selected_text']);

                // Check if empty after trim.
                if (empty($metaarray['selected_text'])) {
                    unset($metaarray['selected_text']);
                } else {
                    // Check length (50KB limit for selected text).
                    $textsize = strlen($metaarray['selected_text']);
                    if ($textsize > 51200) {
                        throw new \moodle_exception(
                            'error_selected_text_too_large',
                            'local_dttutor',
                            '',
                            null,
                            sprintf('Selected text size: %d bytes exceeds 50KB limit', $textsize)
                        );
                    }

                    // Sanitize for security (strip HTML, clean special chars).
                    // Note: Text is not displayed in UI, only sent to AI.
                    // This is defense-in-depth.
                    $metaarray['selected_text'] = clean_param(
                        $metaarray['selected_text'],
                        PARAM_TEXT
                    );

                    // Check again if empty after sanitization.
                    if (empty($metaarray['selected_text'])) {
                        debugging('selected_text is empty after sanitization', DEBUG_DEVELOPER);
                        unset($metaarray['selected_text']);
                    }
                }
            }
        }

        $metaarray['userid'] = (string) $USER->id;
        $metaarray['off_topic_detection_enabled'] = get_config('local_dttutor', 'off_topic_detection_enabled') ? 'true' : 'false';
        $metaarray['off_topic_strictness'] = get_config('local_dttutor', 'off_topic_strictness') ?: 'permissive';

        $customprompt = get_config('local_dttutor', 'custom_prompt');
        if (!empty($customprompt)) {
            $metaarray['custom_prompt'] = $customprompt;
        }

        // Extract cmid from metadata if present (when in module context).
        $cmid = null;
        if (isset($metaarray['cmid']) && is_numeric($metaarray['cmid'])) {
            $cmid = (int) $metaarray['cmid'];
            // Convert cmid to string for API compatibility.
            $metaarray['cmid'] = (string) $cmid;
        }

        $session = $tutoriaapi->start_session($params['courseid'], $USER->id, $cmid);

        if (!isset($session['ready']) || !$session['ready']) {
            throw new \moodle_exception('sessionnotready', 'local_dttutor');
        }

        // Ensure all meta values are strings for API compatibility.
        $metaarray = array_map(function ($value) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            return (string) $value;
        }, $metaarray);

        $tutoriaapi->send_message($session['session_id'], $params['message'], $metaarray);

        $streamurl = $tutoriaapi->get_stream_url($session['session_id']);

        return [
            'session_id' => $session['session_id'],
            'stream_url' => $streamurl,
            'expires_at' => time() + ($session['session_ttl_seconds'] ?? 604800),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     * @since Moodle 4.5
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'session_id' => new external_value(PARAM_TEXT, 'Tutor-IA session ID'),
            'stream_url' => new external_value(PARAM_URL, 'SSE streaming URL with authentication'),
            'expires_at' => new external_value(PARAM_INT, 'Session expiration timestamp'),
        ]);
    }
}
