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

/**
 * Event observers that keep the plugin data in step with course and user deletions.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Remove the course configuration and the stored chat sessions of a deleted course.
     *
     * The cached text of the course is forgotten earlier, before the course is deleted, by
     * {@see \local_dttutor\hook\course_deletion}: its files are needed to find it.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        $courseid = (int)$event->objectid;
        course_config::delete($courseid);
        // Nothing of the course ever reached the service: do not ask it, do not log a failure.
        if (!session_store::has_sessions(null, $courseid)) {
            return;
        }
        $remote = session_store::purge_remote_conversations(null, $courseid);
        session_store::purge('courseid = :courseid', ['courseid' => $courseid], $remote === null);
    }

    /**
     * Remove the stored chat sessions and the cached course knowledge of a deleted user.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        $userid = (int)$event->objectid;
        // Before the early return: the knowledge blocks are cached whether or not a session was stored.
        local\cached_text::forget_user($userid);
        // Nothing of the user ever reached the service: do not ask it, do not log a failure.
        if (!session_store::has_sessions($userid, null)) {
            return;
        }
        $remote = session_store::purge_remote_conversations($userid, null);
        session_store::purge('userid = :userid', ['userid' => $userid], $remote === null);
    }
}
