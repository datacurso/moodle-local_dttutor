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
 * Tests for the usage limits applied before a question reaches the AI service (Mindfree DTT-SEC-006-R1).
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\usage_limit
 * @covers     \local_dttutor\local\usage_limit_exceeded
 */
final class usage_limit_test extends \advanced_testcase {
    /** @var int A fixed time at the start of a ten-minute window. */
    private const NOW = 1767225600;

    /** @var \core\lock\lock[] Slots taken by a test, released when it ends. */
    private array $slots = [];

    /**
     * Reset the configuration after every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Release the slots a test kept, so that no lock outlives it.
     */
    protected function tearDown(): void {
        foreach ($this->slots as $slot) {
            $slot->release();
        }
        $this->slots = [];
        parent::tearDown();
    }

    /**
     * Configure the limits.
     *
     * @param int $user Questions per user and course.
     * @param int $course Questions per course.
     * @param int $concurrent Answers at once per user.
     * @param int $minutes Length of the window.
     */
    private function limits(int $user, int $course, int $concurrent, int $minutes = 10): void {
        set_config('ratelimit_user_requests', $user, 'local_dttutor');
        set_config('ratelimit_course_requests', $course, 'local_dttutor');
        set_config('ratelimit_concurrent', $concurrent, 'local_dttutor');
        set_config('ratelimit_window_minutes', $minutes, 'local_dttutor');
    }

    /**
     * Take the slots from a lock factory that is not reentrant within one process.
     *
     * Every request of a site holds its own connection, so the advisory locks of MySQL and
     * PostgreSQL tell two requests apart. A test runs every request in one process and one
     * connection, where those locks are reentrant; file locks are not, so they stand in for the
     * second request.
     */
    private function use_separate_request_locks(): void {
        global $CFG;
        $CFG->lock_factory = '\\core\\lock\\file_lock_factory';
    }

    /**
     * Ask a question, keeping the slot until the test ends.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $now
     */
    private function ask(int $userid, int $courseid, int $now = self::NOW): void {
        $slot = usage_limit::acquire($userid, $courseid, $now);
        if ($slot !== null) {
            $this->slots[] = $slot;
        }
    }

    /**
     * Ask a question and return the refusal.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $now
     * @return usage_limit_exceeded
     */
    private function refusal(int $userid, int $courseid, int $now = self::NOW): usage_limit_exceeded {
        try {
            $this->ask($userid, $courseid, $now);
        } catch (usage_limit_exceeded $e) {
            return $e;
        }
        $this->fail('The question was expected to be refused.');
    }

    /**
     * DTT-SEC-006-R1: a fresh site limits questions without any configuration of the provider.
     */
    public function test_the_limits_apply_by_default(): void {
        $this->assertSame(usage_limit::DEFAULT_USER_REQUESTS, usage_limit::user_requests());
        $this->assertSame(usage_limit::DEFAULT_COURSE_REQUESTS, usage_limit::course_requests());
        $this->assertSame(usage_limit::DEFAULT_CONCURRENT, usage_limit::concurrent());
        $this->assertSame(usage_limit::DEFAULT_WINDOW_MINUTES * MINSECS, usage_limit::window_seconds());
        $this->assertGreaterThan(0, usage_limit::user_requests());
    }

    /**
     * DTT-SEC-006-R1: repeated questions of one person are refused once the window is full.
     */
    public function test_a_person_is_refused_past_the_limit_of_the_window(): void {
        $this->limits(3, 0, 0);

        for ($i = 0; $i < 3; $i++) {
            $this->ask(5, 7);
        }
        $refusal = $this->refusal(5, 7);

        $this->assertSame(usage_limit_exceeded::SCOPE_USER, $refusal->scope);
        $this->assertSame('error_usage_limit_user', $refusal->errorcode);
        $this->assertSame(self::NOW + 10 * MINSECS, $refusal->retryat);
        $this->assertSame(10 * MINSECS, $refusal->retry_after(self::NOW));
    }

    /**
     * DTT-SEC-006-R1: the limit of a person counts per course, and other people are not affected.
     */
    public function test_the_limit_of_a_person_is_kept_per_course(): void {
        $this->limits(2, 0, 0);

        $this->ask(5, 7);
        $this->ask(5, 7);
        $this->ask(5, 8);
        $this->ask(6, 7);

        $this->assertSame(usage_limit_exceeded::SCOPE_USER, $this->refusal(5, 7)->scope);
    }

