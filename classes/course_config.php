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
 * Course configuration model for AI Tutor
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor;

/**
 * Model class for the per-course enablement of the AI tutor
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_config {
    /**
     * Get course configuration record (creates if not exists).
     *
     * @param int $courseid Course ID
     * @return \stdClass Course configuration record
     * @since Moodle 4.5
     */
    public static function get_by_course(int $courseid): \stdClass {
        global $DB;

        $record = $DB->get_record('local_dttutor_course_config', ['courseid' => $courseid]);

        if (!$record) {
            $record = self::create_default($courseid);
        }

        return $record;
    }

    /**
     * Create default configuration for a course.
     *
     * @param int $courseid Course ID
     * @return \stdClass Created configuration record
     * @since Moodle 4.5
     */
    private static function create_default(int $courseid): \stdClass {
        global $DB, $USER;

        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->indexing_enabled = 0; // Tutor disabled by default (column kept for schema compat).
        $record->timecreated = time();
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $record->id = $DB->insert_record('local_dttutor_course_config', $record);

        return $record;
    }

    /**
     * Update course configuration.
     *
     * @param int $courseid Course ID
     * @param array $data Data to update (field => value pairs)
     * @return bool Success status
     * @since Moodle 4.5
     */
    public static function update(int $courseid, array $data): bool {
        global $DB, $USER;

        $record = self::get_by_course($courseid);

        // Only update allowed fields.
        $allowedfields = [
            'indexing_enabled',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedfields) && property_exists($record, $key)) {
                $record->$key = $value;
            }
        }

        $record->timemodified = time();
        $record->usermodified = $USER->id;

        return $DB->update_record('local_dttutor_course_config', $record);
    }

    /**
     * Check if the tutor is enabled for a course.
     *
     * @param int $courseid Course ID
     * @return bool True if tutor is enabled
     * @since Moodle 4.5
     */
    public static function is_enabled_for_course(int $courseid): bool {
        $config = self::get_by_course($courseid);
        return (bool)$config->indexing_enabled;
    }

    /**
     * Delete course configuration.
     *
     * @param int $courseid Course ID
     * @return bool Success status
     * @since Moodle 4.5
     */
    public static function delete(int $courseid): bool {
        global $DB;
        return $DB->delete_records('local_dttutor_course_config', ['courseid' => $courseid]);
    }
}
