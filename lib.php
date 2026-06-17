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
 * Library functions for Tutor-IA plugin
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extend course navigation with AI Tutor Management link
 *
 * @param navigation_node $parentnode The parent node
 * @param stdClass $course The course object
 * @param context_course $context The course context
 * @return void
 */
function local_dttutor_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    // Only show for teachers/managers.
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    // Only if plugin is enabled.
    if (!get_config('local_dttutor', 'enabled')) {
        return;
    }

    $url = new moodle_url('/local/dttutor/manage.php', ['id' => $course->id]);
    $node = navigation_node::create(
        get_string('manage_tutor', 'local_dttutor'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'dttutormanage',
        new pix_icon('i/settings', '')
    );

    // Add to course secondary navigation (More menu).
    $parentnode->add_node($node);
}

/**
 * Serves the files from the local_dttutor file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function local_dttutor_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    // Handle custom avatar (system context).
    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'customavatar') {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = !$args ? '/' : '/' . implode('/', $args) . '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_dttutor', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 86400, 0, false, $options);
        return;
    }

    // Handle course materials (course context).
    if ($context->contextlevel == CONTEXT_COURSE && $filearea === 'course_materials') {
        // Verify user has access to course.
        require_login($course);
        require_capability('local/dttutor:use', $context);

        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = !$args ? '/' : '/' . implode('/', $args) . '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_dttutor', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 86400, 0, false, $options);
        return;
    }

    return false;
}

/**
 * Resolve the fully qualified class name for a web service function.
 *
 * @param  string      $wsname The WS function name (e.g. core_course_get_courses).
 * @return string|null         The fully qualified class name, or null.
 */
function local_dttutor_resolve_ws_classname(string $wsname): ?string {
    global $DB, $CFG;
    $record = $DB->get_record('external_functions', ['name' => $wsname], 'classname, classpath, component');
    if (!$record) {
        return null;
    }
    // Try class autoloading first (namespaced classes in Moodle 4.x+).
    if (class_exists($record->classname)) {
        return $record->classname;
    }
    // Fallback: load externallib.php via classpath.
    if (!empty($record->classpath)) {
        $path = "{$CFG->dirroot}/{$record->classpath}";
    } else {
        $path = core_component::get_component_directory($record->component) . '/externallib.php';
    }
    if (file_exists($path)) {
        require_once($path);
    }
    return class_exists($record->classname) ? $record->classname : null;
}

/**
 * Determine the current user's role for tool permission checks.
 *
 * @return string 'admin', 'manager', 'teacher', or 'student'.
 */
function local_dttutor_get_user_role(): string {
    global $USER, $COURSE;

    if (is_siteadmin()) {
        return 'admin';
    }

    $sysctx = context_system::instance();

    // Manager: has moodle/user:update at system level.
    if (has_capability('moodle/user:update', $sysctx)) {
        return 'manager';
    }

    // Teacher: can manage activities in the current course.
    if (!empty($COURSE->id) && $COURSE->id > 1) {
        $ctx = context_course::instance($COURSE->id);
        if (has_capability('moodle/course:manageactivities', $ctx)) {
            return 'teacher';
        }
        return 'student';
    }

    return 'student';
}

/**
 * Determine a user's role WITHOUT depending on $COURSE or $PAGE globals.
 *
 * @param  int    $userid The user ID to check.
 * @return string         'admin', 'manager', 'teacher', or 'student'.
 */
function local_dttutor_get_user_role_for_user(int $userid): string {
    // Check if user is site admin.
    $admins = get_admins();
    foreach ($admins as $admin) {
        if ((int) $admin->id === $userid) {
            return 'admin';
        }
    }

    $sysctx = context_system::instance();

    // Manager: has moodle/user:update at system level.
    if (has_capability('moodle/user:update', $sysctx, $userid)) {
        return 'manager';
    }

    // Teacher: has moodle/course:manageactivities in ANY enrolled course.
    $courses = enrol_get_users_courses($userid, true, ['id']);
    foreach ($courses as $c) {
        $ctx = context_course::instance($c->id);
        if (has_capability('moodle/course:manageactivities', $ctx, $userid)) {
            return 'teacher';
        }
    }

    return 'student';
}

/**
 * Get tool definitions for a given role in OpenAI function-calling format.
 *
 * Only 3 base tools for dttutor (no SQL, no admin, no document_read):
 *   - call_webservice
 *   - ws_search
 *   - ws_describe
 *
 * @param  string $role User role: admin, manager, teacher, student.
 * @return array        Array of OpenAI-style tool definitions.
 */