    /**
     * DTT-SEC-006-R1: a new window starts the count again.
     */
    public function test_a_new_window_starts_the_count_again(): void {
        $this->limits(1, 0, 0);

        $this->ask(5, 7);
        $this->refusal(5, 7, self::NOW + 9 * MINSECS);
        $this->ask(5, 7, self::NOW + 10 * MINSECS);

        $this->assertSame(usage_limit_exceeded::SCOPE_USER, $this->refusal(5, 7, self::NOW + 10 * MINSECS)->scope);
    }

    /**
     * DTT-SEC-006-R1: a whole course shares one budget per window.
     */
    public function test_a_course_is_refused_past_its_own_limit(): void {
        $this->limits(0, 3, 0);

        $this->ask(1, 7);
        $this->ask(2, 7);
        $this->ask(3, 7);
        $refusal = $this->refusal(4, 7);

        $this->assertSame(usage_limit_exceeded::SCOPE_COURSE, $refusal->scope);
        // Another course keeps its own budget.
        $this->ask(4, 8);
    }

    /**
     * DTT-SEC-006-R1: a refused question is not counted.
     */
    public function test_a_refused_question_does_not_spend_the_budget(): void {
        $this->limits(1, 2, 0);

        $this->ask(5, 7);
        $this->refusal(5, 7);
        $this->refusal(5, 7);

        // Only one of the three questions reached the course counter.
        $this->ask(6, 7);
        $this->assertSame(usage_limit_exceeded::SCOPE_COURSE, $this->refusal(9, 7)->scope);
    }

    /**
     * DTT-SEC-006-R1: answers streaming in parallel for one person are bounded.
     */
    public function test_a_person_cannot_stream_more_answers_at_once_than_allowed(): void {
        $this->use_separate_request_locks();
        $this->limits(0, 0, 2);

        $this->ask(5, 7);
        $this->ask(5, 8);
        $refusal = $this->refusal(5, 7);

        $this->assertSame(usage_limit_exceeded::SCOPE_CONCURRENT, $refusal->scope);
        $this->assertSame('error_usage_limit_busy', $refusal->errorcode);
        $this->assertGreaterThan(0, $refusal->retry_after(time()));
        // Somebody else is not affected.
        $this->ask(6, 7);
    }

    /**
     * DTT-SEC-006-R1: a slot is freed when its answer ends.
     */
    public function test_a_finished_answer_frees_its_slot(): void {
        $this->use_separate_request_locks();
        $this->limits(0, 0, 1);

        $slot = usage_limit::acquire(5, 7, self::NOW);
        $this->refusal(5, 7);
        $slot->release();

        $this->ask(5, 7);
    }

    /**
     * DTT-SEC-006-R1: a question refused by the counters gives its slot back.
     */
    public function test_a_question_refused_by_a_counter_gives_its_slot_back(): void {
        $this->use_separate_request_locks();
        $this->limits(1, 0, 1);

        $slot = usage_limit::acquire(5, 7, self::NOW);
        $slot->release();
        $this->assertSame(usage_limit_exceeded::SCOPE_USER, $this->refusal(5, 7)->scope);

        // Were the slot of the refused question still held, this would be refused as concurrent.
        $this->assertSame(usage_limit_exceeded::SCOPE_USER, $this->refusal(5, 7)->scope);
    }

    /**
     * DTT-SEC-006-R1: a site may remove every limit on purpose.
     */
    public function test_zero_removes_a_limit(): void {
        $this->limits(0, 0, 0);

        for ($i = 0; $i < 50; $i++) {
            $this->assertNull(usage_limit::acquire(5, 7, self::NOW));
        }
    }

    /**
     * DTT-SEC-006-R1: the window stays between one minute and one day.
     */
    public function test_the_window_is_bounded(): void {
        $this->limits(1, 0, 0, 0);
        $this->assertSame(MINSECS, usage_limit::window_seconds());

        $this->limits(1, 0, 0, 100000);
        $this->assertSame(usage_limit::MAX_WINDOW_MINUTES * MINSECS, usage_limit::window_seconds());
    }

    /**
     * DTT-SEC-006-R1: the person is told when they may ask again.
     */
    public function test_the_refusal_says_when_to_ask_again(): void {
        $this->limits(1, 0, 0);
        $this->ask(5, 7);

        $refusal = $this->refusal(5, 7);

        $when = userdate(self::NOW + 10 * MINSECS, get_string('strftimetime', 'langconfig'));
        $this->assertSame(get_string('error_usage_limit_user', 'local_dttutor', $when), $refusal->getMessage());
    }
}
