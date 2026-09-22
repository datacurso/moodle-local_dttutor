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
 * requesting user can see (structure, dates, time limits, max grades and how far
 * the user has got) and, only when the administrator enables
 * local_dttutor/include_grades, that user's own released grades, so the model can
 * answer course/activity questions from a single, pre-authorised context block.
 * Gathering this in PHP costs zero tokens.
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
    /** @var int Activities listed at most, unless the administrator configures another limit. */
    public const DEFAULT_MAX_ACTIVITIES = 100;

    /**
     * Instance fields that represent activity dates, mapped to a short label.
     *
     * Ordered opening dates first so that a reader follows the life of the activity.
     *
     * @var array
     */
    private const DATE_FIELDS = [
        'allowsubmissionsfromdate' => 'opens',
        'timeopen'                 => 'opens',
        'available'                => 'opens',
        'openingtime'              => 'opens',
        'timeavailablefrom'        => 'available from',
        'submissionstart'          => 'submissions open',
        'assessmentstart'          => 'assessment opens',
        'duedate'                  => 'due',
        'timedue'                  => 'due',
        'deadline'                 => 'due',
        'submissionend'            => 'submissions close',
        'assessmentend'            => 'assessment closes',
        'timeclose'                => 'closes',
        'closingtime'              => 'closes',
        'timeavailableto'          => 'available until',
        'timeavailableuntil'       => 'available until',
        'cutoffdate'               => 'cutoff',
    ];

    /** @var array Instance fields that represent a duration in seconds, mapped to a short label. */
    private const DURATION_FIELDS = [
        'timelimit' => 'time limit',
    ];

    /**
     * Build the pre-loaded course knowledge block.
     *
     * @param int $courseid Course id.
     * @param int $userid User the knowledge is built for.
     * @param array $context Client context (unused for now, kept for future use).
     * @return string The knowledge block, or an empty string when there is nothing to say.
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

        $progress = self::get_progress_block($course, $userid);

        $student = '';
        if (get_config('local_dttutor', 'include_grades')) {
            $student = self::get_student_block($course, $userid);
        }

        return $static . $progress . $student;
    }

    /**
     * Number of activities the block may list.
     *
     * @return int
     */
    private static function max_activities(): int {
        $configured = (int)get_config('local_dttutor', 'max_activities');
        return $configured > 0 ? $configured : self::DEFAULT_MAX_ACTIVITIES;
    }

    /**
     * The cached static block for this course and user, rebuilt when the course changes.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return string
     */
    private static function get_static_block(\stdClass $course, int $userid): string {
        $cache    = \cache::make('local_dttutor', 'course_knowledge');
        $cachekey = $course->id . '_' . $userid;

        $cached = $cache->get($cachekey);
        if (is_array($cached) && (int)($cached['cacherev'] ?? -1) === (int)$course->cacherev) {
            return (string)$cached['text'];
        }

        $text = self::build_static_block($course, $userid);
        $cache->set($cachekey, ['cacherev' => (int)$course->cacherev, 'text' => $text]);
        return $text;
    }

    /**
     * Build the course and activity part of the block.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return string
     */
    private static function build_static_block(\stdClass $course, int $userid): string {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $modinfo = get_fast_modinfo($course, $userid);
        $gradeitems = self::get_grade_items((int)$course->id);

        $lines = [];
        $lines[] = 'COURSE KNOWLEDGE (already retrieved for you — use it to answer directly '
            . 'whenever it contains the answer):';
        $lines[] = '- Course: ' . format_string($course->fullname) . ' (id ' . (int)$course->id . ')';

        $summary = trim(html_to_text((string)$course->summary, 0, false));
        if ($summary !== '') {
            $lines[] = '- Summary: ' . $summary;
        }

        $max = self::max_activities();
        $activitylines = [];
        $omitted = 0;

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->deletioninprogress || $cm->modname === 'label') {
                continue;
            }

            // An activity the user cannot open yet is announced with its condition and nothing else:
            // the student sees it greyed out on the course page and asks about it.
            $locked = !$cm->uservisible;
            if ($locked && !self::is_announced_as_locked($cm)) {
                continue;
            }

            if (count($activitylines) >= $max) {
                $omitted++;
                continue;
            }

            $activitylines[] = '  - ' . implode(' | ', self::describe_activity(
                $DB,
                $course,
                $modinfo,
                $cm,
                $locked,
                $gradeitems
            ));
        }

        if (!empty($activitylines)) {
            $lines[] = '- Activities (' . count($activitylines) . '):';
            $lines   = array_merge($lines, $activitylines);
        }
        if ($omitted > 0) {
            $lines[] = '- ' . $omitted . ' further activities are not listed here. Say so if the answer '
                . 'might be among them, and point the student at the course page.';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Whether an activity the user cannot open is still shown to them on the course page.
     *
     * @param \cm_info $cm
     * @return bool
     */
    private static function is_announced_as_locked(\cm_info $cm): bool {
        return $cm->is_visible_on_course_page() && trim((string)$cm->availableinfo) !== '';
    }

    /**
     * The parts describing one activity.
     *
     * @param \moodle_database $db
     * @param \stdClass $course
     * @param \course_modinfo $modinfo
     * @param \cm_info $cm
     * @param bool $locked Whether the user cannot open the activity yet.
     * @param array $gradeitems Grade items of the course indexed by module and instance.
     * @return string[]
     */
    private static function describe_activity(
        \moodle_database $db,
        \stdClass $course,
        \course_modinfo $modinfo,
        \cm_info $cm,
        bool $locked,
        array $gradeitems
    ): array {
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

        if ($locked) {
            // Nothing else is disclosed about an activity the user cannot open.
            $parts[] = 'not available yet: ' . trim(html_to_text((string)$cm->availableinfo, 0, false));
            return $parts;
        }

        $record = self::get_instance_record($db, $cm->modname, (int)$cm->instance);

        $dates = self::extract_dates($record);
        if ($dates !== '') {
            $parts[] = $dates;
        }

        $durations = self::extract_durations($record);
        if ($durations !== '') {
            $parts[] = $durations;
        }

        $item = $gradeitems[$cm->modname . '|' . (int)$cm->instance] ?? null;
        if ($item !== null && (float)$item->grademax > 0) {
            $parts[] = 'max grade ' . format_float((float)$item->grademax, (int)$item->get_decimals());
        }

        return $parts;
    }

    /**
     * The instance record of an activity, or null when it cannot be read.
     *
     * @param \moodle_database $db
     * @param string $modname
     * @param int $instance
     * @return \stdClass|null
     */
    private static function get_instance_record(\moodle_database $db, string $modname, int $instance): ?\stdClass {
        try {
            $record = $db->get_record($modname, ['id' => $instance]);
        } catch (\Throwable $e) {
            return null;
        }
        return $record ?: null;
    }

    /**
     * The dates configured for an activity.
     *
     * @param \stdClass|null $record
     * @return string
     */
    private static function extract_dates(?\stdClass $record): string {
        if ($record === null) {
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
     * The time the user has to complete an activity once started.
     *
     * @param \stdClass|null $record
     * @return string
     */
    private static function extract_durations(?\stdClass $record): string {
        if ($record === null) {
            return '';
        }

        $found = [];
        foreach (self::DURATION_FIELDS as $field => $label) {
            if (!empty($record->$field) && (int)$record->$field > 0) {
                $found[] = $label . ': ' . format_time((int)$record->$field);
            }
        }
        return empty($found) ? '' : implode(', ', $found);
    }

    /**
     * How far the user has got with the course: what they submitted and what they completed.
     *
     * Never cached: unlike the structure of the course, this changes with every submission the
     * user makes, and an answer about what is left to hand in has to be current.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return string
     */
    private static function get_progress_block(\stdClass $course, int $userid): string {
        global $DB;

        if ($userid <= 0) {
            return '';
        }

        try {
            $modinfo = get_fast_modinfo($course, $userid);
        } catch (\Throwable $e) {
            return '';
        }
        $completion = new \completion_info($course);

        $lines = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || $cm->deletioninprogress || $cm->modname === 'label') {
                continue;
            }
            $states = self::extract_progress($DB, $cm, $completion, $userid);
            if ($states !== '') {
                $lines[] = '  - ' . format_string($cm->name) . ': ' . $states;
            }
        }

        if (empty($lines)) {
            return '';
        }

        return "\nYOUR PROGRESS (current user):\n" . implode("\n", $lines) . "\n";
    }

    /**
     * How far the user has got with one activity: submitted, completed, or nothing worth saying.
     *
     * @param \moodle_database $db
     * @param \cm_info $cm
     * @param \completion_info $completion
     * @param int $userid
     * @return string
     */
    private static function extract_progress(
        \moodle_database $db,
        \cm_info $cm,
        \completion_info $completion,
        int $userid
    ): string {
        $states = [];

        if ($cm->modname === 'assign') {
            $submitted = $db->record_exists('assign_submission', [
                'assignment' => (int)$cm->instance,
                'userid' => $userid,
                'status' => 'submitted',
                'latest' => 1,
            ]);
            $states[] = $submitted ? 'submitted' : 'not submitted';
        }

        if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
            try {
                $data = $completion->get_data($cm, false, $userid);
                $states[] = in_array((int)$data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)
                    ? 'completed'
                    : 'not completed';
            } catch (\Throwable $e) {
                unset($e);
            }
        }

        return empty($states) ? '' : implode(', ', $states);
    }

    /**
     * Grade items of the course, indexed by module name and instance.
     *
     * Read once for the whole course instead of once per activity and message.
     *
     * @param int $courseid
     * @return array
     */
    private static function get_grade_items(int $courseid): array {
        $items = [];
        try {
            $all = \grade_item::fetch_all(['courseid' => $courseid, 'itemtype' => 'mod']);
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($all ?: [] as $item) {
            $items[$item->itemmodule . '|' . (int)$item->iteminstance] = $item;
        }
        return $items;
    }

    /**
     * Build the part of the block with the grades of the user.
     *
     * @param \stdClass $course
     * @param int $userid
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

        $gradeitems = self::get_grade_items((int)$course->id);
        $usergrades = self::get_user_grades((int)$course->id, $userid);
        $seehidden = has_capability('moodle/grade:viewhidden', \context_course::instance((int)$course->id), $userid);

        $gradelines = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || $cm->deletioninprogress || $cm->modname === 'label') {
                continue;
            }
            $item = $gradeitems[$cm->modname . '|' . (int)$cm->instance] ?? null;
            if ($item === null) {
                continue;
            }
            $grade = self::describe_grade($item, $usergrades[(int)$item->id] ?? null, $seehidden);
            if ($grade !== '') {
                $gradelines[] = '  - ' . format_string($cm->name) . ': ' . $grade;
            }
        }

        if (empty($gradelines)) {
            return '';
        }

        $coursegrade = self::extract_course_grade((int)$course->id, $userid, $usergrades, $seehidden);
        $header = "\nYOUR GRADES (current student, user id {$userid}):";
        if ($coursegrade !== '') {
            $header .= "\n  - Course total: " . $coursegrade;
        }

        return $header . "\n" . implode("\n", $gradelines) . "\n";
    }

    /**
     * Every grade of the user in the course, indexed by grade item id.
     *
     * Read in one go instead of once per activity and message.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    private static function get_user_grades(int $courseid, int $userid): array {
        global $DB;

        $sql = "SELECT gg.*
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE gi.courseid = :courseid
                   AND gg.userid = :userid";

        $grades = [];
        try {
            $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($records as $record) {
            $grades[(int)$record->itemid] = new \grade_grade($record, false);
        }
        return $grades;
    }

    /**
     * Describe one grade of the user, or nothing when the user is not meant to see it yet.
     *
     * A grade hidden by the teacher, or one still held by the marking workflow, never leaves the
     * site: the student would learn through the chat a mark that is not in their gradebook.
     *
     * @param \grade_item $item
     * @param \grade_grade|null $grade
     * @param bool $seehidden Whether the user may see hidden grades.
     * @return string
     */
    private static function describe_grade(\grade_item $item, ?\grade_grade $grade, bool $seehidden): string {
        if ($grade === null) {
            return '';
        }

        // Handing over the item that is already in memory: left to itself, a grade fetches its own
        // item from the database, which would be one query per gradable activity and per message.
        $grade->grade_item = $item;

        if (!$seehidden && $grade->is_hidden()) {
            return '';
        }
        if ($grade->finalgrade === null) {
            return 'not graded yet';
        }

        $decimals = (int)$item->get_decimals();
        $value    = format_float((float)$grade->finalgrade, $decimals);
        if ((float)$item->grademax > 0) {
            return $value . ' / ' . format_float((float)$item->grademax, $decimals);
        }
        return $value;
    }

    /**
     * The course total of the user, when they are meant to see it.
     *
     * @param int $courseid
     * @param int $userid
     * @param array $usergrades Grades of the user indexed by grade item id.
     * @param bool $seehidden
     * @return string
     */
    private static function extract_course_grade(
        int $courseid,
        int $userid,
        array $usergrades,
        bool $seehidden
    ): string {
        try {
            $item = \grade_item::fetch_course_item($courseid);
        } catch (\Throwable $e) {
            return '';
        }
        if (!$item) {
            return '';
        }

        $grade = $usergrades[(int)$item->id] ?? null;
        if ($grade === null || $grade->finalgrade === null) {
            return '';
        }

        $grade->grade_item = $item;
        if (!$seehidden && $grade->is_hidden()) {
            return '';
        }

        $decimals = (int)$item->get_decimals();
        $value    = format_float((float)$grade->finalgrade, $decimals);
        if ((float)$item->grademax > 0) {
            return $value . ' / ' . format_float((float)$item->grademax, $decimals);
        }
        return $value;
    }
}
