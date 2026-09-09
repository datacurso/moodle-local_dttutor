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
 * External web services for the Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_dttutor_get_chat_history' => [
        'classname'   => 'local_dttutor\external\get_chat_history',
        'methodname'  => 'execute',
        'description' => 'Get chat history for a Tutor-AI session',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/dttutor:use',
    ],
    'local_dttutor_delete_chat_session' => [
        'classname'   => 'local_dttutor\external\delete_chat_session',
        'methodname'  => 'execute',
        'description' => 'Delete a Tutor-AI chat session',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/dttutor:use',
    ],
    'local_dttutor_save_course_config' => [
        'classname'   => 'local_dttutor\external\save_course_config',
        'methodname'  => 'execute',
        'description' => 'Save course configuration',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'moodle/course:update',
    ],
];
