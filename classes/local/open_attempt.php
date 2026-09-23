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

namespace local_dttutor\local;

/**
 * Whether someone is in the middle of sitting a quiz right now.
 *
 * The tutor steps aside while a quiz is being taken. An attempt that is merely open does not
 * count: a quiz with no time limit keeps its attempt open for as long as nobody submits it, and
 * a practice attempt forgotten weeks ago must not cost a student the tutor for the rest of the
 * course. What counts is an attempt that is still running.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class open_attempt {
    /**
     * How long after the last move an attempt with no deadline is still taken as being sat.
     *
     * Only reached by a quiz with neither a time limit nor a closing date, where nothing else
     * says when the sitting ends.
     */
    public const STILL_SITTING = 3 * HOURSECS;

    /** @var string[] States of an attempt that has not been handed in. */
    private const OPEN_STATES = ['inprogress', 'overdue'];

    /**
     * Whether the user is sitting a quiz of this course at this moment.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public static function is_being_sat(int $courseid, int $userid): bool {
        global $DB;

        if ($courseid <= 1 || $userid <= 0) {
            return false;
        }

        [$insql, $inparams] = $DB->get_in_or_equal(self::OPEN_STATES, SQL_PARAMS_NAMED, 'state');

        $sql = "SELECT qa.id, qa.timestart, qa.timemodified,
                       q.timelimit, q.timeclose, q.graceperiod, q.overduehandling
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                 WHERE q.course = :courseid
                   AND qa.userid = :userid
                   AND qa.preview = 0
                   AND qa.state {$insql}";

        try {
            $attempts = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid] + $inparams);
        } catch (\Throwable $e) {
            // The tutor does not go silent because a query failed.
            return false;
        }

        $now = time();
        foreach ($attempts as $attempt) {
            if (self::is_running($attempt, $now)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether one attempt is still being sat.
     *
     * @param \stdClass $attempt The attempt joined with the timing of its quiz.
     * @param int $now
     * @return bool
     */
    private static function is_running(\stdClass $attempt, int $now): bool {
        $timelimit = (int)$attempt->timelimit;
        if ($timelimit > 0) {
            $grace = (string)$attempt->overduehandling === 'graceperiod' ? (int)$attempt->graceperiod : 0;
            return $now < (int)$attempt->timestart + $timelimit + $grace;
        }

        $timeclose = (int)$attempt->timeclose;
        if ($timeclose > 0) {
            return $now < $timeclose;
        }

        // Nothing says when this sitting ends, so recent activity is what tells them apart.
        return $now - (int)$attempt->timemodified < self::STILL_SITTING;
    }
}
