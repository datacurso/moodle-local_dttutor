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
 * External API for Tutor-IA chat history retrieval
 *
 * @package    local_dttutor
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\external;

use external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use local_dttutor\httpclient\tutoria_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
/**
 * Class get_chat_history
 *
 * Retrieves chat history for a Tutor-IA session.
 *
 * @package    local_dttutor
 * @category   external
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_chat_history extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 4.5
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'Course module ID for activity context', VALUE_DEFAULT, null),
            'limit' => new external_value(PARAM_INT, 'Maximum messages to return', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Messages to skip for pagination', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get chat history for a session.
     *
     * @param int $courseid Course ID.
     * @param int|null $cmid Course module ID for activity context.
     * @param int $limit Maximum number of messages to return.
     * @param int $offset Number of messages to skip for pagination.
     * @return array History data with messages and pagination info.
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     * @since Moodle 4.5
     */
    public static function execute($courseid, $cmid = null, $limit = 20, $offset = 0): array {
        global $USER;
        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'limit' => $limit,
            'offset' => $offset,
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

        // Verify user has permission to use Tutor-IA.
        require_capability('local/dttutor:use', $context);

        // Sanitize pagination parameters.
        if ($params['limit'] < 1) {
            $params['limit'] = 20;
        }
        if ($params['limit'] > 100) {
            $params['limit'] = 100;
        }

        if ($params['offset'] < 0) {
            $params['offset'] = 0;
        }

        $tutoriaapi = new tutoria_api();

        $cmid = $params['cmid'] ?? null;
        $session = $tutoriaapi->start_session_v2($params['courseid'], $USER->id, $cmid);

        $response = $tutoriaapi->get_history(
            $session['session_id'],
            $params['limit'],
            $params['offset']
        );

        return $response;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     * @since Moodle 4.5
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'session_id' => new external_value(PARAM_TEXT, 'Session ID'),
            'total_messages' => new external_value(PARAM_INT, 'Total number of messages in session'),
            'messages' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_TEXT, 'Message ID'),
                    'role' => new external_value(PARAM_TEXT, 'Message role (user or assistant)'),
                    'content' => new external_value(PARAM_RAW, 'Message content'),
                    'timestamp' => new external_value(PARAM_INT, 'Unix timestamp'),
                ]),
                'Array of messages',
                VALUE_OPTIONAL
            ),
            'pagination' => new external_single_structure([
                'limit' => new external_value(PARAM_INT, 'Messages per page'),
                'offset' => new external_value(PARAM_INT, 'Current offset'),
                'has_more' => new external_value(PARAM_BOOL, 'Whether more messages exist'),
            ]),
        ]);
    }
}
