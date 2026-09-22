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
 * Restore of the per-course settings of the Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_dttutor\course_config;

/**
 * Brings the switch of the tutor back with the course.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_dttutor_plugin extends restore_local_plugin {
    /**
     * Paths of the course backup this plugin reads.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element('local_dttutor_courseconfig', $this->get_pathfor('/courseconfig')),
        ];
    }

    /**
     * Restore the switch of the tutor for the course being restored.
     *
     * @param array|object $data
     */
    public function process_local_dttutor_courseconfig($data) {
        $data = (object)$data;
        course_config::update((int)$this->task->get_courseid(), [
            'indexing_enabled' => (int)(bool)($data->indexing_enabled ?? 0),
        ]);
    }
}
