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
 * Post-installation for local_dttutor.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create the service bot user used by the AI tutor.
 *
 * Course-scoped permissions (teacher role) are assigned dynamically
 * when the tutor is activated for a course via manage.php.
 */
function xmldb_local_dttutor_install() {
    local_dttutor_create_service_bot_user();
}

/**
 * Create or reuse the service bot user and store its ID in plugin config.
 */
function local_dttutor_create_service_bot_user(): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/user/lib.php');

    $username = 'tutoriabot_datacurso';
    $existing = $DB->get_record('user', ['username' => $username]);
    if ($existing) {
        set_config('serviceuserid', (int)$existing->id, 'local_dttutor');
        return;
    }

    $user = (object)[
        'username' => $username,
        'password' => bin2hex(random_bytes(24)),
        'firstname' => get_string('servicebot_firstname', 'local_dttutor'),
        'lastname' => get_string('servicebot_lastname', 'local_dttutor'),
        'email' => 'tutoria@datacurso.com',
        'auth' => 'manual',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $userid = user_create_user($user, false, false);
    set_config('serviceuserid', $userid, 'local_dttutor');
}
