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

use local_dttutor\httpclient\tutoria_api;
use local_dttutor\proxy\document_read_failure;
use local_dttutor\task\delete_remote_conversation;

/**
 * Deletions the AI service has not confirmed yet, kept until it does.
 *
 * Erasing a conversation takes two steps: the handle Moodle holds and the conversation the AI
 * service holds. The local step always completes, so that a privacy request never waits on a
 * third party. The remote step can fail —the service is down, the licence is gone, the request
 * times out— and when it did, the handle went with the local step and nothing could repeat it.
 * Whatever the service did not confirm is handed to an ad hoc task instead, which the cron runs
 * again, waiting longer after each failure, until the service confirms it (Mindfree DTT-PRIV-004).
 * The queue of ad hoc tasks of Moodle holds the deletion: the plugin keeps no table of its own.
 *
 * A deletion is either one session, named by its remote identifier, or a deletion by user and
 * course that also reaches the conversations Moodle never registered.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pending_deletion {
    /** @var int Seconds before the first retry; the cron doubles the wait after each failure. */
    public const FIRST_DELAY = 300;

    /** @var string Reason recorded when the client of the service could not even be built. */
    public const PROVIDER_UNAVAILABLE = 'provider_unavailable';

    /**
     * Keep a session the service did not confirm as deleted.
     *
     * @param string $remotesessionid Session identifier in the AI service.
     */
    public static function queue_session(string $remotesessionid): void {
        if ($remotesessionid === '') {
            return;
        }
        self::queue(['remotesessionid' => $remotesessionid, 'userid' => null, 'courseid' => null]);
    }

    /**
     * Keep a deletion by user and course the service did not confirm.
     *
     * @param int|null $userid User whose conversations are deleted, or null for every user of the course.
     * @param int|null $courseid Course the deletion is limited to, or null for every course of the user.
     */
    public static function queue_purge(?int $userid, ?int $courseid): void {
        if ($userid === null && $courseid === null) {
            return;
        }
        self::queue(['remotesessionid' => null, 'userid' => $userid, 'courseid' => $courseid]);
    }

    /**
     * Every deletion still waiting for the service.
     *
     * @return array[] Scopes with remotesessionid, userid and courseid, oldest first.
     */
    public static function pending(): array {
        $tasks = \core\task\manager::get_adhoc_tasks(delete_remote_conversation::class);
        ksort($tasks);

        $scopes = [];
        foreach ($tasks as $task) {
            $scope = self::scope_of($task->get_custom_data());
            if ($scope !== null) {
                $scopes[] = $scope;
            }
        }
        return $scopes;
    }

    /**
     * Ask the service for one deletion.
     *
     * @param tutoria_api $api API the deletion goes through.
     * @param array $scope remotesessionid, userid and courseid of the deletion.
     * @throws \Throwable When the service did not confirm the deletion.
     */
    public static function attempt(tutoria_api $api, array $scope): void {
        try {
            if ($scope['remotesessionid'] !== null) {
                $api->delete_remote_session($scope['remotesessionid']);
            } else {
                $api->purge_conversations($scope['userid'], $scope['courseid']);
            }
        } catch (\Throwable $e) {
            if (!self::is_already_gone($e)) {
                throw $e;
            }
        }
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
     * The scope of a deletion as the ad hoc task carries it.
     *
     * @param mixed $customdata Custom data of the task.
     * @return array|null remotesessionid, userid and courseid, or null when it names nothing.
     */
    public static function scope_of(mixed $customdata): ?array {
        $data = (array)$customdata;
        $remotesessionid = isset($data['remotesessionid']) && $data['remotesessionid'] !== ''
            ? (string)$data['remotesessionid'] : null;
        $userid = isset($data['userid']) ? (int)$data['userid'] : null;
        $courseid = isset($data['courseid']) ? (int)$data['courseid'] : null;

        if ($remotesessionid === null && $userid === null && $courseid === null) {
            return null;
        }
        if ($remotesessionid !== null) {
            return ['remotesessionid' => $remotesessionid, 'userid' => null, 'courseid' => null];
        }
        return ['remotesessionid' => null, 'userid' => $userid, 'courseid' => $courseid];
    }

    /**
     * Hand a deletion to the cron, once.
     *
     * The same deletion queued twice is kept once: the queue compares the scope.
     *
     * @param array $scope remotesessionid, userid and courseid of the deletion.
     */
    private static function queue(array $scope): void {
        $task = new delete_remote_conversation();
        $task->set_custom_data($scope);
        $task->set_attempts_available(delete_remote_conversation::ATTEMPTS);
        $task->set_next_run_time(time() + self::FIRST_DELAY);
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
