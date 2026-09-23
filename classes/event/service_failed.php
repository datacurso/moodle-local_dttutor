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

namespace local_dttutor\event;

/**
 * The AI service refused a request or could not be reached.
 *
 * Recorded so that an administrator sees the failures of the tutor in the reports of the
 * platform, instead of having to read the error log of the server.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_failed extends \core\event\base {
    /**
     * Set the basic properties of the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Name of the event, as the log report shows it.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_service_failed', 'local_dttutor');
    }

    /**
     * Description of what happened, for the log report.
     *
     * @return string
     */
    public function get_description() {
        $reason = (string)($this->other['reason'] ?? 'unknown');
        return "A request to the AI tutor service failed with the reason '{$reason}'.";
    }

    /**
     * Record a failure of the service, without ever getting in the way of answering the user.
     *
     * @param string $reason Short machine-readable reason.
     */
    public static function record(string $reason): void {
        try {
            self::create([
                'context' => \context_system::instance(),
                'other' => ['reason' => $reason],
            ])->trigger();
        } catch (\Throwable $e) {
            // Reporting a failure must never become one.
            unset($e);
        }
    }
}
