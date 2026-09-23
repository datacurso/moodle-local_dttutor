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
 * Behat data generator for local_dttutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_dttutor_generator extends behat_generator_base {
    /**
     * Entities Behat can create for this plugin.
     *
     * @return array The list of creatable entities.
     */
    protected function get_creatable_entities(): array {
        return [
            'course settings' => [
                'singular' => 'course setting',
                'datagenerator' => 'course_settings',
                'required' => ['course'],
                'switchids' => ['course' => 'courseid'],
            ],
            'conversations' => [
                'singular' => 'conversation',
                'datagenerator' => 'conversation',
                'required' => ['user', 'course'],
                'switchids' => ['user' => 'userid', 'course' => 'courseid'],
            ],
        ];
    }
}
