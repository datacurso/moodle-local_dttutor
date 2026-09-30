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

/**
 * Reading the documents a course hands out.
 *
 * See MDL-INT-016 of cases_data/dttutor/dttutor-2.0.10.md. Nothing in Moodle reads a PDF, so the
 * documents are sent to the AI service and the text that comes back is kept against the content
 * hash of the file.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\file_content
 */
final class file_content_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Turn both settings on: documents ride on the material of the course.
     */
    private function enable_files(): void {
        set_config('include_content', 1, 'local_dttutor');
        set_config('include_files', 1, 'local_dttutor');
    }

    /**
     * A course with a student and a file resource holding one document.
     *
     * @param string $filename Name of the document.
     * @param string $component Area the document is stored in.
     * @param string $filearea Area the document is stored in.
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass} Course, student and activity.
     */
    private function course_with_a_document(
        string $filename = 'guide.pdf',
        string $component = 'mod_resource',
        string $filearea = 'content'
    ): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $resource = $generator->create_module('resource', ['course' => $course->id, 'name' => 'Handbook']);
        // The generator leaves a file of its own behind, and every test here counts documents.
        get_file_storage()->delete_area_files(
            \context_module::instance($resource->cmid)->id,
            'mod_resource',
            'content'
        );
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($resource->cmid)->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], 'the bytes of ' . $filename);

        return [$course, $student, $resource];
    }

    /**
     * MDL-INT-016: the documents of the course are sent once and their text travels.
     */
    public function test_the_text_of_a_document_reaches_the_knowledge(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [
            ['filename' => 'guide.pdf', 'sha1' => sha1('the bytes of guide.pdf'), 'kind' => 'pdf',
                'chars' => 20, 'truncated' => false, 'text' => 'Prune the vine in winter'],
        ], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame(['POST /chat/material/extract'], $fake->get_call_signatures());
        $this->assertStringContainsString('Document guide.pdf: Prune the vine in winter', $text);
        $this->assertStringNotContainsString('files not readable by you', $text);
    }

    /**
     * MDL-INT-016: a document is read once, however many times the knowledge is built.
     */
    public function test_a_document_already_read_is_not_sent_again(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(['extracted' => [
            ['filename' => 'guide.pdf', 'sha1' => sha1('the bytes of guide.pdf'), 'kind' => 'pdf',
                'chars' => 20, 'truncated' => false, 'text' => 'Prune the vine in winter'],
        ], 'skipped' => []]);
        \core\di::set(ai_client::class, $fake);

        context_preloader::build((int)$course->id, (int)$student->id);
        \cache::make('local_dttutor', 'course_knowledge')->purge();
        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertCount(1, $fake->calls, 'The document is read once and kept by its content hash.');
        $this->assertStringContainsString('Prune the vine in winter', $text);
    }

    /**
     * MDL-INT-016: with the setting off, no document leaves the platform.
     */
    public function test_no_document_is_sent_until_the_administrator_asks(): void {
        set_config('include_content', 1, 'local_dttutor');
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertSame([], $fake->get_call_signatures());
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
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
     * MDL-INT-016: a service that cannot be reached leaves the course readable.
     */
    public function test_a_failure_to_read_never_breaks_the_knowledge(): void {
        $this->enable_files();
        [$course, $student] = $this->course_with_a_document();
        $fake = new fake_ai_client();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));
        \core\di::set(ai_client::class, $fake);

        $text = context_preloader::build((int)$course->id, (int)$student->id);

        $this->assertDebuggingCalled();
        $this->assertStringContainsString('Handbook', $text);
        $this->assertStringContainsString('files not readable by you: guide.pdf', $text);
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
}
