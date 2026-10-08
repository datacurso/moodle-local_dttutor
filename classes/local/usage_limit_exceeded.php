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
 * A question refused because a usage limit of the tutor has been reached.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usage_limit_exceeded extends \moodle_exception {
    /** @var string The person asked too many questions in the course in this window. */
    public const SCOPE_USER = 'user';

    /** @var string The course asked too many questions in this window. */
    public const SCOPE_COURSE = 'course';

    /** @var string The person already has as many answers streaming as allowed. */
    public const SCOPE_CONCURRENT = 'concurrent';

    /** @var string The counters could not be read in time. */
    public const SCOPE_BUSY = 'busy';

    /** @var int Seconds to suggest waiting when there is no window to wait for. */
    private const SHORT_WAIT = 10;

    /** @var string Which limit was reached: one of the SCOPE_* constants. */
    public string $scope;

    /** @var int When asking again may succeed. */
    public int $retryat;

    /**
     * Build the refusal with the message the person is shown.
     *
     * @param string $scope Which limit was reached: one of the SCOPE_* constants.
     * @param int $resetat When the window that is full ends, or 0 when the wait is a short one.
     */
    public function __construct(string $scope, int $resetat) {
        $this->scope = $scope;
        $this->retryat = $resetat > 0 ? $resetat : time() + self::SHORT_WAIT;

        if ($scope === self::SCOPE_USER || $scope === self::SCOPE_COURSE) {
            $when = userdate($this->retryat, get_string('strftimetime', 'langconfig'));
            parent::__construct('error_usage_limit_' . $scope, 'local_dttutor', '', $when);
            return;
        }
        parent::__construct('error_usage_limit_busy', 'local_dttutor');
    }

    /**
     * Seconds until asking again may succeed, for the Retry-After header.
     *
     * @param int $now Current time.
     * @return int
     */
    public function retry_after(int $now): int {
        return max(1, $this->retryat - $now);
    }
}
