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

defined('MOODLE_INTERNAL') || die();

/**
 * Base of the tests that read the documents a course hands out.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class course_documents_testcase extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Turn both settings on: documents ride on the material of the course.
     */
    protected function enable_files(): void {
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
    protected function course_with_a_document(
        string $filename = 'guide.pdf',
        string $component = 'mod_resource',
        string $filearea = 'content'
    ): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $resource = $this->add_document($course, 'Handbook', $filename, $component, $filearea);

        return [$course, $student, $resource];
    }

    /**
     * Add a file resource holding one document to a course.
     *
     * @param \stdClass $course Course the resource goes to.
     * @param string $name Name of the resource.
     * @param string $filename Name of the document.
     * @param string $component Area the document is stored in.
     * @param string $filearea Area the document is stored in.
     * @return \stdClass The resource.
     */
    protected function add_document(
        \stdClass $course,
        string $name,
        string $filename,
        string $component = 'mod_resource',
        string $filearea = 'content'
    ): \stdClass {
        $resource = $this->getDataGenerator()->create_module('resource', ['course' => $course->id, 'name' => $name]);
        $context = \context_module::instance($resource->cmid);
        $storage = get_file_storage();
        // The generator leaves a file of its own behind, and every test here counts documents.
        $storage->delete_area_files($context->id, 'mod_resource', 'content');
        $storage->create_file_from_string([
            'contextid' => $context->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $this->bytes_of($filename));

        return $resource;
    }

    /**
     * What the service answers when it read one document.
     *
     * @param string $filename Name of the document.
     * @param string $text Text read out of it.
     * @return array
     */
    protected function read_response(string $filename, string $text): array {
        return ['extracted' => [$this->read_entry($filename, $text)], 'skipped' => []];
    }

    /**
     * One document as the service lists it once read.
     *
     * @param string $filename Name of the document.
     * @param string $text Text read out of it.
     * @return array
     */
    protected function read_entry(string $filename, string $text): array {
        $bytes = $this->bytes_of($filename);
        return ['filename' => $filename, 'sha1' => sha1($bytes), 'text' => $text];
    }

    /**
     * The content every test document holds.
     *
     * @param string $filename Name of the document.
     * @return string
     */
    protected function bytes_of(string $filename): string {
        return 'the bytes of ' . $filename;
    }
}
