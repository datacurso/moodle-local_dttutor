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

namespace local_dttutor\task;

use local_dttutor\event\service_failed;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\local\pending_deletion;
use local_dttutor\proxy\document_read_failure;

/**
 * Ask the AI service again for a deletion it has not confirmed yet.
 *
 * A failure is thrown back to the cron, which runs the task again later and doubles the wait
 * each time, up to a day. The queue of Moodle gives a task a fixed number of attempts and then
 * leaves it failed for good, which would lose the deletion once more: on its last attempt the
 * task hands the same deletion to a new task instead, so it is asked for until the service
 * confirms it.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_remote_conversation extends \core\task\adhoc_task {
    /** @var int Attempts a task is given before it hands the deletion to a new one. */
    public const ATTEMPTS = 12;

    /** @var int Failed attempts after which the administrator is told that a deletion is stuck. */
    public const ALERT_AFTER_ATTEMPTS = 8;

    /**
     * Name of the task, as the administration shows it.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_retry_pending_deletions', 'local_dttutor');
    }

    /**
     * Ask for the deletion, and fail so that the cron asks again while the service does not confirm.
     *
     * @throws \Throwable When the service did not confirm the deletion and attempts remain.
     */
    public function execute(): void {
        $scope = pending_deletion::scope_of($this->get_custom_data());
        if ($scope === null) {
            // Nothing is named, so there is nothing to delete.
            return;
        }

        try {
            $api = \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            $this->failed($scope, pending_deletion::PROVIDER_UNAVAILABLE, $e);
            return;
        }

        try {
            pending_deletion::attempt($api, $scope);
        } catch (\Throwable $e) {
            $this->failed($scope, document_read_failure::reason_of($e), $e);
            return;
        }
        mtrace('Remote deletion confirmed.');
    }

    /**
     * Report a failed attempt, and keep the deletion for the next one.
     *
     * @param array $scope remotesessionid, userid and courseid of the deletion.
     * @param string $reason Short machine-readable reason of the failure.
     * @param \Throwable $error What the attempt failed with.
     * @throws \Throwable The same error, while this task has attempts left.
     */
    private function failed(array $scope, string $reason, \Throwable $error): void {
        $attempt = self::ATTEMPTS - $this->get_attempts_available() + 1;
        mtrace("Remote deletion not confirmed ({$reason}), attempt {$attempt}.");

        if ($attempt === self::ALERT_AFTER_ATTEMPTS) {
            $this->alert($scope, $reason, $attempt);
        }

        if ($this->get_attempts_available() > 1) {
            throw $error;
        }

        // The last attempt of this task: a new one carries the deletion on, a day from now.
        $next = new self();
        $next->set_custom_data($scope);
        $next->set_attempts_available(self::ATTEMPTS);
        $next->set_fail_delay(DAYSECS);
        $next->set_next_run_time(time() + DAYSECS);
        \core\task\manager::queue_adhoc_task($next);
        mtrace('Remote deletion handed to a new task.');
    }

    /**
     * Tell the administrator that a deletion keeps failing.
     *
     * Metadata only: neither the person nor the session is named, so the alert itself does not
     * become personal data that outlives the deletion it reports.
     *
     * @param array $scope remotesessionid, userid and courseid of the deletion.
     * @param string $reason Short machine-readable reason of the last failure.
     * @param int $attempt Attempts this task has made.
     */
    private function alert(array $scope, string $reason, int $attempt): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        \local_dttutor_log('REMOTE_DELETION_OVERDUE', [
            'task' => (int)$this->get_id(),
            'attempts' => $attempt,
            'reason' => $reason,
            'kind' => $scope['remotesessionid'] === null ? 'purge' : 'session',
        ], true);
        service_failed::record($reason, service_failed::OPERATION_DELETION);
    }
}
