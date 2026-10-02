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

use local_dttutor\event\service_failed;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\fixtures\provider_exception;
use local_dttutor\httpclient\ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');
require_once(__DIR__ . '/../fixtures/provider_exception.php');
require_once(__DIR__ . '/course_documents_testcase.php');

/**
 * Telling the administrator why the documents of a course could not be read.
 *
 * See MDL-INT-016 of cases_data/dttutor/dttutor-2.0.10.md. A failed reading never stops the
 * tutor, so without a record a refused licence and a service that is down look the same.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\document_read_failure
 * @covers     \local_dttutor\proxy\document_reader
 * @covers     \local_dttutor\event\service_failed
 */
final class document_read_failure_test extends course_documents_testcase {
    /**
     * Error codes of the provider and the reason each one is recorded with.
     *
     * @return array[]
     */
    public static function provider_errors(): array {
        return [
            'licence refused' => ['license_not_allowed', null, 'license_not_allowed'],
            'credits run out' => ['notenoughtokens', null, 'tokens_not_sufficient'],
            'rate limit reached' => ['error_ratelimit_exceeded', '2026-10-02 10:00', 'rate_limit_exceeded'],
            'service unreachable' => ['curlerror', 28, 'transport'],
            'http error' => ['httperror', 502, 'http_502'],
            'http error without status' => ['httperror', null, 'http_0'],
            'unknown refusal' => ['forbidden', null, 'forbidden'],
            'no licence key' => ['invalidlicensekey', null, 'invalidlicensekey'],
            'no error code' => ['', null, document_read_failure::UNEXPECTED],
        ];
    }

    /**
     * MDL-INT-016: every error code of the provider is told with the words the chat uses.
     *
     * @dataProvider provider_errors
     * @param string $errorcode Error code of the provider.
     * @param mixed $a Argument of the error code.
     * @param string $expected Reason recorded.
     */
    public function test_each_provider_error_has_its_reason(string $errorcode, $a, string $expected): void {
        $error = new provider_exception($errorcode, $a);

        $this->assertSame($expected, document_read_failure::reason_of($error));
    }

    /**
     * MDL-INT-016: a failure that is not the provider's has no error code to tell.
     */
    public function test_a_failure_from_outside_the_provider_is_unexpected(): void {
        $error = new \RuntimeException('Something broke');

        $this->assertSame(document_read_failure::UNEXPECTED, document_read_failure::reason_of($error));
    }

    /**
     * MDL-INT-016: a reading refused for the licence is recorded with its reason, against the course.
     */
    public function test_a_licence_refusal_while_reading_is_recorded_against_the_course(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(new provider_exception('license_not_allowed'));
        \core\di::set(ai_client::class, $fake);
        $sink = $this->redirectEvents();

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertDebuggingCalled('Reading the documents of the course failed: license_not_allowed');
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
        $failures = $this->failures_in($sink);
        $this->assertCount(1, $failures);
        $failure = $failures[0];
        $this->assertSame('license_not_allowed', $failure->other['reason']);
        $this->assertSame(service_failed::OPERATION_DOCUMENTS, $failure->other['operation']);
        $this->assertEquals($course->id, $failure->courseid);
        $this->assertStringContainsString(
            "(document_read) failed with the reason 'license_not_allowed'",
            $failure->get_description()
        );
    }

    /**
     * MDL-INT-016: a failure from outside the provider is recorded too, as unexpected.
     */
    public function test_a_failure_from_outside_the_provider_is_recorded(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(new \RuntimeException('Something broke'));
        \core\di::set(ai_client::class, $fake);
        $sink = $this->redirectEvents();

        context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertDebuggingCalled('Reading the documents of the course failed: unexpected');
        $failures = $this->failures_in($sink);
        $this->assertCount(1, $failures);
        $this->assertSame(document_read_failure::UNEXPECTED, $failures[0]->other['reason']);
    }

    /**
     * MDL-INT-016: a reading that succeeds records no failure.
     */
    public function test_a_successful_reading_records_no_failure(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);
        $sink = $this->redirectEvents();

        context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame([], $this->failures_in($sink));
    }

    /**
     * MDL-INT-016: a provider that cannot be built leaves the documents unread and records nothing.
     *
     * The chat reports that failure first, with a message of its own.
     */
    public function test_a_provider_that_cannot_be_built_records_no_failure_while_reading(): void {
        $this->enable_files();
        [$course] = $this->course_with_a_document();
        fake_ai_client::bind_unavailable();
        $sink = $this->redirectEvents();
        $modinfo = get_fast_modinfo($course);
        $cmids = array_keys($modinfo->get_cms());

        $complete = document_reader::prefetch($modinfo, $cmids);

        $this->assertFalse($complete);
        $this->assertSame([], $this->failures_in($sink));
    }

    /**
     * A failure of the chat is still recorded against the site, as before.
     */
    public function test_a_failure_of_the_chat_is_recorded_against_the_site(): void {
        $sink = $this->redirectEvents();

        service_failed::record('transport');

        $failures = $this->failures_in($sink);
        $this->assertCount(1, $failures);
        $this->assertSame(service_failed::OPERATION_CHAT, $failures[0]->other['operation']);
        $this->assertEquals(\context_system::instance()->id, $failures[0]->contextid);
    }

    /**
     * A failure for a course that no longer exists is recorded against the site instead of lost.
     */
    public function test_a_failure_for_a_course_that_is_gone_is_recorded_against_the_site(): void {
        $sink = $this->redirectEvents();

        service_failed::record('transport', service_failed::OPERATION_DOCUMENTS, 999999);

        $failures = $this->failures_in($sink);
        $this->assertCount(1, $failures);
        $this->assertEquals(\context_system::instance()->id, $failures[0]->contextid);
    }

    /**
     * The failures of the service among the events caught.
     *
     * @param \phpunit_event_sink $sink Events caught.
     * @return service_failed[]
     */
    private function failures_in(\phpunit_event_sink $sink): array {
        $failures = [];
        $events = $sink->get_events();
        foreach ($events as $event) {
            if ($event instanceof service_failed) {
                $failures[] = $event;
            }
        }
        return $failures;
    }
}
