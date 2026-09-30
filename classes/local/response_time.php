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
 * How long an answer took, step by step, against the time the site committed to.
 *
 * An answer is made of steps that belong to different owners: opening the conversation and
 * storing the messages are ours, generating the text is the model's. Timing them together says
 * that the tutor is slow; timing them apart says which part is, which is the only form of the
 * measurement anyone can act on.
 *
 * Nothing but durations is recorded here: no message, no identifier of a person.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_time {
    /** @var int Seconds an answer may take before the site considers it late. */
    public const DEFAULT_TARGET_SECONDS = 20;

    /** @var array<string, int> Milliseconds spent on each step, in the order they happened. */
    private array $steps = [];

    /** @var float Start of the step being timed. */
    private float $started;

    /** @var float Start of the whole answer. */
    private float $opened;

    /**
     * Start timing an answer.
     */
    public function __construct() {
        $this->opened = microtime(true);
        $this->started = $this->opened;
    }

    /**
     * Close the step that was running and open the next one.
     *
     * @param string $step Name of the step that just finished.
     */
    public function step(string $step): void {
        $now = microtime(true);
        $this->steps[$step] = (int)round(($now - $this->started) * 1000);
        $this->started = $now;
    }

    /**
     * Milliseconds since the answer started.
     *
     * @return int
     */
    public function elapsed(): int {
        return (int)round((microtime(true) - $this->opened) * 1000);
    }

    /**
     * The time the site committed to, in seconds, or zero when it committed to none.
     *
     * @return int
     */
    public static function target_seconds(): int {
        $configured = get_config('local_dttutor', 'response_target_seconds');
        if ($configured === false) {
            return self::DEFAULT_TARGET_SECONDS;
        }
        return max(0, (int)$configured);
    }

    /**
     * Whether an answer of this length is late for the site.
     *
     * @param int $elapsed Milliseconds the answer took.
     * @return bool False whenever the site committed to no time at all.
     */
    public static function is_late(int $elapsed): bool {
        $target = self::target_seconds();
        return $target > 0 && $elapsed > $target * 1000;
    }

    /**
     * Record what the answer took, step by step.
     *
     * An answer within the committed time is recorded for developers, so a site can watch its own
     * numbers. One that is late is recorded as a failure, so that it reaches the error log of a
     * production site the way other failures do and someone finds out without being told.
     */
    public function record(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/dttutor/lib.php');

        $total = $this->elapsed();
        $late = self::is_late($total);

        \local_dttutor_log(
            $late ? 'RESPONSE_LATE' : 'RESPONSE_TIME',
            $this->steps + ['total' => $total, 'target' => self::target_seconds()],
            $late
        );
    }

    /**
     * The steps measured so far, in milliseconds.
     *
     * @return array<string, int>
     */
    public function steps(): array {
        return $this->steps;
    }
}
