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

namespace local_dttutor\proxy;

/**
 * The course total of a user, as their own gradebook shows it.
 *
 * The stored course total aggregates every grade, hidden ones included. Handing it over as it is
 * lets a student work out a hidden mark from the difference with the visible ones, so the total
 * follows the course setting "Hide totals if they contain hidden items" exactly like the user
 * report does: hidden, recalculated without the hidden items, or shown as it is.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_total {
    /**
     * The course total the user is meant to see, or null when it must not be shown.
     *
     * @param \grade_item $courseitem The course grade item.
     * @param int $userid The user the total belongs to.
     * @param \grade_grade[] $usergrades Grades of the user indexed by grade item id.
     * @param bool $seehidden Whether the user may see hidden grades.
     * @return array|null With 'grade' and 'grademax', or null when nothing is to be shown.
     */
    public static function as_seen_by(\grade_item $courseitem, int $userid, array $usergrades, bool $seehidden): ?array {
        $grade = $usergrades[(int)$courseitem->id] ?? null;
        if ($grade === null || $grade->finalgrade === null) {
            return null;
        }

        $grade->grade_item = $courseitem;
        if (!$seehidden && $grade->is_hidden()) {
            return null;
        }

        $total = ['grade' => (float)$grade->finalgrade, 'grademax' => (float)$grade->get_grade_max()];
        if ($seehidden || self::mode((int)$courseitem->courseid) === GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN) {
            return $total;
        }

        return self::without_hidden_items($courseitem, $userid, $usergrades, $total);
    }

    /**
     * Apply the hiding rules of the gradebook to the total.
     *
     * Mirrors grade_report::blank_hidden_total_and_adjust_bounds(), which is not reachable
     * without building a whole report.
     *
     * @param \grade_item $courseitem
     * @param int $userid
     * @param \grade_grade[] $usergrades
     * @param array $total The stored total, with 'grade' and 'grademax'.
     * @return array|null
     */
    private static function without_hidden_items(\grade_item $courseitem, int $userid, array $usergrades, array $total): ?array {
        $courseid = (int)$courseitem->courseid;
        $items = \grade_item::fetch_all(['courseid' => $courseid]) ?: [];
        $grades = [];
        $anyhidden = false;
        foreach ($items as $itemid => $item) {
            $grades[$itemid] = $usergrades[$itemid] ?? new \grade_grade(['userid' => $userid, 'itemid' => $itemid], false);
            $grades[$itemid]->grade_item = $item;
            $anyhidden = $anyhidden || ($itemid !== (int)$courseitem->id && $grades[$itemid]->is_hidden());
        }

        // Working out what the hiding alters reads the dependencies item by item, so it is
        // only done when something is hidden from the user: otherwise the total is unchanged.
        if (!$anyhidden) {
            return $total;
        }

        $affected = \grade_grade::get_hiding_affected($grades, $items);
        $id = (int)$courseitem->id;
        $hidetotals = self::mode($courseid) === GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN;

        if (array_key_exists($id, $affected['altered'])) {
            if ($hidetotals) {
                return null;
            }
            $total['grade'] = $affected['altered'][$id];
        } else if (array_key_exists($id, $affected['unknowngrades'])) {
            if ($hidetotals) {
                return null;
            }
            $total['grade'] = $affected['unknowngrades'][$id];
        }

        if (array_key_exists($id, $affected['alteredgrademax'])) {
            $total['grademax'] = (float)$affected['alteredgrademax'][$id];
        }

        return $total['grade'] === null ? null : $total;
    }

    /**
     * The "Hide totals if they contain hidden items" setting of the course.
     *
     * @param int $courseid
     * @return int One of the GRADE_REPORT_*_TOTAL_IF_CONTAINS_HIDDEN constants.
     */
    private static function mode(int $courseid): int {
        global $CFG;
        return (int)grade_get_setting(
            $courseid,
            'report_user_showtotalsifcontainhidden',
            $CFG->grade_report_user_showtotalsifcontainhidden ?? GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN
        );
    }
}
