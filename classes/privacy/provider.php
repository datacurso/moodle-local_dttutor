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

namespace local_dttutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_dttutor\session_store;

/**
 * Privacy Subsystem implementation for local_dttutor.
 *
 * User data lives in course contexts: the per-course tutor enablement (last editor)
 * and the handles of the chat sessions each user opened with the Datacurso AI service.
 * The conversations themselves are held by that service; deleting a session here also
 * requests its remote deletion.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, plugin_provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_dttutor_course_config', [
            'timemodified' => 'privacy:metadata:local_dttutor_course_config:timemodified',
            'usermodified' => 'privacy:metadata:local_dttutor_course_config:usermodified',
        ], 'privacy:metadata:local_dttutor_course_config');

        $collection->add_database_table('local_dttutor_session', [
            'cmid' => 'privacy:metadata:local_dttutor_session:cmid',
            'courseid' => 'privacy:metadata:local_dttutor_session:courseid',
            'remotesessionid' => 'privacy:metadata:local_dttutor_session:remotesessionid',
            'timecreated' => 'privacy:metadata:local_dttutor_session:timecreated',
            'timemodified' => 'privacy:metadata:local_dttutor_session:timemodified',
            'userid' => 'privacy:metadata:local_dttutor_session:userid',
        ], 'privacy:metadata:local_dttutor_session');

        // Everything the chat proxy and the external functions send to the Datacurso AI service.
        $collection->add_external_location_link('datacurso_ai', [
            'cmid' => 'privacy:metadata:datacurso_ai:cmid',
            'course_structure' => 'privacy:metadata:datacurso_ai:course_structure',
            'custom_prompt' => 'privacy:metadata:datacurso_ai:custom_prompt',
            'grades' => 'privacy:metadata:datacurso_ai:grades',
            'lang' => 'privacy:metadata:datacurso_ai:lang',
            'messages' => 'privacy:metadata:datacurso_ai:messages',
            'page_url' => 'privacy:metadata:datacurso_ai:page_url',
            'selected_text' => 'privacy:metadata:datacurso_ai:selected_text',
            'site_id' => 'privacy:metadata:datacurso_ai:site_id',
            'site_url' => 'privacy:metadata:datacurso_ai:site_url',
            'timezone' => 'privacy:metadata:datacurso_ai:timezone',
            'userid' => 'privacy:metadata:datacurso_ai:userid',
        ], 'privacy:metadata:datacurso_ai');

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid];

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_dttutor_course_config} cc ON cc.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND cc.usermodified = :userid";
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_dttutor_session} s ON s.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel
                   AND s.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $params = ['courseid' => $context->instanceid];

        $sql = "SELECT usermodified FROM {local_dttutor_course_config} WHERE courseid = :courseid";
        $userlist->add_from_sql('usermodified', $sql, $params);

        $sql = "SELECT userid FROM {local_dttutor_session} WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        $root = get_string('pluginname', 'local_dttutor');

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $courseid = (int)$context->instanceid;

            $sessions = $DB->get_records(session_store::TABLE, ['userid' => $userid, 'courseid' => $courseid], 'id');
            if ($sessions) {
                $rows = [];
                foreach ($sessions as $session) {
                    $rows[] = (object)[
                        'cmid' => (int)$session->cmid,
                        'remotesessionid' => $session->remotesessionid,
                        'timecreated' => transform::datetime($session->timecreated),
                        'timemodified' => transform::datetime($session->timemodified),
                    ];
                }
                writer::with_context($context)->export_data(
                    [$root, get_string('privacy:export:sessions', 'local_dttutor')],
                    (object)['sessions' => $rows]
                );
            }

            $config = $DB->get_record('local_dttutor_course_config', ['courseid' => $courseid, 'usermodified' => $userid]);
            if ($config) {
                writer::with_context($context)->export_data(
                    [$root, get_string('privacy:export:course_config', 'local_dttutor')],
                    (object)[
                        'timemodified' => transform::datetime($config->timemodified),
                    ]
                );
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_course) {
            return;
        }
        $courseid = (int)$context->instanceid;

        session_store::purge('courseid = :courseid', ['courseid' => $courseid]);
        // The configuration belongs to the course, not to a person: only the editor reference is removed.
        $DB->set_field('local_dttutor_course_config', 'usermodified', 0, ['courseid' => $courseid]);
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $params = ['courseid' => (int)$context->instanceid, 'userid' => $userid];

            session_store::purge('courseid = :courseid AND userid = :userid', $params);
            $DB->set_field('local_dttutor_course_config', 'usermodified', 0, [
                'courseid' => $params['courseid'],
                'usermodified' => $userid,
            ]);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if (!$context instanceof \context_course || empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = ['courseid' => (int)$context->instanceid] + $inparams;

        session_store::purge("courseid = :courseid AND userid {$insql}", $params);
        $DB->set_field_select(
            'local_dttutor_course_config',
            'usermodified',
            0,
            "courseid = :courseid AND usermodified {$insql}",
            $params
        );
    }
}
