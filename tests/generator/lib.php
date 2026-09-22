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

/**
 * Data generator for local_dttutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_dttutor\course_config;
use local_dttutor\session_store;

/**
 * Creates the records the tutor keeps in Moodle, for tests that need a course already set up.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_dttutor_generator extends component_generator_base {
    /**
     * Switch the tutor on or off for a course.
     *
     * @param array $data Keys: courseid, and optionally enabled (defaults to on).
     */
    public function create_course_settings(array $data): void {
        if (empty($data['courseid'])) {
            throw new coding_exception('The course is required to configure the tutor.');
        }
        $enabled = array_key_exists('enabled', $data) ? (int)(bool)$data['enabled'] : 1;
        course_config::update((int)$data['courseid'], ['indexing_enabled' => $enabled]);
    }

    /**
     * Store the reference to a conversation opened in the AI service.
     *
     * @param array $data Keys: userid, courseid, remotesessionid, and optionally cmid.
     */
    public function create_conversation(array $data): void {
        if (empty($data['userid']) || empty($data['courseid'])) {
            throw new coding_exception('The user and the course are required to store a conversation.');
        }
        session_store::upsert(
            (int)$data['userid'],
            (int)$data['courseid'],
            isset($data['cmid']) ? (int)$data['cmid'] : null,
            (string)($data['remotesessionid'] ?? 'behat-session')
        );
    }
}
