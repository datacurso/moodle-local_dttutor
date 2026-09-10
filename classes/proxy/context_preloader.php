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
 * Course knowledge pre-loader for the AI chat proxy.
 *
 * Builds a compact, deterministic snapshot of the course and the activities the
 * requesting user can see (structure, dates, max grades) and, only when the
 * administrator enables local_dttutor/include_grades, that user's own grades, so
 * the model can answer course/activity questions from a single, pre-authorised
 * context block. Gathering this in PHP costs zero tokens.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

/**
 * Builds the pre-loaded course knowledge block for the system message.
 */
class context_preloader {
    /** @var array Instance fields that represent activity dates, mapped to a short label. */
    private const DATE_FIELDS = [
        'allowsubmissionsfromdate' => 'opens',
        'timeopen'                 => 'opens',
        'timeavailablefrom'        => 'available from',
        'duedate'                  => 'due',
        'timedue'                  => 'due',
        'timeclose'                => 'closes',
        'timeavailableuntil'       => 'available until',
        'cutoffdate'               => 'cutoff',
    ];

    /**
     * Build the full course-knowledge block (static course data + this student's grades when enabled).
     *
     * @param  int   $courseid The current course ID.
     * @param  int   $userid   The current user ID (for personal grades).
     * @param  array $context  The page context. Not used yet: kept so callers can pass it when activity focus is added.
     * @return string          A compact text block, or '' when there is nothing to add.
     */
    public static function build(int $courseid, int $userid, array $context = []): string {
        if ($courseid <= 1) {
            return '';
        }

        try {
            $course = get_course($courseid);
        } catch (\Throwable $e) {
            return '';
        }

        $static = self::get_static_block($course, $userid);
        if ($static === '') {
            return '';
        }

        // Personal grades are sent to the AI provider only when the site opted in.
        $student = '';
        if (get_config('local_dttutor', 'include_grades')) {
            $student = self::get_student_block($course, $userid);
        }

        return $static . $student;
    }

    /**
     * Get the static knowledge block for one user, cached per course, user and course revision.
     *
     * The block only lists modules visible to the given user, so it must never be
     * shared across users.
     *
     * @param  \stdClass $course The course record.
     * @param  int       $userid The user the block is built for.
     * @return string
     */
    private static function get_static_block(\stdClass $course, int $userid): string {
        $cache    = \cache::make('local_dttutor', 'course_knowledge');
        $cachekey = $course->id . '_' . $userid;
        $cached   = $cache->get($cachekey);
        if (is_array($cached) && (int)($cached['cacherev'] ?? -1) === (int)$course->cacherev) {
            return (string)$cached['text'];
        }

        $text = self::build_static_block($course, $userid);
        $cache->set($cachekey, ['cacherev' => (int)$course->cacherev, 'text' => $text]);
        return $text;
    }

    /**
     * Build the static course knowledge: course info + every activity visible to the user.
     *
     * @param  \stdClass $course The course record.
     * @param  int       $userid The user whose visibility applies.
     * @return string
     */
    private static function build_static_block(\stdClass $course, int $userid): string {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $modinfo = get_fast_modinfo($course, $userid);

        $lines = [];
        $lines[] = 'COURSE KNOWLEDGE (already retrieved for you — use it to answer directly '
            . 'whenever it contains the answer):';
        $lines[] = '- Course: ' . format_string($course->fullname) . ' (id ' . (int)$course->id . ')';

        $summary = trim(html_to_text((string)$course->summary, 0, false));
        if ($summary !== '') {
            $lines[] = '- Summary: ' . $summary;
        }

        $activitylines = [];
        foreach ($modinfo->get_cms() as $cm) {
            // Skip modules this user cannot see (hidden or restricted), labels and deleted modules.
            if (!$cm->uservisible || $cm->deletioninprogress || $cm->modname === 'label') {
                continue;
            }

            $parts = [];
            $parts[] = '[' . $cm->modname . ']';
            $parts[] = format_string($cm->name);
            $parts[] = '(cmid ' . (int)$cm->id . ', instance ' . (int)$cm->instance . ')';

            $sectionname = trim((string)$modinfo->get_section_info($cm->sectionnum)->name);
            if ($sectionname === '') {
                $sectionname = get_section_name($course, $cm->sectionnum);
            }
            if ($sectionname !== '') {
                $parts[] = "section '" . $sectionname . "'";
            }

            $dates = self::extract_dates($DB, $cm->modname, (int)$cm->instance);
            if ($dates !== '') {
                $parts[] = $dates;
            }

            $maxgrade = self::extract_max_grade((int)$course->id, $cm->modname, (int)$cm->instance);
            if ($maxgrade !== '') {
                $parts[] = 'max grade ' . $maxgrade;
            }

            $activitylines[] = '  - ' . implode(' | ', $parts);
        }

        if (!empty($activitylines)) {
            $lines[] = '- Activities (' . count($activitylines) . '):';
            $lines   = array_merge($lines, $activitylines);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Build the current student's personal grades block (fetched fresh, not cached).
     *
     * @param  \stdClass $course The course record.
     * @param  int       $userid The current user ID.
     * @return string
     */
    private static function get_student_block(\stdClass $course, int $userid): string {
        global $CFG;
        if ($userid <= 0) {
            return '';
        }
        require_once($CFG->libdir . '/gradelib.php');

        try {
            $modinfo = get_fast_modinfo($course, $userid);
        } catch (\Throwable $e) {
            return '';
        }

        $gradelines = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || $cm->deletioninprogress || $cm->modname === 'label') {
                continue;
            }
            $grade = self::extract_user_grade((int)$course->id, $cm->modname, (int)$cm->instance, $userid);
            if ($grade !== '') {
                $gradelines[] = '  - ' . format_string($cm->name) . ': ' . $grade;
            }
        }

        if (empty($gradelines)) {
            return '';
        }

        $coursegrade = self::extract_course_grade((int)$course->id, $userid);
        $header = "\nYOUR GRADES (current student, user id {$userid}):";
        if ($coursegrade !== '') {
            $header .= "\n  - Course total: " . $coursegrade;
        }

        return $header . "\n" . implode("\n", $gradelines) . "\n";
    }

