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
 * External API for deleting Tutor-IA chat sessions
 *
 * @package    local_dttutor
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\proxy\request_guard;
use local_dttutor\session_store;

/**
 * Class delete_chat_session
 *
 * Deletes the stored Tutor-IA chat session of the current user, if any.
 *
 * No UI in this plugin calls it (the chat resets sessions through chatproxy.php); it is kept
 * as a public AJAX API so that integrators can delete a user's session on demand.
 *
 * @package    local_dttutor
 * @category   external
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_chat_session extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 4.5
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'Course Module ID', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Delete a Tutor-IA chat session for the current user.
     *
     * Only a session already recorded in local_dttutor_session is deleted: no remote
     * session is ever opened just to close it.
     *
     * @param int $courseid Course ID where the session was created.
     * @param int|null $cmid Course Module ID (optional).
     * @return array Deletion status.
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     * @since Moodle 4.5
     */
    public static function execute($courseid, $cmid = null): array {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
        ]);

        // Check if user is logged in.
        require_login();

        // Verify plugin is enabled.
        if (!get_config('local_dttutor', 'enabled')) {
            throw new \moodle_exception('error_api_not_configured', 'local_dttutor');
        }

        // Validate course context and permissions.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Verify user has permission to use Tutor-IA and that the tutor is enabled for this course.
        require_capability('local/dttutor:use', $context);
        request_guard::assert_course_enabled((int)$params['courseid']);

        $cmid = null;
        if (!empty($params['cmid'])) {
            $cmid = (int)request_guard::resolve_cm((int)$params['cmid'], (int)$params['courseid'], (int)$USER->id)->id;
        }
        $remotesessionid = session_store::get_remote_session_id((int)$USER->id, (int)$params['courseid'], $cmid);
        if ($remotesessionid === null) {
            return ['deleted' => false];
        }

        try {
            $tutoriaapi = \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            // The provider client cannot be built (unconfigured or unreachable). Drop the stored
            // handle anyway so a later deletion does not chase a dead pointer; a stale cache entry
            // is detected by the liveness probe the next time a session is started.
            \local_dttutor_log('SESSION_DELETE_REMOTE_UNAVAILABLE', ['exception' => get_class($e)], true);
            session_store::forget($remotesessionid);
            return ['deleted' => false];
        }
        $tutoriaapi->forget_cached_session((int)$params['courseid'], (int)$USER->id, $cmid);

        try {
            // The stored handle is dropped by delete_session() even when the remote call fails.
            $result = $tutoriaapi->delete_session($remotesessionid);
            return ['deleted' => (bool)($result['deleted'] ?? false)];
        } catch (\Throwable $e) {
            // The session may already have expired remotely; never fail the client for that.
            debugging('Failed to delete Tutor-IA session: ' . get_class($e), DEBUG_DEVELOPER);
            return ['deleted' => false];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     * @since Moodle 4.5
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Session deleted successfully'),
        ]);
    }
}
