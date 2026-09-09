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
     * Delete the stored sessions matching a condition, remotely first and then locally.
     *
     * Remote deletion is best effort: a failure (including the remote client being
     * unavailable, e.g. no license key configured) is logged as metadata only and never
     * prevents the local rows from being removed, so Privacy API deletions always complete.
     *
     * @param string $select SQL fragment for the WHERE clause (named placeholders).
     * @param array $params Placeholder values.
     */
    public static function purge(string $select, array $params): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        $rows = $DB->get_records_select(self::TABLE, $select, $params, '', 'id, remotesessionid');
        if ($rows === []) {
            return;
        }

        try {
            $api = \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            \local_dttutor_log('SESSION_PURGE_REMOTE_UNAVAILABLE', ['exception' => get_class($e), 'rows' => count($rows)], true);
            $api = null;
        }

        if ($api !== null) {
            foreach ($rows as $row) {
                try {
                    $api->delete_session($row->remotesessionid);
                } catch (\Throwable $e) {
                    debugging('Remote deletion of Tutor-IA session failed: ' . get_class($e), DEBUG_DEVELOPER);
                }
            }
        }

        $DB->delete_records_select(self::TABLE, $select, $params);
    }
}
