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

use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');
require_once(__DIR__ . '/course_documents_testcase.php');

/**
 * Sending the documents of a course to be read, and keeping what comes back.
 *
 * See MDL-INT-016 of cases_data/dttutor/dttutor-2.0.10.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\document_reader
 * @covers     \local_dttutor\proxy\context_preloader
 */
final class document_reader_test extends course_documents_testcase {
    /**
     * MDL-INT-016: a document is read once, however many times the knowledge is built.
     */
    public function test_a_document_already_read_is_not_sent_again(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        \cache::make('local_dttutor', 'course_knowledge')->purge();
        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(1, $fake->calls, 'The document is read once and kept by its content hash.');
        $this->assertStringContainsString('Prune the vine in winter', $text);
    }

    /**
     * MDL-INT-016: a document left out for want of budget is sent again on the next build.
     */
    public function test_a_document_left_out_for_want_of_budget_is_sent_again(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [], 'skipped' => [
            ['filename' => 'guide.pdf', 'sha1' => sha1('the bytes of guide.pdf'), 'reason' => 'no_budget_left'],
        ]]);
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        \cache::make('local_dttutor', 'course_knowledge')->purge();
        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(2, $fake->calls);
        $this->assertStringContainsString('Document guide.pdf: Prune the vine in winter', $text);
    }

    /**
     * MDL-INT-016: one request never carries more documents than the service accepts.
     */
    public function test_one_request_carries_at_most_the_documents_allowed(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document('doc0.pdf');
        $this->add_documents_to_the_course($course, document_reader::MAX_FILES);
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(1, $fake->calls);
        $this->assertCount(document_reader::MAX_FILES, $fake->calls[0]['body']['files']);
    }

    /**
     * MDL-INT-016: a file the service could not read anyway never leaves the platform.
     */
    public function test_a_file_of_a_kind_that_cannot_be_read_is_not_sent(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document('lecture.mp4');
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame([], $fake->get_call_signatures(), 'Encoding a video to be refused is a poor trade.');
        $this->assertStringContainsString('files not readable by you: lecture.mp4', $text);
    }

    /**
     * MDL-INT-016: a service that cannot be reached leaves the course readable.
     */
    public function test_a_failure_to_read_never_breaks_the_knowledge(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertDebuggingCalled('Reading the documents of the course failed: error_unexpected');
        $this->assertStringContainsString('Handbook', $text);
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
    }

    /**
     * MDL-INT-016: a course with nothing left to read asks the service nothing.
     */
    public function test_a_course_without_documents_asks_the_service_nothing(): void {
        $this->enable_files();
        $course = $this->getDataGenerator()->create_course();
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        $modinfo = get_fast_modinfo($course);

        $complete = document_reader::prefetch($modinfo, []);

        $this->assertTrue($complete);
        $this->assertSame([], $fake->get_call_signatures());
    }

    /**
     * MDL-INT-016: a block built while the documents could not be read is retried a few minutes later.
     */
    public function test_a_failure_to_read_is_retried_after_a_few_minutes(): void {
        $this->enable_files();
        $clock = $this->mock_clock_with_frozen();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        $this->assertDebuggingCalled();
        $soon = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(1, $fake->calls, 'A failure is not sent again with every question.');
        $this->assertStringNotContainsString('Prune the vine in winter', $soon);

        $clock->bump(context_preloader::INCOMPLETE_TTL + 1);
        $later = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(2, $fake->calls);
        $this->assertStringContainsString('Document guide.pdf: Prune the vine in winter', $later);
    }

    /**
     * MDL-INT-016: a block built with every document read is kept as before.
     */
    public function test_a_complete_block_is_not_rebuilt_after_a_few_minutes(): void {
        $this->enable_files();
        $clock = $this->mock_clock_with_frozen();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        \cache::make('local_dttutor', 'file_text')->purge();
        $clock->bump(context_preloader::INCOMPLETE_TTL + 1);
        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(1, $fake->calls, 'Only a block missing its documents expires early.');
        $this->assertStringContainsString('Prune the vine in winter', $text);
    }

    /**
     * AUD-03: one request carries at most the bytes allowed, so the encoding fits in memory.
     */
    public function test_one_request_carries_at_most_the_bytes_allowed(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document('doc0.pdf');
        $this->add_documents_to_the_course($course, 3);
        // Each document of the fixture weighs 22 bytes: two fit, the other two wait.
        set_config('document_request_bytes', 50, 'local_dttutor');
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        $complete = document_reader::prefetch(get_fast_modinfo($course, $student->id), $this->resources_of($course));

        $this->assertFalse($complete, 'Documents left for the next request leave the knowledge incomplete.');
        $this->assertCount(1, $fake->calls);
        $this->assertCount(2, $fake->calls[0]['body']['files']);
    }

    /**
     * AUD-03: the documents that did not fit are read by the next build, a few minutes later.
     */
    public function test_the_documents_that_did_not_fit_are_read_next(): void {
        $this->enable_files();
        $clock = $this->mock_clock_with_frozen();
        [$course, $student] = $this->course_with_a_document('doc0.pdf');
        $this->add_documents_to_the_course($course, 1);
        set_config('document_request_bytes', 30, 'local_dttutor');
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [$this->read_entry('doc0.pdf', 'First text')], 'skipped' => []]);
        $fake->enqueue(['extracted' => [$this->read_entry('doc1.pdf', 'Second text')], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        $clock->bump(context_preloader::INCOMPLETE_TTL + 1);
        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(2, $fake->calls);
        $this->assertSame('doc1.pdf', $fake->calls[1]['body']['files'][0]['filename']);
        $this->assertStringContainsString('First text', $text);
        $this->assertStringContainsString('Second text', $text);
    }

    /**
     * AUD-03: a document heavier than the budget is still read, on its own.
     */
    public function test_a_document_heavier_than_the_budget_is_read_alone(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document('guide.pdf');
        set_config('document_request_bytes', 5, 'local_dttutor');
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        $complete = document_reader::prefetch(get_fast_modinfo($course, $student->id), $this->resources_of($course));

        $this->assertTrue($complete);
        $this->assertCount(1, $fake->calls[0]['body']['files']);
    }

    /**
     * AUD-03: while one request reads the documents of a course, the others do not send them again.
     */
    public function test_a_course_being_read_by_another_request_is_not_sent_twice(): void {
        global $CFG;
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        // File locks are not reentrant within one process, so they stand in for another request.
        $CFG->lock_factory = '\\core\\lock\\file_lock_factory';
        $other = \core\lock\lock_config::get_lock_factory('local_dttutor_documents')->get_lock('course_' . $course->id, 0);

        try {
            $complete = document_reader::prefetch(get_fast_modinfo($course, $student->id), $this->resources_of($course));
        } finally {
            $other->release();
        }

        $this->assertFalse($complete);
        $this->assertSame([], $fake->get_call_signatures());
    }

    /**
     * The course modules of a course, in the order they are listed.
     *
     * @param \stdClass $course
     * @return int[]
     */
    private function resources_of(\stdClass $course): array {
        return array_keys(get_fast_modinfo($course)->get_cms());
    }

    /**
     * Add documents to a course, each one in a resource of its own.
     *
     * @param \stdClass $course Course the documents go to.
     * @param int $count Documents to add.
     */
    private function add_documents_to_the_course(\stdClass $course, int $count): void {
        for ($i = 1; $i <= $count; $i++) {
            $this->add_document($course, 'Annex ' . $i, 'doc' . $i . '.pdf');
        }
    }
}