function local_dttutor_get_tool_definitions(string $role): array {
    $tools = [];

    // Tool ws_search — available to all roles.
    $tools[] = [
        'type' => 'function',
        'function' => [
            'name'        => 'ws_search',
            'description' => get_string('tool_ws_search_desc', 'local_dttutor'),
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'query' => [
                        'type'        => 'string',
                        'description' => get_string('tool_ws_search_query', 'local_dttutor'),
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => get_string('tool_ws_search_limit', 'local_dttutor'),
                    ],
                ],
                'required' => ['query'],
            ],
        ],
    ];

    // Tool ws_describe — available to all roles.
    $tools[] = [
        'type' => 'function',
        'function' => [
            'name'        => 'ws_describe',
            'description' => get_string('tool_ws_describe_desc', 'local_dttutor'),
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'wsname' => [
                        'type'        => 'string',
                        'description' => get_string('tool_ws_describe_wsname', 'local_dttutor'),
                    ],
                ],
                'required' => ['wsname'],
            ],
        ],
    ];

    // Tool call_webservice — all roles (permissions enforced by Moodle WS itself).
    $tools[] = [
        'type' => 'function',
        'function' => [
            'name'        => 'call_webservice',
            'description' => get_string('tool_call_webservice_desc', 'local_dttutor'),
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'function' => [
                        'type'        => 'string',
                        'description' => get_string('tool_call_webservice_function', 'local_dttutor'),
                    ],
                    'params' => [
                        'type'        => 'object',
                        'description' => get_string('tool_call_webservice_params', 'local_dttutor'),
                    ],
                ],
                'required' => ['function'],
            ],
        ],
    ];

    return $tools;
}

/**
 * Simple structured logging for the AI proxy.
 *
 * Logs to Moodle's error log with a [local_dttutor] prefix.
 *
 * @param string $event The event name.
 * @param array  $data  Structured data to log.
 */
function local_dttutor_log(string $event, array $data = []): void {
    $line = '[local_dttutor] ' . $event . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    debugging($line, DEBUG_DEVELOPER);
}

/**
 * Get the page context for the current request (used by the system message builder).
 *
 * @return array Page context data.
 */
function local_dttutor_get_page_context(): array {
    global $PAGE, $COURSE, $USER;

    $url = '';
    try {
        $url = $PAGE->url->out(false);
    } catch (\Exception $e) {
        $url = $_SERVER['REQUEST_URI'] ?? '';
    }

    $pagetype = $PAGE->pagetype ?? '';
    $context  = [
        'page_url'      => $url,
        'page_type'     => $pagetype,
        'page_title'    => $PAGE->title ?? '',
        'user_id'       => $USER->id ?? 0,
        'user_fullname' => fullname($USER),
        'user_role'     => local_dttutor_get_user_role(),
        'course_id'     => 0,
        'course_name'   => '',
        'activity_id'   => 0,
        'activity_type' => '',
        'location'      => 'unknown',
    ];

    if (!empty($COURSE->id) && $COURSE->id > 1) {
        $context['course_id']   = $COURSE->id;
        $context['course_name'] = $COURSE->fullname ?? '';
        $context['location']    = 'course';
    }

    if (preg_match('/[?&]id=(\d+)/', $url, $m) && strpos($url, '/mod/') !== false) {
        $context['activity_id'] = (int) $m[1];
        $context['location']    = 'activity';
        if (preg_match('/^mod-(\w+)-/', $pagetype, $mt)) {
            $context['activity_type'] = $mt[1];
        }

        // Resolve instance ID from cmid so the AI doesn't have to guess it.
        $cminfo = get_coursemodule_from_id('', $context['activity_id']);
        if ($cminfo) {
            $context['activity_instance'] = (int)$cminfo->instance;
            if (empty($context['activity_type'])) {
                $context['activity_type'] = $cminfo->modname;
            }
        }
    }

    $locationmap = [
        '/admin/'   => 'admin',
        '/my/'      => 'dashboard',
        '/grade/'   => 'grades',
        '/message/' => 'messages',
        '/user/'    => 'profile',
    ];
    foreach ($locationmap as $path => $loc) {
        if (strpos($url, $path) !== false) {
            $context['location'] = $loc;
            break;
        }
    }
    if ($pagetype === 'my-index') {
        $context['location'] = 'dashboard';
    }

    return $context;
}
