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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

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
            'custom_prompt' => new external_value(PARAM_TEXT, 'Custom prompt', VALUE_DEFAULT, ''),
            'enabled' => new external_value(PARAM_BOOL, 'Enable tutor for this course', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Save course configuration
     *
     * @param int $courseid Course ID
     * @param string $customprompt Custom prompt
     * @param bool $enabled Enable tutor for course
     * @return array Save status
     * @since Moodle 4.5
     */
    public static function execute(int $courseid, string $customprompt = '', bool $enabled = true): array {
        global $DB;

        // 1. Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'custom_prompt' => $customprompt,
            'enabled' => $enabled,
        ]);

        // 2. Check authentication.
        require_login();

        // 3. Verify plugin is enabled.
        if (!get_config('local_dttutor', 'enabled')) {
            throw new \moodle_exception('error_api_not_configured', 'local_dttutor');
        }

        // 4. Validate course context and check capabilities.
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // 5. Update configuration.
        $data = [
            'custom_prompt' => !empty($params['custom_prompt']) ? $params['custom_prompt'] : null,
            'indexing_enabled' => $params['enabled'] ? 1 : 0,
        ];

        $success = course_config::update($params['courseid'], $data);

        // 6. When enabling the tutor, enrol the service bot as teacher in the course.
        // Uses enrol_manual API to create a proper user_enrolments record + role assignment.
        $enrolmessage = '';
        if ($success && $params['enabled']) {
            $serviceuserid = (int)get_config('local_dttutor', 'serviceuserid');
            if ($serviceuserid > 0) {
                $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
                if (!$teacherrole) {
                    $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
                }
                if ($teacherrole) {
                    $coursecontext = \context_course::instance($params['courseid']);

                    // Check if already enrolled (any method).
                    if (is_enrolled($coursecontext, $serviceuserid)) {
                        $enrolmessage = 'already_enrolled';
                    } else {
                        $plugin = enrol_get_plugin('manual');
                        if (!$plugin || !enrol_is_enabled('manual')) {
                            $enrolmessage = 'manual_not_available';
                        } else {
                            // Find existing manual enrolment instance.
                            $instances = $DB->get_records('enrol', [
                                'enrol' => 'manual',
                                'courseid' => $params['courseid'],
                            ]);

                            if (empty($instances)) {
                                // Create a new manual enrolment instance for this course.
                                $course = get_course($params['courseid']);
                                $instanceid = $plugin->add_instance($course, [
                                    'status' => ENROL_INSTANCE_ENABLED,
                                    'roleid' => $teacherrole->id,
                                ]);
                                if ($instanceid) {
                                    $instances = $DB->get_records('enrol', [
                                        'enrol' => 'manual',
                                        'courseid' => $params['courseid'],
                                    ]);
                                }
                            }

                            $instance = reset($instances);

                            if ($instance) {
                                if ($instance->status != ENROL_INSTANCE_ENABLED) {
                                    $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
                                }

                                // Enrol via the manual plugin (creates user_enrolments + role_assign).
                                $plugin->enrol_user($instance, $serviceuserid, $teacherrole->id, time());

                                // Verify enrolment was created.
                                $nowenrolled = $DB->record_exists('user_enrolments', [
                                    'enrolid' => $instance->id,
                                    'userid' => $serviceuserid,
                                ]);

                                if ($nowenrolled) {
                                    $roleassigned = $DB->record_exists('role_assignments', [
                                        'roleid' => $teacherrole->id,
                                        'userid' => $serviceuserid,
                                        'contextid' => $coursecontext->id,
                                    ]);
                                    $enrolmessage = $roleassigned ? 'enrolled_ok' : 'enrolled_no_role';
                                } else {
                                    $enrolmessage = 'enrol_failed';
                                }
                            } else {
                                $enrolmessage = 'create_instance_failed';
                            }
                        }
                    }
                } else {
                    $enrolmessage = 'teacher_role_not_found';
                }
            } else {
                $enrolmessage = 'no_serviceuserid';
            }
        }

        return [
            'success' => $success,
            'message' => $success ? get_string('changessaved') : get_string('error'),
            'enrol_status' => $enrolmessage,
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
            'enrol_status' => new external_value(PARAM_TEXT, 'Enrolment diagnostic message', VALUE_OPTIONAL, ''),
        ]);
    }
}
