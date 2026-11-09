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

use local_dttutor\course_config;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\fixtures\provider_dependency_trait;
use local_dttutor\httpclient\ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');
require_once(__DIR__ . '/../fixtures/provider_dependency_trait.php');

/**
 * The tutor refuses every question while the provider applies no usage limit to it (DTT-SEC-006-R1).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\request_guard
 */
final class rate_limit_gate_test extends \advanced_testcase {
    use provider_dependency_trait;

    /** @var string[] Headers the provider sends when the limit of the tutor is active. */
    private const ACTIVE_LIMIT = [
        'X-RateLimit-Enable: 1',
        'X-RateLimit-Limit: 100',
        'X-RateLimit-WindowSeconds: 3600',
        'X-RateLimit-MaxPerAction: 1',
    ];

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->require_provider();
    }

    /**
     * A course with the tutor on, the provider configured as given, and a student enrolled in it.
     *
     * @param array $provider Options of the provider generator.
     * @return array{0: \stdClass, 1: \stdClass} The course and the student.
     */
    private function course_with_the_tutor_on(array $provider = []): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider($provider);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        return [$course, $student];
    }

    /**
     * A chat request for a course.
     *
     * @param int $courseid
     * @return array
     */
    private function chat_request(int $courseid): array {
        return [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'context' => ['course_id' => $courseid],
        ];
    }

    /**
     * Bind a fake AI client that sends the given rate limit headers.
     *
     * @param string[] $headers
     * @return fake_ai_client
     */
    private function bind_client(array $headers): fake_ai_client {
        $fake = new fake_ai_client();
        $fake->set_rate_limit_headers($headers);
        \core\di::set(ai_client::class, $fake);
        return $fake;
    }

    /**
     * Ask, expecting the refusal for a missing limit.
     *
     * @param int $courseid
     */
    private function assert_refused(int $courseid): void {
        try {
            request_guard::authorize($this->chat_request($courseid));
            $this->fail('Without a usage limit in the provider the question had to be refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame(request_guard::RATE_LIMIT_ERROR, $e->errorcode);
        }
    }

    /**
     * With the limit of the provider off, the question is refused before any call and is not recorded as a use.
     */
    public function test_question_is_refused_before_any_call_while_the_limit_is_off(): void {
        [$course, $student] = $this->course_with_the_tutor_on();
        $fake = $this->bind_client([]);
        $this->setUser($student);
        $sink = $this->redirectEvents();

        $this->assert_refused((int)$course->id);

        $this->assertSame([], $fake->calls, 'No request may reach the AI service without a usage limit.');
        $this->assertSame([], $sink->get_events(), 'A refused question is not recorded as a use of the tutor.');
    }

    /**
     * The real provider, with the limit of the tutor switched off, refuses.
     */
    public function test_provider_limit_switched_off_is_refused(): void {
        [$course, $student] = $this->course_with_the_tutor_on(['ratelimit' => false]);
        $this->setUser($student);

        $this->assert_refused((int)$course->id);
    }

    /**
     * The real provider, with the limit switched on but no credits set, refuses: that limit is not applied.
     */
    public function test_provider_limit_without_credits_is_refused(): void {
        [$course, $student] = $this->course_with_the_tutor_on(['ratelimit_limit' => 0]);
        $this->setUser($student);

        $this->assert_refused((int)$course->id);
    }

    /**
     * The real provider, with the limit of the tutor on, lets the question through.
     */
    public function test_provider_limit_switched_on_lets_the_question_through(): void {
        [$course, $student] = $this->course_with_the_tutor_on();
        $this->setUser($student);

        $this->assertNotEmpty(request_guard::authorize($this->chat_request((int)$course->id)));
    }

    /**
     * A client that sends the limit lets the question through.
     */
    public function test_active_limit_lets_the_question_through(): void {
        [$course, $student] = $this->course_with_the_tutor_on();
        $this->bind_client(self::ACTIVE_LIMIT);
        $this->setUser($student);

        $this->assertNotEmpty(request_guard::authorize($this->chat_request((int)$course->id)));
    }

    /**
     * A limit that cannot be read counts as not set.
     */
    public function test_unreadable_limit_is_refused(): void {
        [$course, $student] = $this->course_with_the_tutor_on();
        $fake = $this->bind_client(self::ACTIVE_LIMIT);
        $fake->fail_rate_limit_headers(new \RuntimeException('Limit unreadable'));
        $this->setUser($student);

        $this->assert_refused((int)$course->id);
    }

    /**
     * Without a client at all (no licence) this check steps aside: nothing can reach the service anyway.
     */
    public function test_unavailable_client_is_left_to_the_licence_check(): void {
        fake_ai_client::bind_unavailable();

        request_guard::assert_rate_limit_enabled();
        $this->assertTrue(true, 'No refusal for a missing limit when there is no client to send anything.');
    }

    /**
     * Students are asked to contact the administrator; administrators are told where to enable the limit.
     */
    public function test_message_depends_on_who_asks(): void {
        [, $student] = $this->course_with_the_tutor_on();

        $this->setUser($student);
        $this->assertSame(
            get_string('error_ratelimit_not_configured', 'local_dttutor'),
            request_guard::rate_limit_error_message()
        );

        $this->setAdminUser();
        $this->assertSame(
            get_string('error_ratelimit_not_configured_admin', 'local_dttutor'),
            request_guard::rate_limit_error_message()
        );
    }

    /**
     * The chat proxy answers the refusal with its own code and the localized message.
     */
    public function test_chatproxy_sends_the_message_of_the_refusal(): void {
        $source = file_get_contents(__DIR__ . '/../../chatproxy.php');

        $this->assertStringContainsString('request_guard::RATE_LIMIT_ERROR', $source);
        $this->assertStringContainsString("'ratelimit_not_configured'", $source);
        $this->assertStringContainsString('request_guard::rate_limit_error_message()', $source);
    }
}
