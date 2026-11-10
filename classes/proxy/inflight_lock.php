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

use core\lock\lock;
use core\lock\lock_config;
use core\lock\lock_factory;

/**
 * One question in flight per user and course.
 *
 * Every question costs a call to the AI service. Without this guard a user could fire as many
 * questions in parallel as the browser allows (DTT-SEC-006-R1). The chat proxy takes this lock
 * before it contacts any service and keeps it until the answer has been streamed and stored, so a
 * second question of the same user in the same course is refused while the first one is running.
 * Other users, and the same user in another course, hold a different lock and are not affected.
 *
 * The lock is taken without waiting. The database lock factories release it when the request
 * ends, even when the process dies; the row-based fallback lets it expire after MAX_LIFETIME.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inflight_lock {
    /** @var string Lock type, used by Moodle to namespace the resources. */
    private const LOCK_TYPE = 'local_dttutor_chat';

    /**
     * @var int Seconds after which a lock left behind by a dead process expires (row-based factory only).
     *
     * Longer than the slowest answer: the upstream call alone may take 180 seconds.
     */
    public const MAX_LIFETIME = 300;

    /**
     * @var lock_factory|null Factory shared by the whole request.
     *
     * Shared on purpose: the factories refuse a second lock on a resource they already hold, but
     * two factories on the same database connection would each grant it.
     */
    private static ?lock_factory $factory = null;

    /**
     * Take the lock of a user in a course, without waiting.
     *
     * @param int $userid User asking the question.
     * @param int $courseid Course the question is asked in.
     * @return lock|null The lock, or null when that user already has a question in flight in that course.
     */
    public static function acquire(int $userid, int $courseid): ?lock {
        $lock = self::factory()->get_lock(self::resource($userid, $courseid), 0, self::MAX_LIFETIME);
        return $lock === false ? null : $lock;
    }

    /**
     * Name of the locked resource for a user in a course.
     *
     * @param int $userid
     * @param int $courseid
     * @return string
     */
    public static function resource(int $userid, int $courseid): string {
        return 'user' . $userid . '_course' . $courseid;
    }

    /**
     * Course id the client asked for, before the request guard validates it.
     *
     * The lock is taken before the guard records the use of the tutor, so that a refused
     * question is not logged as one. The value only names the lock: an invalid course is
     * refused by the guard right after.
     *
     * @param array $input The decoded chat proxy body.
     * @return int
     */
    public static function course_from_input(array $input): int {
        $context = is_array($input['context'] ?? null) ? $input['context'] : [];
        return max(0, (int)($context['course_id'] ?? 0));
    }

    /**
     * Lock factory of this request.
     *
     * @return lock_factory
     */
    private static function factory(): lock_factory {
        if (self::$factory === null) {
            self::$factory = lock_config::get_lock_factory(self::LOCK_TYPE);
        }
        return self::$factory;
    }
}
