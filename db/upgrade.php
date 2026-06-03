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
 * Plugin upgrade steps are defined here.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_dttutor upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_dttutor_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025120300) {
        // Define table local_dttutor_course_config to be created.
        $table = new xmldb_table('local_dttutor_course_config');

        // Adding fields to table local_dttutor_course_config.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('custom_prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('indexing_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('last_indexed_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('indexing_status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'not_indexed');
        $table->add_field('indexing_task_id', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('indexing_error', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_dttutor_course_config.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_dttutor_course_config.
        $table->add_index('courseid', XMLDB_INDEX_UNIQUE, ['courseid']);
        $table->add_index('indexing_status', XMLDB_INDEX_NOTUNIQUE, ['indexing_status']);

        // Conditionally launch create table for local_dttutor_course_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025120300, 'local', 'dttutor');
    }

    if ($oldversion < 2025120302) {
        // Change default value of indexing_enabled from 1 to 0 (disabled by default).
        $table = new xmldb_table('local_dttutor_course_config');
        $field = new xmldb_field('indexing_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Launch change of default for field indexing_enabled.
        $dbman->change_field_default($table, $field);

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025120302, 'local', 'dttutor');
    }

    if ($oldversion < 2026012201) {
        // Add local/dttutor:use capability to 'user' archetype roles.
        // This allows all authenticated users to use the tutor, not just enrolled students.
        $capability = 'local/dttutor:use';
        $archetypes = ['user'];

        foreach ($archetypes as $archetype) {
            // Get all roles with this archetype.
            $roles = get_archetype_roles($archetype);

            foreach ($roles as $role) {
                // Assign capability at system context level for course context.
                $systemcontext = context_system::instance();

                // Check if capability already exists for this role.
                $existingcap = $DB->get_record('role_capabilities', [
                    'roleid' => $role->id,
                    'capability' => $capability,
                    'contextid' => $systemcontext->id,
                ]);

                if (!$existingcap) {
                    // Assign capability with CAP_ALLOW permission.
                    assign_capability($capability, CAP_ALLOW, $role->id, $systemcontext->id);
                }
            }
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026012201, 'local', 'dttutor');
    }

    if ($oldversion < 2026060101) {
        require_once($CFG->dirroot . '/local/dttutor/db/install.php');
        local_dttutor_create_service_bot_user();

        // Remove any previous system-level role assignments for the bot user.
        // Permissions are now course-scoped via toggle in manage.php.
        $serviceuserid = (int)get_config('local_dttutor', 'serviceuserid');
        if ($serviceuserid > 0) {
            $systemcontext = context_system::instance();

            // Remove manager role if previously assigned.
            $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
            if ($managerrole) {
                role_unassign($managerrole->id, $serviceuserid, $systemcontext->id);
            }

            // Remove dttutorbot role if previously assigned.
            $botrole = $DB->get_record('role', ['shortname' => 'dttutorbot']);
            if ($botrole) {
                role_unassign($botrole->id, $serviceuserid, $systemcontext->id);
                // Also clean up all course-level assignments of this role for the bot.
                $DB->delete_records('role_assignments', [
                    'roleid' => $botrole->id,
                    'userid' => $serviceuserid,
                ]);
            }
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026060101, 'local', 'dttutor');
    }

    if ($oldversion < 2026060102) {
        // Ensure the bot user exists (in case it was missed by a previous step).
        require_once($CFG->dirroot . '/local/dttutor/db/install.php');
        local_dttutor_create_service_bot_user();

        // Remove any system-level role assignments for the bot user.
        // From this version on, permissions are course-scoped via teacher role_assign.
        $serviceuserid = (int)get_config('local_dttutor', 'serviceuserid');
        if ($serviceuserid > 0) {
            $systemcontext = context_system::instance();

            // Remove manager role if previously assigned.
            $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
            if ($managerrole) {
                role_unassign($managerrole->id, $serviceuserid, $systemcontext->id);
            }

            // Remove dttutorbot custom role if previously assigned.
            $botrole = $DB->get_record('role', ['shortname' => 'dttutorbot']);
            if ($botrole) {
                role_unassign($botrole->id, $serviceuserid, $systemcontext->id);
                // Also clean up any stale course-level assignments of this role.
                $DB->delete_records('role_assignments', [
                    'roleid' => $botrole->id,
                    'userid' => $serviceuserid,
                ]);
            }
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026060102, 'local', 'dttutor');
    }

    if ($oldversion < 2026060104) {
        // Assign moodle/course:view (View courses without participation) to the editingteacher
        // and teacher archetypes. Core WS functions like core_course_get_courses use
        // require_capability('moodle/course:view') to check access, but only the manager
        // archetype has this capability by default. The bot user needs it to read course
        // data via Web Services.
        $systemcontext = context_system::instance();

        $editingteacherroles = get_archetype_roles('editingteacher');
        foreach ($editingteacherroles as $role) {
            assign_capability('moodle/course:view', CAP_ALLOW, $role->id, $systemcontext->id);
        }

        $teacherroles = get_archetype_roles('teacher');
        foreach ($teacherroles as $role) {
            assign_capability('moodle/course:view', CAP_ALLOW, $role->id, $systemcontext->id);
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026060104, 'local', 'dttutor');
    }

    if ($oldversion < 2026060105) {
        // Update the service bot user's name and email to the new values.
        $serviceuserid = (int)get_config('local_dttutor', 'serviceuserid');
        if ($serviceuserid > 0) {
            $user = $DB->get_record('user', ['id' => $serviceuserid]);
            if ($user) {
                $user->firstname = get_string('servicebot_firstname', 'local_dttutor');
                $user->lastname = get_string('servicebot_lastname', 'local_dttutor');
                $user->email = 'tutoria@datacurso.com';
                $user->timemodified = time();
                $DB->update_record('user', $user);
            }
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026060105, 'local', 'dttutor');
    }

    return true;
}
