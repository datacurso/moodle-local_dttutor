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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\privacy\provider;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * No text of a course stays cached once the course, the user or a privacy request deletes it (DTT-PRIV-005).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\cached_text
 * @covers     \local_dttutor\hook\course_deletion
 * @covers     \local_dttutor\observer
 * @covers     \local_dttutor\privacy\provider
 */
final class cached_text_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        // No real service: a privacy deletion still asks it to purge the conversations, and the fake agrees.
        \core\di::set(\local_dttutor\httpclient\ai_client::class, new fake_ai_client());
    }

    /**
     * Create a course holding one document, and cache its text as the tutor would.
     *
     * @param string $content Content of the document, which sets its content hash.
     * @return array{0: \stdClass, 1: string} The course and the content hash of its document.
     */
    private function course_with_cached_document(string $content): array {
        $course = $this->getDataGenerator()->create_course();
        $resource = $this->getDataGenerator()->create_module('resource', ['course' => $course->id]);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($resource->cmid)->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'notes.txt',
        ], $content);
        \cache::make('local_dttutor', 'file_text')->set($file->get_contenthash(), ['text' => $content]);
        return [$course, $file->get_contenthash()];
    }

    /**
     * Cache the knowledge block of a user in a course.
     *
     * @param int $courseid
     * @param int $userid
     */
    private function cache_knowledge(int $courseid, int $userid): void {
        \cache::make('local_dttutor', 'course_knowledge')->set(
            cached_text::knowledge_key($courseid, $userid),
            ['text' => 'Course material']
        );
    }

    /**
     * Whether the text of a document is still cached.
     *
     * @param string $hash
     * @return bool
     */
    private function document_cached(string $hash): bool {
        return \cache::make('local_dttutor', 'file_text')->get($hash) !== false;
    }

    /**
     * Whether the knowledge block of a user in a course is still cached.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    private function knowledge_cached(int $courseid, int $userid): bool {
        return \cache::make('local_dttutor', 'course_knowledge')->get(cached_text::knowledge_key($courseid, $userid)) !== false;
    }

    /**
     * Deleting a course forgets the text of its documents and its knowledge blocks; other courses keep theirs.
     */
    public function test_deleting_a_course_forgets_its_text(): void {
        [$course, $hash] = $this->course_with_cached_document('Text of the deleted course');
        [, $otherhash] = $this->course_with_cached_document('Text of another course');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->cache_knowledge((int)$course->id, (int)$student->id);

        delete_course($course->id, false);

        $this->assertFalse($this->document_cached($hash));
        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$student->id));
        $this->assertTrue($this->document_cached($otherhash), 'The documents of other courses are not read again.');
    }

    /**
     * Deleting a user forgets their knowledge blocks, even when they never stored a conversation.
     */
    public function test_deleting_a_user_forgets_their_knowledge(): void {
        [$course] = $this->course_with_cached_document('Course text');
        $othercourse = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->cache_knowledge((int)$course->id, (int)$student->id);
        $this->cache_knowledge((int)$othercourse->id, (int)$student->id);

        delete_user($student);

        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$student->id));
        $this->assertFalse($this->knowledge_cached((int)$othercourse->id, (int)$student->id));
    }

    /**
     * A privacy deletion of a whole course forgets the text of its documents and its knowledge blocks.
     */
    public function test_privacy_deletion_of_a_course_forgets_its_text(): void {
        [$course, $hash] = $this->course_with_cached_document('Course text');
        [, $otherhash] = $this->course_with_cached_document('Text of another course');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->cache_knowledge((int)$course->id, (int)$student->id);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertFalse($this->document_cached($hash));
        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$student->id));
        $this->assertTrue($this->document_cached($otherhash));
    }

    /**
     * A privacy deletion for one user forgets that user's knowledge block in that course only.
     */
    public function test_privacy_deletion_for_a_user_forgets_only_their_knowledge(): void {
        [$course] = $this->course_with_cached_document('Course text');
        $othercourse = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $classmate = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->cache_knowledge((int)$course->id, (int)$student->id);
        $this->cache_knowledge((int)$othercourse->id, (int)$student->id);
        $this->cache_knowledge((int)$course->id, (int)$classmate->id);

        provider::delete_data_for_user(
            new approved_contextlist($student, 'local_dttutor', [\context_course::instance($course->id)->id])
        );

        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$student->id));
        $this->assertTrue($this->knowledge_cached((int)$othercourse->id, (int)$student->id));
        $this->assertTrue($this->knowledge_cached((int)$course->id, (int)$classmate->id));
    }

    /**
     * A privacy deletion for several users of a course forgets their knowledge blocks there.
     */
    public function test_privacy_deletion_for_users_forgets_their_knowledge(): void {
        [$course] = $this->course_with_cached_document('Course text');
        $studenta = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $studentb = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $kept = $this->getDataGenerator()->create_and_enrol($course, 'student');
        foreach ([$studenta, $studentb, $kept] as $user) {
            $this->cache_knowledge((int)$course->id, (int)$user->id);
        }

        provider::delete_data_for_users(new approved_userlist(
            \context_course::instance($course->id),
            'local_dttutor',
            [(int)$studenta->id, (int)$studentb->id]
        ));

        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$studenta->id));
        $this->assertFalse($this->knowledge_cached((int)$course->id, (int)$studentb->id));
        $this->assertTrue($this->knowledge_cached((int)$course->id, (int)$kept->id));
    }

    /**
     * The proxy reads the knowledge block under the key that the deletions remove.
     */
    public function test_the_preloader_uses_the_shared_key(): void {
        $this->assertStringContainsString(
            'cached_text::knowledge_key(',
            file_get_contents(__DIR__ . '/../../classes/proxy/context_preloader.php')
        );
    }
}
