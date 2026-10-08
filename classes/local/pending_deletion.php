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

namespace local_dttutor\local;

use local_dttutor\event\service_failed;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\proxy\document_read_failure;

/**
 * Deletions the AI service has not confirmed yet, kept until it does.
 *
 * Erasing a conversation takes two steps: the handle Moodle holds and the conversation the AI
 * service holds. The local step always completes, so that a privacy request never waits on a
 * third party. The remote step can fail —the service is down, the licence is gone, the request
 * times out— and when it did, the handle went with the local step and nothing could repeat it.
 * Whatever the service did not confirm is written down here instead, and a scheduled task asks
 * again until it does (Mindfree DTT-PRIV-004).
 *
 * A row is either one session, named by its remote identifier, or a deletion by user and course
 * that also reaches the conversations Moodle never registered.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pending_deletion {
    /** @var string Table holding the deletions waiting for the service. */
    public const TABLE = 'local_dttutor_pending_delete';

    /** @var int Failed attempts after which the administrator is told that a deletion is stuck. */
    public const ALERT_AFTER_ATTEMPTS = 8;

    /** @var int Seconds before the first retry; each failure doubles it. */
    public const FIRST_DELAY = 300;

    /** @var int Longest wait between two attempts. */
    public const MAX_DELAY = DAYSECS;

    /** @var int Deletions tried in one run of the task, so a long outage does not hold the cron. */
    public const BATCH_SIZE = 200;

    /** @var string Reason recorded when the client of the service could not even be built. */
    public const PROVIDER_UNAVAILABLE = 'provider_unavailable';

    /**
     * Keep a session the service did not confirm as deleted.
     *
     * @param string $remotesessionid Session identifier in the AI service.
     * @param \Throwable|string $failure What the deletion failed with, or its reason.
     */
    public static function queue_session(string $remotesessionid, \Throwable|string $failure): void {
        global $DB;

        if ($remotesessionid === '' || $DB->record_exists(self::TABLE, ['remotesessionid' => $remotesessionid])) {
            return;
        }
        self::insert(['remotesessionid' => $remotesessionid, 'userid' => null, 'courseid' => null], $failure);
    }

    /**
     * Keep a deletion by user and course the service did not confirm.
     *
     * @param int|null $userid User whose conversations are deleted, or null for every user of the course.
     * @param int|null $courseid Course the deletion is limited to, or null for every course of the user.
     * @param \Throwable|string $failure What the deletion failed with, or its reason.
     */
    public static function queue_purge(?int $userid, ?int $courseid, \Throwable|string $failure): void {
        global $DB;

        if ($userid === null && $courseid === null) {
            return;
        }

        $conditions = ['remotesessionid IS NULL'];
        $params = [];
        foreach (['userid' => $userid, 'courseid' => $courseid] as $field => $value) {
            if ($value === null) {
                $conditions[] = "{$field} IS NULL";
            } else {
                $conditions[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }
        if ($DB->record_exists_select(self::TABLE, implode(' AND ', $conditions), $params)) {
            return;
        }
        self::insert(['remotesessionid' => null, 'userid' => $userid, 'courseid' => $courseid], $failure);
    }

    /**
     * Ask the service again for every deletion that is due.
     *
     * @param tutoria_api|null $api API the deletions go through, or null when it cannot be built.
     * @param int $now Current time.
     * @return array{confirmed: int, failed: int} Deletions confirmed and deletions still pending.
     */
    public static function retry_due(?tutoria_api $api, int $now): array {
        global $DB;

        $select = 'nextattempt <= :now';
        $rows = $DB->get_records_select(self::TABLE, $select, ['now' => $now], 'nextattempt, id', '*', 0, self::BATCH_SIZE);

        $result = ['confirmed' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            if (self::attempt($api, $row)) {
                $DB->delete_records(self::TABLE, ['id' => $row->id]);
                $result['confirmed']++;
            } else {
                $result['failed']++;
            }
        }
        return $result;
    }

    /**
     * Whether a failed deletion means that there was nothing left to delete.
     *
     * The service answers 404 for a session that expired on its own: that is the outcome the
     * deletion wanted, not a failure to repeat.
     *
     * @param \Throwable $error What the deletion failed with.
     * @return bool
     */
    public static function is_already_gone(\Throwable $error): bool {
        return document_read_failure::reason_of($error) === 'http_404';
    }

    /**
     * Try one pending deletion, and on failure push the next attempt further away.
     *
     * @param tutoria_api|null $api API the deletion goes through, or null when it cannot be built.
     * @param \stdClass $row The pending deletion.
     * @return bool True when the service confirmed it.
     */
    private static function attempt(?tutoria_api $api, \stdClass $row): bool {
        if ($api === null) {
            self::postpone($row, self::PROVIDER_UNAVAILABLE);
            return false;
        }

        try {
            if ($row->remotesessionid !== null) {
                $api->delete_remote_session((string)$row->remotesessionid);
            } else {
                $api->purge_conversations(
                    $row->userid === null ? null : (int)$row->userid,
                    $row->courseid === null ? null : (int)$row->courseid
                );
            }
            return true;
        } catch (\Throwable $e) {
            if (self::is_already_gone($e)) {
                return true;
            }
            self::postpone($row, document_read_failure::reason_of($e));
            return false;
        }
    }

    /**
     * Record a failed attempt, and tell the administrator once a deletion stays stuck.
     *
     * @param \stdClass $row The pending deletion.
     * @param string $reason Short machine-readable reason of the failure.
     */
    private static function postpone(\stdClass $row, string $reason): void {
        global $DB;

        $now = time();
        $row->attempts = (int)$row->attempts + 1;
        $row->nextattempt = $now + self::delay_after((int)$row->attempts);
        $row->lasterror = \core_text::substr($reason, 0, 64);
        $row->timemodified = $now;
        $DB->update_record(self::TABLE, $row);

        if ((int)$row->attempts === self::ALERT_AFTER_ATTEMPTS) {
            self::alert($row, $reason);
        }
    }

    /**
     * Seconds to wait after a number of failed attempts.
     *
     * @param int $attempts Failed attempts so far.
     * @return int
     */
    public static function delay_after(int $attempts): int {
        $delay = self::FIRST_DELAY * (2 ** max(0, min($attempts - 1, 16)));
        return (int)min($delay, self::MAX_DELAY);
    }

    /**
     * Write a new pending deletion.
     *
     * @param array $scope remotesessionid, userid and courseid of the deletion.
     * @param \Throwable|string $failure What the first attempt failed with, or its reason.
     */
    private static function insert(array $scope, \Throwable|string $failure): void {
        global $DB;

        $reason = is_string($failure) ? $failure : document_read_failure::reason_of($failure);
        $now = time();
        $record = (object)($scope + [
            'attempts' => 1,
            'nextattempt' => $now + self::delay_after(1),
            'lasterror' => \core_text::substr($reason, 0, 64),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Tell the administrator that a deletion keeps failing.
     *
     * Metadata only: neither the person nor the session is named, so the alert itself does not
     * become personal data that outlives the deletion it reports.
     *
     * @param \stdClass $row The pending deletion.
     * @param string $reason Short machine-readable reason of the last failure.
     */
    private static function alert(\stdClass $row, string $reason): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        \local_dttutor_log('REMOTE_DELETION_OVERDUE', [
            'id' => (int)$row->id,
            'attempts' => (int)$row->attempts,
            'reason' => $reason,
            'kind' => $row->remotesessionid === null ? 'purge' : 'session',
        ], true);
        service_failed::record($reason, service_failed::OPERATION_DELETION);
    }
}
