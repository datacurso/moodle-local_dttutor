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
 * Tests for the one-question-in-flight lock of the chat proxy (DTT-SEC-006-R1).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\inflight_lock
 */
final class inflight_lock_test extends \advanced_testcase {
    /**
     * Two simultaneous questions of the same user in the same course: one goes on, one is refused.
     */
    public function test_second_question_of_the_same_user_and_course_is_refused(): void {
        $first = inflight_lock::acquire(10, 20);
        $second = inflight_lock::acquire(10, 20);

        $this->assertNotNull($first, 'The first question must be answered.');
        $this->assertNull($second, 'A second question while the first is in flight must be refused.');

        $first->release();
    }

    /**
     * Once the answer ends, the same user can ask again in that course.
     */
    public function test_the_user_can_ask_again_once_the_answer_ends(): void {
        $first = inflight_lock::acquire(10, 20);
        $first->release();

        $again = inflight_lock::acquire(10, 20);
        $this->assertNotNull($again);
        $again->release();
    }

    /**
     * Another user in the same course is not affected by a question in flight.
     */
    public function test_another_user_is_not_affected(): void {
        $mine = inflight_lock::acquire(10, 20);
        $theirs = inflight_lock::acquire(11, 20);

        $this->assertNotNull($mine);
        $this->assertNotNull($theirs);

        $mine->release();
        $theirs->release();
    }

    /**
     * The same user in another course is not affected by a question in flight.
     */
    public function test_the_same_user_in_another_course_is_not_affected(): void {
        $here = inflight_lock::acquire(10, 20);
        $there = inflight_lock::acquire(10, 21);

        $this->assertNotNull($here);
        $this->assertNotNull($there);

        $here->release();
        $there->release();
    }

    /**
     * Each user and course pair names its own resource, so ids cannot run into each other.
     */
    public function test_resources_do_not_collide(): void {
        $this->assertNotSame(inflight_lock::resource(1, 23), inflight_lock::resource(12, 3));
        $this->assertSame(inflight_lock::resource(10, 20), inflight_lock::resource(10, 20));
    }

    /**
     * The course id is read from the request body, anything malformed naming course 0.
     *
     * @dataProvider course_from_input_provider
     * @param array $input Decoded request body.
     * @param int $expected Course id expected.
     */
    public function test_course_from_input(array $input, int $expected): void {
        $this->assertSame($expected, inflight_lock::course_from_input($input));
    }

    /**
     * Request bodies and the course id they name.
     *
     * @return array
     */
    public static function course_from_input_provider(): array {
        return [
            'valid' => [['context' => ['course_id' => 7]], 7],
            'numeric string' => [['context' => ['course_id' => '7']], 7],
            'no context' => [[], 0],
            'context not an array' => [['context' => 'x'], 0],
            'negative' => [['context' => ['course_id' => -3]], 0],
        ];
    }

    /**
     * The proxy takes the lock before anything costs or is recorded, refuses with 429 and releases it.
     *
     * The guard records the use of the tutor and the session calls reach the AI service, so the lock
     * has to come first: a refused question must not be logged as a use nor reach any service.
     */
    public function test_chatproxy_takes_the_lock_before_any_call(): void {
        $source = file_get_contents(__DIR__ . '/../../chatproxy.php');

        $acquire = strpos($source, 'inflight_lock::acquire(');
        $this->assertNotFalse($acquire, 'The proxy must take the in-flight lock.');
        $this->assertLessThan(strpos($source, 'request_guard::authorize('), $acquire);
        $this->assertLessThan(strpos($source, 'new tutoria_api()'), $acquire);
        $this->assertLessThan(strpos($source, 'handler::run('), $acquire);

        $this->assertStringContainsString('http_response_code(429)', $source);
        $this->assertStringContainsString("get_string('error_request_in_progress', 'local_dttutor')", $source);
        $this->assertStringContainsString("register_function([\$inflight, 'release'])", $source);
        $this->assertGreaterThan(strpos($source, 'handler::run('), strpos($source, '$inflight->release()'));
    }
}
