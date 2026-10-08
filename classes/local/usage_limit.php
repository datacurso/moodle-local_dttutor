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
 * How many questions a person, and a course, may ask the tutor, and how many at once.
 *
 * Each question costs a call to a third party and holds a PHP worker for as long as the answer
 * streams. The limit the AI service applies is configured outside this plugin and is off unless
 * an administrator switches it on, so on its own the tutor accepted as many questions as anyone
 * cared to send (Mindfree DTT-SEC-006-R1). This counts them here, before anything leaves the site:
 *
 * - questions of one person in one course, per window of time;
 * - questions of the whole course, per window of time, so that a class cannot spend the budget
 *   of the site between them;
 * - answers being streamed for one person at the same time, since the session lock is released
 *   before the answer starts and nothing else would stop parallel requests.
 *
 * The windows are fixed (they start at a multiple of their length), which is enough for a
 * budget and cheap to keep: one counter per window, held in the application cache.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class usage_limit {
    /** @var int Questions one person may ask in one course per window, unless configured otherwise. */
    public const DEFAULT_USER_REQUESTS = 30;

    /** @var int Questions a whole course may ask per window, unless configured otherwise. */
    public const DEFAULT_COURSE_REQUESTS = 600;

    /** @var int Length of the window in minutes, unless configured otherwise. */
    public const DEFAULT_WINDOW_MINUTES = 10;

    /** @var int Answers one person may have streaming at once, unless configured otherwise. */
    public const DEFAULT_CONCURRENT = 2;

    /** @var int Longest window that can be configured, so that the counters outlive their window. */
    public const MAX_WINDOW_MINUTES = 1440;

    /**
     * @var int Seconds after which a slot is freed even if the request that held it died.
     *
     * The longest an answer can take is the timeout of the call to the model (180 seconds) plus
     * the work before and after it.
     */
    public const SLOT_MAX_LIFETIME = 300;

    /** @var int Seconds to wait for the counters of a course while another request updates them. */
    private const COUNTER_LOCK_TIMEOUT = 5;

    /** @var string Type of the lock factory the slots and the counters use. */
    private const LOCK_TYPE = 'local_dttutor_usage';

    /**
     * Count a question and take a streaming slot for it, or refuse it.
     *
     * The slot is released when the request ends, whether it ended well or not.
     *
     * @param int $userid Person asking.
     * @param int $courseid Course the question is about.
     * @param int $now Current time.
     * @return \core\lock\lock|null The slot held for the answer, or null when concurrency is not limited.
     * @throws usage_limit_exceeded When a limit has been reached.
     */
    public static function acquire(int $userid, int $courseid, int $now): ?\core\lock\lock {
        $factory = \core\lock\lock_config::get_lock_factory(self::LOCK_TYPE);

        $slot = self::take_slot($factory, $userid);
        try {
            self::count($factory, $userid, $courseid, $now);
        } catch (\Throwable $e) {
            $slot?->release();
            throw $e;
        }

        if ($slot !== null) {
            \core_shutdown_manager::register_function([$slot, 'release']);
        }
        return $slot;
    }

    /**
     * Questions one person may ask in one course per window; 0 means no limit.
     *
     * @return int
     */
    public static function user_requests(): int {
        return self::configured('ratelimit_user_requests', self::DEFAULT_USER_REQUESTS);
    }

    /**
     * Questions a whole course may ask per window; 0 means no limit.
     *
     * @return int
     */
    public static function course_requests(): int {
        return self::configured('ratelimit_course_requests', self::DEFAULT_COURSE_REQUESTS);
    }

    /**
     * Answers one person may have streaming at once; 0 means no limit.
     *
     * @return int
     */
    public static function concurrent(): int {
        return self::configured('ratelimit_concurrent', self::DEFAULT_CONCURRENT);
    }

    /**
     * Length of the window in seconds.
     *
     * @return int
     */
    public static function window_seconds(): int {
        $minutes = self::configured('ratelimit_window_minutes', self::DEFAULT_WINDOW_MINUTES);
        $minutes = max(1, min($minutes, self::MAX_WINDOW_MINUTES));
        return $minutes * MINSECS;
    }

    /**
     * Take one of the streaming slots of the person, without waiting for one.
     *
     * @param \core\lock\lock_factory $factory
     * @param int $userid
     * @return \core\lock\lock|null The slot, or null when concurrency is not limited.
     * @throws usage_limit_exceeded When every slot is taken.
     */
    private static function take_slot(\core\lock\lock_factory $factory, int $userid): ?\core\lock\lock {
        $slots = self::concurrent();
        if ($slots <= 0) {
            return null;
        }

        for ($i = 1; $i <= $slots; $i++) {
            $lock = $factory->get_lock("slot_{$userid}_{$i}", 0, self::SLOT_MAX_LIFETIME);
            if ($lock !== false) {
                return $lock;
            }
        }
        throw new usage_limit_exceeded(usage_limit_exceeded::SCOPE_CONCURRENT, 0);
    }

    /**
     * Add the question to the counters of the person and of the course, unless one is full.
     *
     * @param \core\lock\lock_factory $factory
     * @param int $userid
     * @param int $courseid
     * @param int $now
     * @throws usage_limit_exceeded When a counter is full, or cannot be read in time.
     */
    private static function count(\core\lock\lock_factory $factory, int $userid, int $courseid, int $now): void {
        $userlimit = self::user_requests();
        $courselimit = self::course_requests();
        if ($userlimit <= 0 && $courselimit <= 0) {
            return;
        }

        $window = self::window_seconds();
        $start = intdiv($now, $window) * $window;
        $resetat = $start + $window;

        // Read and written under a lock: two questions arriving together must not both see the
        // last free place. If the lock cannot be had, the question is refused rather than let
        // through uncounted.
        $lock = $factory->get_lock("count_{$courseid}", self::COUNTER_LOCK_TIMEOUT, MINSECS);
        if ($lock === false) {
            throw new usage_limit_exceeded(usage_limit_exceeded::SCOPE_BUSY, 0);
        }

        try {
            $cache = \cache::make('local_dttutor', 'usage');
            $userkey = "u_{$userid}_{$courseid}_{$start}";
            $coursekey = "c_{$courseid}_{$start}";
            $usercount = (int)$cache->get($userkey);
            $coursecount = (int)$cache->get($coursekey);

            if ($userlimit > 0 && $usercount >= $userlimit) {
                throw new usage_limit_exceeded(usage_limit_exceeded::SCOPE_USER, $resetat);
            }
            if ($courselimit > 0 && $coursecount >= $courselimit) {
                throw new usage_limit_exceeded(usage_limit_exceeded::SCOPE_COURSE, $resetat);
            }

            $cache->set($userkey, $usercount + 1);
            $cache->set($coursekey, $coursecount + 1);
        } finally {
            $lock->release();
        }
    }

    /**
     * A limit as the site configures it, or its default when the site never set one.
     *
     * @param string $name Name of the setting.
     * @param int $default Value used when the setting was never saved.
     * @return int
     */
    private static function configured(string $name, int $default): int {
        $value = get_config('local_dttutor', $name);
        if ($value === false || $value === '') {
            return $default;
        }
        return max(0, (int)$value);
    }
}
