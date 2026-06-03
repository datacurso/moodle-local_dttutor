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
 * AI Tutor course management page
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use local_dttutor\course_config;

$courseid = required_param('id', PARAM_INT);

require_login($courseid);
$course = get_course($courseid);
$context = context_course::instance($courseid);

// Check capability.
require_capability('moodle/course:update', $context);

// Check plugin is enabled.
if (!get_config('local_dttutor', 'enabled')) {
    throw new moodle_exception('error_api_not_configured', 'local_dttutor');
}

// Set up page.
$PAGE->set_url('/local/dttutor/manage.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manage_tutor', 'local_dttutor'));
$PAGE->set_heading($course->fullname);

// Get course configuration.
$config = course_config::get_by_course($courseid);

// Course materials module for tutor toggle functionality.
$PAGE->requires->js_call_amd('local_dttutor/course_materials', 'init', [$courseid]);

// Prepare template context.
$templatecontext = [
    'courseid' => $courseid,
    'tutor_enabled' => (bool)$config->indexing_enabled,
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_tutor', 'local_dttutor'));
echo $OUTPUT->render_from_template('local_dttutor/manage_course', $templatecontext);
echo $OUTPUT->footer();
