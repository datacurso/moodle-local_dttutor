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

/**
 * Execute local_dttutor upgrade from the given old version.
 *
 * Note: the steps released between 2026012201 and 2026060105 created a
 * service bot user and granted system-level capabilities (local/dttutor:use
 * to the user archetype, moodle/course:view to the teacher archetypes). Those
 * steps were removed and their effects are reverted by step 2026090800.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_dttutor_upgrade($oldversion) {
    global $DB;

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

    if ($oldversion < 2026090800) {
        // The web service bridge and its service bot user were removed. Retire the bot
        // (unenrol everywhere, suspend, forget its id) without deleting it, so historical
        // log entries that reference it stay intact.
        \local_dttutor\upgrade\service_bot::retire();

        // Revoke the system-level grants made by earlier versions: local/dttutor:use for
        // the user archetype and moodle/course:view for the teacher archetypes. The tutor
        // is now authorised per course, where enrolled roles receive the capability.
        \local_dttutor\upgrade\roles::revoke_system_capabilities();

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026090800, 'local', 'dttutor');
    }

    if ($oldversion < 2026090801) {
        // Define table local_dttutor_session to be created.
        $table = new xmldb_table('local_dttutor_session');

        // Adding fields to table local_dttutor_session.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('remotesessionid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_dttutor_session.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Adding indexes to table local_dttutor_session.
        $table->add_index('userid-courseid-cmid', XMLDB_INDEX_UNIQUE, ['userid', 'courseid', 'cmid']);

        // Conditionally launch create table for local_dttutor_session.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026090801, 'local', 'dttutor');
    }

    if ($oldversion < 2026090900) {
        // Drop the columns of local_dttutor_course_config that no code reads or writes: the
        // indexing bookkeeping of the removed materials feature and the per-course custom
        // prompt, which no UI exposed and which the enablement toggle silently wiped.
        $table = new xmldb_table('local_dttutor_course_config');

        $index = new xmldb_index('indexing_status', XMLDB_INDEX_NOTUNIQUE, ['indexing_status']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        foreach (['last_indexed_at', 'indexing_status', 'indexing_task_id', 'indexing_error', 'custom_prompt'] as $name) {
            $field = new xmldb_field($name);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Dttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2026090900, 'local', 'dttutor');
    }

    return true;
}
