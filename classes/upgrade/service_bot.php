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

namespace local_dttutor\upgrade;

/**
 * Retires the legacy service bot user created by earlier plugin versions.
 *
 * Versions up to 2.0.8 created a "tutoriabot_datacurso" user, stored its id in
 * the serviceuserid setting and enrolled it as editing teacher in every course
 * where the tutor was enabled. The web service bridge that impersonated it is
 * gone, so the account is unenrolled everywhere and suspended. It is not
 * deleted so that log entries referencing it remain intact.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_bot {
    /** @var string Username used by the legacy service bot. */
    public const USERNAME = 'tutoriabot_datacurso';

    /**
     * Unenrol the bot from every course, suspend it and forget its id. Safe to call repeatedly.
     */
    public static function retire(): void {
        $bot = self::find_bot();
        if ($bot !== null) {
            self::unenrol_everywhere((int)$bot->id);

            if (empty($bot->suspended)) {
                self::suspend((int)$bot->id);
                \core\session\manager::destroy_user_sessions((int)$bot->id);
            }
        }

        unset_config('serviceuserid', 'local_dttutor');
    }

    /**
     * Suspend a user through the user API of the running Moodle.
     *
     * Moodle 5.3 deprecated user_update_user() in favour of \core\user::update_user() (MDL-82650),
     * which Moodle 4.5 does not have.
     *
     * @param int $userid
     */
    private static function suspend(int $userid): void {
        global $CFG;

        $update = (object)['id' => $userid, 'suspended' => 1];
        if (method_exists(\core\user::class, 'update_user')) {
            \core\user::update_user($update, false, true);
            return;
        }

        require_once($CFG->dirroot . '/user/lib.php');
        user_update_user($update, false, true);
    }

    /**
     * Locate the bot user by stored id, falling back to its well-known username.
     *
     * @return \stdClass|null
     */
    private static function find_bot(): ?\stdClass {
        global $CFG, $DB;

        $serviceuserid = (int)get_config('local_dttutor', 'serviceuserid');
        if ($serviceuserid > 0) {
            $bot = $DB->get_record('user', ['id' => $serviceuserid, 'deleted' => 0]);
            if ($bot) {
                return $bot;
            }
        }

        $bot = $DB->get_record('user', [
            'username' => self::USERNAME,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ]);

        return $bot ?: null;
    }

    /**
     * Remove every enrolment of the user through the owning enrol plugin.
     *
     * @param int $userid The user id.
     */
    private static function unenrol_everywhere(int $userid): void {
        global $DB;

        $sql = "SELECT e.*
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid";
        $instances = $DB->get_records_sql($sql, ['userid' => $userid]);
        foreach ($instances as $instance) {
            $plugin = enrol_get_plugin($instance->enrol);
            if ($plugin) {
                $plugin->unenrol_user($instance, $userid);
            }
        }
    }
}
