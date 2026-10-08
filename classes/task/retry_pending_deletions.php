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

use local_dttutor\httpclient\tutoria_api;
use local_dttutor\local\pending_deletion;

/**
 * Ask the AI service again for the deletions it has not confirmed yet.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retry_pending_deletions extends \core\task\scheduled_task {
    /**
     * Name of the task, as the administration shows it.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_retry_pending_deletions', 'local_dttutor');
    }

    /**
     * Try every deletion that is due, and keep the ones the service still does not confirm.
     */
    public function execute(): void {
        try {
            $api = \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            // Nothing can be asked while the provider cannot be built; the deletions wait.
            $api = null;
        }

        $result = pending_deletion::retry_due($api, time());
        if ($result['confirmed'] > 0 || $result['failed'] > 0) {
            mtrace("Remote deletions confirmed: {$result['confirmed']}; still pending: {$result['failed']}.");
        }
    }
}