    /**
     * Extract formatted date fields from a module instance record.
     *
     * @param  \moodle_database $db       The DB handle.
     * @param  string           $modname  Module name (e.g. assign, quiz).
     * @param  int              $instance Module instance ID.
     * @return string                     A " | "-free, comma-joined date string, or ''.
     */
    private static function extract_dates(\moodle_database $db, string $modname, int $instance): string {
        try {
            $record = $db->get_record($modname, ['id' => $instance]);
        } catch (\Throwable $e) {
            return '';
        }
        if (!$record) {
            return '';
        }

        $found = [];
        foreach (self::DATE_FIELDS as $field => $label) {
            if (!empty($record->$field) && (int)$record->$field > 0) {
                $found[] = $label . ': ' . userdate((int)$record->$field);
            }
        }

        return empty($found) ? '' : implode(', ', $found);
    }

    /**
     * Extract the maximum grade for a gradable activity.
     *
     * @param  int    $courseid Course ID.
     * @param  string $modname  Module name.
     * @param  int    $instance Module instance ID.
     * @return string           Formatted max grade, or '' if not graded.
     */
    private static function extract_max_grade(int $courseid, string $modname, int $instance): string {
        try {
            $grades = grade_get_grades($courseid, 'mod', $modname, $instance);
        } catch (\Throwable $e) {
            return '';
        }
        if (empty($grades->items)) {
            return '';
        }
        $item = reset($grades->items);
        if (!isset($item->grademax) || (float)$item->grademax <= 0) {
            return '';
        }
        return format_float((float)$item->grademax, (int)($item->decimals ?? 2));
    }

    /**
     * Extract the current user's grade for a gradable activity.
     *
     * @param  int    $courseid Course ID.
     * @param  string $modname  Module name.
     * @param  int    $instance Module instance ID.
     * @param  int    $userid   User ID.
     * @return string           Human-readable grade (e.g. "85 / 100"), or '' if none.
     */
    private static function extract_user_grade(int $courseid, string $modname, int $instance, int $userid): string {
        try {
            $grades = grade_get_grades($courseid, 'mod', $modname, $instance, $userid);
        } catch (\Throwable $e) {
            return '';
        }
        if (empty($grades->items)) {
            return '';
        }
        $item = reset($grades->items);
        if (empty($item->grades) || !isset($item->grades[$userid])) {
            return '';
        }
        $usergrade = $item->grades[$userid];
        if (!isset($usergrade->grade) || $usergrade->grade === null || $usergrade->grade === false) {
            return 'not graded yet';
        }
        $decimals = (int)($item->decimals ?? 2);
        $value    = format_float((float)$usergrade->grade, $decimals);
        if (isset($item->grademax) && (float)$item->grademax > 0) {
            return $value . ' / ' . format_float((float)$item->grademax, $decimals);
        }
        return $value;
    }

    /**
     * Extract the current user's overall course grade.
     *
     * @param  int $courseid Course ID.
     * @param  int $userid   User ID.
     * @return string        Human-readable course total, or ''.
     */
    private static function extract_course_grade(int $courseid, int $userid): string {
        try {
            $item = \grade_item::fetch_course_item($courseid);
            if (!$item) {
                return '';
            }
            $grade = new \grade_grade(['itemid' => $item->id, 'userid' => $userid], true);
            if (!$grade || $grade->finalgrade === null) {
                return '';
            }
            $decimals = (int)$item->get_decimals();
            $value    = format_float((float)$grade->finalgrade, $decimals);
            if ((float)$item->grademax > 0) {
                return $value . ' / ' . format_float((float)$item->grademax, $decimals);
            }
            return $value;
        } catch (\Throwable $e) {
            return '';
        }
    }
}
