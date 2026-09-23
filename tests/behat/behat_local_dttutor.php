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
 * Page resolvers for local_dttutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_dttutor extends behat_base {
    /**
     * Convert page names to URLs for steps like "I am on the "C1" "local_dttutor > course management" page".
     *
     * Recognised page types:
     *  - "course management": the per-course tutor page. Identifier: the course shortname.
     *
     * @param string $type Identifies the page type.
     * @param string $identifier Identifies the particular page.
     * @return moodle_url The page URL.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;

        switch (strtolower($type)) {
            case 'course management':
                $courseid = $DB->get_field('course', 'id', ['shortname' => $identifier], MUST_EXIST);
                return new moodle_url('/local/dttutor/manage.php', ['id' => $courseid]);

            default:
                throw new Exception('Unrecognised local_dttutor page type "' . $type . '".');
        }
    }
}
