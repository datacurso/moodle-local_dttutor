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

use local_dttutor\session_store;

/**
 * Remove the conversations that have outlived the retention period of the site.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_old_conversations extends \core\task\scheduled_task {
    /**
     * Name of the task, as the administration shows it.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_purge_old_conversations', 'local_dttutor');
    }

    /**
     * Delete every conversation older than the retention period, here and in the AI service.
     */
    public function execute(): void {
        $days = (int)get_config('local_dttutor', 'retention_days');
        if ($days <= 0) {
            // No period agreed: conversations are kept until the user, the course or a privacy
            // request removes them.
            return;
        }

        $cutoff = time() - ($days * DAYSECS);
        session_store::purge('timemodified < :cutoff', ['cutoff' => $cutoff]);
    }
}
