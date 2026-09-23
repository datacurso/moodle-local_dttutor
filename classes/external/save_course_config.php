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
 * External service to save course configuration
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_dttutor\course_config;

/**
 * External service to save course configuration
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_course_config extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 4.5
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'enabled' => new external_value(PARAM_BOOL, 'Enable tutor for this course', VALUE_DEFAULT, true),
            'tutorname' => new external_value(
                PARAM_TEXT,
                'Name of the tutor in this course, empty to follow the site. Omitted leaves it as it is.',
                VALUE_DEFAULT,
                null
            ),
            'welcomemessage' => new external_value(
                PARAM_TEXT,
                'Welcome message in this course, empty to follow the site. Omitted leaves it as it is.',
                VALUE_DEFAULT,
                null
            ),
        ]);
    }

    /**
     * Save course configuration
     *
     * @param int $courseid Course ID
     * @param bool $enabled Enable tutor for course
     * @param string|null $tutorname Name of the tutor in this course, or null to leave it as it is
     * @param string|null $welcomemessage Welcome message in this course, or null to leave it as it is
     * @return array Save status
     * @since Moodle 4.5
     */
    public static function execute(
        int $courseid,
        bool $enabled = true,
        ?string $tutorname = null,
        ?string $welcomemessage = null
    ): array {
        // 1. Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'enabled' => $enabled,
            'tutorname' => $tutorname,
            'welcomemessage' => $welcomemessage,
        ]);

        // 2. Check authentication.
        require_login();

        // 3. Verify plugin is enabled.
        if (!get_config('local_dttutor', 'enabled')) {
            throw new \moodle_exception('error_tutor_disabled_site', 'local_dttutor');
        }

        // 4. Validate course context and check capabilities.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // 5. Update configuration.
        $data = [
            'indexing_enabled' => $params['enabled'] ? 1 : 0,
        ];

        // Only what the request carries is written. A caller that leaves a field out keeps what the
        // course already had: the toggle used to send an empty prompt with every change and wiped
        // it, which is why that feature was removed in 2.0.9, and this is the guard against it.
        foreach (course_config::OVERRIDABLE as $name) {
            if ($params[$name] !== null) {
                $data[$name] = trim((string)$params[$name]);
            }
        }

        $success = course_config::update($params['courseid'], $data);

        return [
            'success' => $success,
            'message' => $success ? get_string('changessaved') : get_string('error'),
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     * @since Moodle 4.5
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
