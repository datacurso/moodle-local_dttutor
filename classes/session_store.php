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

namespace local_dttutor;

use local_dttutor\httpclient\client_factory;
use local_dttutor\httpclient\provider_config;
use local_dttutor\httpclient\tutoria_api;

/**
 * Durable record of the remote chat sessions opened for each user.
 *
 * The Datacurso AI service keeps the conversation; Moodle only stores the session
 * handle (one row per user, course and module) so that Privacy API requests and the
 * course/user deletion observers can find and delete the remote sessions later.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_store {
    /** @var string Table holding the session handles. */
    public const TABLE = 'local_dttutor_session';

    /**
     * Store (or replace) the remote session handle for a user, course and module.
     *
     * @param int $userid User who owns the session.
     * @param int $courseid Course the session belongs to.
     * @param int|null $cmid Course module, or null for the whole course.
     * @param string $remotesessionid Session identifier in the Datacurso AI service.
     */
    public static function upsert(int $userid, int $courseid, ?int $cmid, string $remotesessionid): void {
        global $DB;

        $now = time();
        $conditions = ['userid' => $userid, 'courseid' => $courseid, 'cmid' => (int)$cmid];
        $existing = $DB->get_record(self::TABLE, $conditions);

        if ($existing) {
            $existing->remotesessionid = $remotesessionid;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
            return;
        }

        $record = (object)$conditions;
        $record->remotesessionid = $remotesessionid;
        $record->timecreated = $now;
        $record->timemodified = $now;

        try {
            static::insert($record);
        } catch (\dml_write_exception $e) {
            // A concurrent request inserted the same (userid, courseid, cmid) between our read and
            // our write; the unique index rejected ours. Recover by updating that row instead, so the
            // result is the same last-writer-wins outcome as if we had found it in the first place.
            $existing = $DB->get_record(self::TABLE, $conditions);
            if (!$existing) {
                throw $e;
            }
            $existing->remotesessionid = $remotesessionid;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        }
    }

    /**
     * Insert a new session row.
     *
     * Kept as a separate overridable step so tests can interpose a concurrent insert
     * between the existence check and the write performed by {@see upsert()}.
     *
     * @param \stdClass $record Row to insert.
     * @throws \dml_write_exception When the unique (userid, courseid, cmid) index is violated.
     */
    protected static function insert(\stdClass $record): void {
        global $DB;
        $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Find the stored remote session handle for a user, course and module.
     *
     * @param int $userid User who owns the session.
     * @param int $courseid Course the session belongs to.
     * @param int|null $cmid Course module, or null for the whole course.
     * @return string|null The remote session identifier, or null when none is stored.
     */
    public static function get_remote_session_id(int $userid, int $courseid, ?int $cmid): ?string {
        global $DB;

        $conditions = ['userid' => $userid, 'courseid' => $courseid, 'cmid' => (int)$cmid];
        $remotesessionid = $DB->get_field(self::TABLE, 'remotesessionid', $conditions);

        return $remotesessionid === false ? null : (string)$remotesessionid;
    }

    /**
     * Forget a remote session handle (the remote session itself is not touched).
     *
     * @param string $remotesessionid Session identifier in the Datacurso AI service.
     */
    public static function forget(string $remotesessionid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['remotesessionid' => $remotesessionid]);
    }

    /**
     * Ask the AI service to delete every conversation of a user, or of a whole course.
     *
     * Deleting the stored handles one by one only reaches the conversations Moodle knows
     * about. The ones opened before the handles began to be stored have no row here, so a
     * suppression request that relied on them would leave data behind; this asks the service
     * by user and course instead, which covers both.
     *
     * The call is best effort in the same way as {@see purge()}: a failure is logged as
     * metadata only and never stops the local rows from being removed, so a Privacy API
     * request always completes. The caller learns that the service was not reached from the
     * null return, and can fall back to deleting the known sessions one by one.
     *
     * @param int|null $userid User whose conversations are deleted, or null for every user of
     *                         the course.
     * @param int|null $courseid Course the deletion is limited to, or null for every course of
     *                           the user.
     * @return int|null Conversations the service reported as deleted, or null when it could
     *                  not be asked.
     */
    public static function purge_remote_conversations(?int $userid, ?int $courseid): ?int {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        if ($userid === null && $courseid === null) {
            return null;
        }

        try {
            $deleted = 0;
            foreach (self::owners_by_tenant($userid, $courseid) as $owner) {
                $response = self::api_for($owner)->purge_conversations($userid, $courseid);
                $deleted += (int)($response['deleted_sessions'] ?? 0);
            }
        } catch (\Throwable $e) {
            // Metadata only: the scope says what was asked for without naming the person.
            \local_dttutor_log('CONVERSATION_PURGE_FAILED', [
                'courseid' => $courseid,
                'exception' => get_class($e),
                'scope' => $userid === null ? 'course' : ($courseid === null ? 'user' : 'user_in_course'),
            ], true);
            return null;
        }

        return $deleted;
    }

    /**
     * Delete the stored sessions matching a condition, remotely first and then locally.
     *
     * Remote deletion is best effort: a failure (including the remote client being
     * unavailable, e.g. no license key configured) is logged as metadata only and never
     * prevents the local rows from being removed, so Privacy API deletions always complete.
     *
     * @param string $select SQL fragment for the WHERE clause (named placeholders).
     * @param array $params Placeholder values.
     * @param bool $deleteremote Whether each stored session is also deleted in the AI service.
     *                           Pass false when {@see purge_remote_conversations()} already
     *                           erased the same conversations by user and course.
     */
    public static function purge(string $select, array $params, bool $deleteremote = true): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        $rows = $DB->get_records_select(self::TABLE, $select, $params, '', 'id, remotesessionid, userid');
        if ($rows === []) {
            return;
        }

        if (!$deleteremote) {
            $DB->delete_records_select(self::TABLE, $select, $params);
            return;
        }

        foreach (self::group_by_tenant($rows) as $owner => $group) {
            try {
                $api = self::api_for($owner ?: null);
            } catch (\Throwable $e) {
                \local_dttutor_log(
                    'SESSION_PURGE_REMOTE_UNAVAILABLE',
                    ['exception' => get_class($e), 'rows' => count($group)],
                    true
                );
                continue;
            }

            foreach ($group as $row) {
                try {
                    $api->delete_session($row->remotesessionid);
                } catch (\Throwable $e) {
                    debugging('Remote deletion of Tutor-IA session failed: ' . get_class($e), DEBUG_DEVELOPER);
                }
            }
        }

        $DB->delete_records_select(self::TABLE, $select, $params);
    }

    /**
     * API client for the requests made about the conversations of a user.
     *
     * On Workplace the request goes with the licence of the tenant of that user, not with the one
     * of whoever triggered the deletion (usually an administrator of another tenant). Elsewhere,
     * and when no user is given, it is the client of the current request.
     *
     * @param int|null $userid User the conversations belong to.
     * @return tutoria_api
     * @throws \Throwable When the client cannot be built.
     */
    private static function api_for(?int $userid): tutoria_api {
        if ($userid === null || !provider_config::has_tenants()) {
            return \core\di::get(tutoria_api::class);
        }

        return new tutoria_api(client_factory::for_user($userid));
    }

    /**
     * One user of each tenant whose conversations a purge reaches, to send it with their licence.
     *
     * A purge of a user goes with that user. A purge of a whole course goes once for each tenant
     * with a stored conversation in it; when none is stored, or on a site without tenancy, it goes
     * once with the client of the current request.
     *
     * @param int|null $userid User the purge is limited to, or null for every user.
     * @param int|null $courseid Course the purge is limited to, or null for every course.
     * @return array<int, int|null> Users to send the purge as, null for the current request.
     */
    private static function owners_by_tenant(?int $userid, ?int $courseid): array {
        global $DB;

        if ($userid !== null || !provider_config::has_tenants()) {
            return [$userid];
        }

        $owners = $DB->get_fieldset_select(self::TABLE, 'DISTINCT userid', 'courseid = :courseid', ['courseid' => $courseid]);
        $grouped = self::group_by_tenant(array_map(static fn($owner) => (object)['userid' => (int)$owner], $owners));

        return $grouped === [] ? [null] : array_keys($grouped);
    }

    /**
     * Group stored sessions by the tenant of their owner, keyed by one owner of each tenant.
     *
     * Without tenancy every session goes in a single group keyed by 0, sent with the client of the
     * current request.
     *
     * @param \stdClass[] $rows Sessions with at least the userid field.
     * @return array<int, \stdClass[]>
     */
    private static function group_by_tenant(array $rows): array {
        if (!provider_config::has_tenants()) {
            return $rows === [] ? [] : [0 => array_values($rows)];
        }

        $owners = [];
        $groups = [];
        foreach ($rows as $row) {
            $tenantid = provider_config::get_tenant_id((int)$row->userid);
            $owners[$tenantid] ??= (int)$row->userid;
            $groups[$owners[$tenantid]][] = $row;
        }

        return $groups;
    }
}
