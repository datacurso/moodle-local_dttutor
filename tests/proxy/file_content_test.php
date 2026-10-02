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
 * The text of the documents a course hands out, as it reaches the tutor.
 *
 * See MDL-INT-016 of cases_data/dttutor/dttutor-2.0.10.md. Nothing in Moodle reads a PDF, so the
 * documents are sent to the AI service and the text that comes back travels with the knowledge
 * of the course, within its budget.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\file_content
 */
final class file_content_test extends course_documents_testcase {
    /**
     * MDL-INT-016: the documents of the course are sent once and their text travels.
     */
    public function test_the_text_of_a_document_reaches_the_knowledge(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame(['POST /chat/material/extract'], $fake->get_call_signatures());
        $this->assertStringContainsString('Document guide.pdf: Prune the vine in winter', $text);
        $this->assertStringNotContainsString('files not readable by you', $text);
    }

    /**
     * MDL-INT-016: the first activity holding documents does not take the whole budget.
     */
    public function test_documents_share_the_budget_between_activities(): void {
        $this->enable_files();
        set_config('content_chars_total', 2000, 'local_dttutor');
        [$course, $student] = $this->course_with_a_document('first.pdf');
        $this->add_document($course, 'Annex', 'second.pdf');
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [
            $this->read_entry('first.pdf', str_repeat('a', 4000)),
            $this->read_entry('second.pdf', 'Harvest in autumn'),
        ], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertStringContainsString('Document first.pdf: aaa', $text);
        $this->assertStringContainsString('Document second.pdf: Harvest in autumn', $text);
        $this->assertStringNotContainsString('files not readable by you', $text);
    }

    /**
     * MDL-INT-016: a document read but left out for want of budget is still named.
     */
    public function test_a_document_left_out_of_the_budget_is_named(): void {
        $this->enable_files();
        set_config('content_chars_total', 1, 'local_dttutor');
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue($this->read_response('guide.pdf', 'Prune the vine in winter'));
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertStringNotContainsString('Prune the vine in winter', $text);
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
    }

    /**
     * MDL-INT-016: with the material of the course switched off, no document leaves either.
     */
    public function test_no_document_is_sent_until_the_administrator_asks(): void {
        set_config('include_content', 0, 'local_dttutor');
        set_config('include_files', 1, 'local_dttutor');
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame([], $fake->get_call_signatures());
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
    }

    /**
     * MDL-INT-016: with the documents switched off, the rest of the material still travels alone.
     */
    public function test_no_document_is_sent_when_documents_are_switched_off(): void {
        set_config('include_content', 1, 'local_dttutor');
        set_config('include_files', 0, 'local_dttutor');
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertFalse(file_content::is_enabled());
        $this->assertSame([], $fake->get_call_signatures());
        $this->assertStringContainsString('Handbook', $text);
    }

    /**
     * MDL-INT-016: a document the service could not read is named, not invented.
     */
    public function test_a_document_that_could_not_be_read_is_named_as_unreadable(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document('scan.pdf');
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [], 'skipped' => [
            ['filename' => 'scan.pdf', 'sha1' => sha1('the bytes of scan.pdf'), 'reason' => 'no_text_found'],
        ]]);
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertStringContainsString('files not readable by you: scan.pdf', $text);
        $this->assertStringNotContainsString('Document scan.pdf', $text);
    }

    /**
     * MDL-INT-016: what a student uploaded is never sent to be read.
     */
    public function test_a_document_uploaded_by_a_student_is_never_sent(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document(
            'mine.pdf',
            'assignsubmission_file',
            'submission_files'
        );
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame([], $fake->get_call_signatures());
        $this->assertStringNotContainsString('mine.pdf', $text);
    }

    /**
     * The characters one document may contribute follow the setting, and its default otherwise.
     */
    public function test_the_characters_per_document_follow_the_setting(): void {
        $this->assertSame(file_content::DEFAULT_CHARS_PER_FILE, file_content::chars_per_file());

        set_config('file_chars', 0, 'local_dttutor');
        $this->assertSame(file_content::DEFAULT_CHARS_PER_FILE, file_content::chars_per_file());

        set_config('file_chars', 1500, 'local_dttutor');
        $this->assertSame(1500, file_content::chars_per_file());
    }
}
