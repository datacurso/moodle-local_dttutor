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
 * Backup of the per-course settings of the Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Puts the switch of the tutor into the backup of the course.
 *
 * Only the setting of the course travels. The references to the conversations held in the AI
 * service are personal data tied to a service outside this site, and a copy of a course is no
 * place for them.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_dttutor_plugin extends backup_local_plugin {
    /**
     * Structure added to the backup of a course.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $config = new backup_nested_element('courseconfig', ['id'], ['indexing_enabled', 'timemodified']);
        $wrapper->add_child($config);

        $config->set_source_table('local_dttutor_course_config', ['courseid' => backup::VAR_COURSEID]);

        return $plugin;
    }
}
