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
 * Someone asked the AI tutor a question.
 *
 * Carries who asked, in which course and activity, and when. Never the question itself: the
 * plugin does not store the text of the messages, and a log is no place to start.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tutor_used extends \core\event\base {
    /**
     * Set the basic properties of the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Name of the event, as the log report shows it.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_tutor_used', 'local_dttutor');
    }

    /**
     * Description of what happened, for the log report.
     *
     * @return string
     */
    public function get_description() {
        $cmid = (int)($this->other['cmid'] ?? 0);
        $where = $cmid > 0 ? " from the course module with id '{$cmid}'" : '';
        return "The user with id '{$this->userid}' asked the AI tutor in the course with id " .
            "'{$this->courseid}'{$where}.";
    }

    /**
     * Where the event happened.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $cmid = (int)($this->other['cmid'] ?? 0);
        if ($cmid > 0) {
            return new \moodle_url('/mod/' . ($this->other['modname'] ?? 'forum') . '/view.php', ['id' => $cmid]);
        }
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    /**
     * Validate the data the event was created with.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('The tutor is always used in the context of a course.');
        }
    }
}
