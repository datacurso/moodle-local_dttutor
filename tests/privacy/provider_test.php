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

namespace local_dttutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\database_table;
use core_privacy\local\metadata\types\external_location;
use core_privacy\local\metadata\types\subsystem_link;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_dttutor\course_config;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Privacy provider tests for local_dttutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\privacy\provider
 */
final class provider_test extends provider_testcase {
    /** @var string Component name under test. */
    private const COMPONENT = 'local_dttutor';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Register a fake HTTP layer for every code path that resolves the tutor API from the DI container.
     *
     * @return fake_ai_client
     */
    private function fake_remote_api(): fake_ai_client {
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        return $fake;
    }

    /**
     * Insert a stored session row.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $remoteid
     * @param int $cmid
     * @return int Row id.
     */
    private function add_session(int $userid, int $courseid, string $remoteid, int $cmid = 0): int {
        global $DB;
        $now = time();
        return $DB->insert_record('local_dttutor_session', (object)[
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'remotesessionid' => $remoteid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Declared metadata items of one type, indexed by name.
     *
     * @param string $class
     * @return array<string, \core_privacy\local\metadata\types\type>
     */
    private function get_items_by_type(string $class): array {
        $items = [];
        foreach (provider::get_metadata(new collection(self::COMPONENT))->get_collection() as $item) {
            if ($item instanceof $class) {
                $items[$item->get_name()] = $item;
            }
        }
        return $items;
    }

    public function test_metadata_declares_tables_and_external_location(): void {
        $tables = $this->get_items_by_type(database_table::class);
        $this->assertArrayHasKey('local_dttutor_course_config', $tables);
        $this->assertArrayHasKey('local_dttutor_session', $tables);
        $this->assertEqualsCanonicalizing(
            ['timemodified', 'usermodified'],
            array_keys($tables['local_dttutor_course_config']->get_privacy_fields())
        );
        $this->assertEqualsCanonicalizing(
            ['cmid', 'courseid', 'remotesessionid', 'timecreated', 'timemodified', 'userid'],
            array_keys($tables['local_dttutor_session']->get_privacy_fields())
        );

        // No file area is declared any more: the course-materials feature was removed.
        $this->assertSame([], $this->get_items_by_type(subsystem_link::class));

        $external = $this->get_items_by_type(external_location::class);
        $this->assertArrayHasKey('datacurso_ai', $external);
        $this->assertEqualsCanonicalizing(
            ['cmid', 'course_structure', 'custom_prompt', 'grades', 'lang', 'messages', 'page_url',
                'selected_text', 'site_id', 'site_url', 'timezone', 'userid'],
            array_keys($external['datacurso_ai']->get_privacy_fields())
        );
    }

    public function test_contexts_for_user_with_a_stored_session(): void {
        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course();
        $courseb = $generator->create_course();
        $student = $generator->create_and_enrol($coursea, 'student');
        $this->add_session((int)$student->id, (int)$coursea->id, 'sess-a');

        $contextlist = $this->get_contexts_for_userid((int)$student->id, self::COMPONENT);

        $this->assertEquals([\context_course::instance($coursea->id)->id], $contextlist->get_contextids());
        $this->assertNotContains(\context_course::instance($courseb->id)->id, $contextlist->get_contextids());
    }

    public function test_contexts_for_teacher_who_modified_the_course_config(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        course_config::update($course->id, ['indexing_enabled' => 1]);

        $contextlist = $this->get_contexts_for_userid((int)$teacher->id, self::COMPONENT);

        $this->assertEquals([\context_course::instance($course->id)->id], $contextlist->get_contextids());
    }

    public function test_contexts_are_empty_for_an_unrelated_user(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $other = $generator->create_and_enrol($course, 'student');
        $bystander = $generator->create_user();
        $this->add_session((int)$other->id, (int)$course->id, 'sess-other');

        $contextlist = $this->get_contexts_for_userid((int)$bystander->id, self::COMPONENT);

        $this->assertCount(0, $contextlist);
    }

    public function test_users_in_course_context(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $generator->create_and_enrol($course, 'student');
        $this->setUser($teacher);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $this->add_session((int)$student->id, (int)$course->id, 'sess-student');

        $userlist = new userlist(\context_course::instance($course->id), self::COMPONENT);
        provider::get_users_in_context($userlist);

        $this->assertEqualsCanonicalizing([(int)$student->id, (int)$teacher->id], $userlist->get_userids());
    }

    public function test_users_in_non_course_context_is_empty(): void {
        $userlist = new userlist(\context_system::instance(), self::COMPONENT);
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);
    }

    public function test_export_writes_the_users_sessions_and_config(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $other = $generator->create_and_enrol($course, 'student');
        $this->setUser($teacher);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $this->add_session((int)$teacher->id, (int)$course->id, 'sess-teacher', (int)$page->cmid);
        $this->add_session((int)$other->id, (int)$course->id, 'sess-other');
        $context = \context_course::instance($course->id);

        $this->export_context_data_for_user((int)$teacher->id, $context, self::COMPONENT);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $sessions = $writer->get_data([
            get_string('pluginname', self::COMPONENT),
            get_string('privacy:export:sessions', self::COMPONENT),
        ]);
        $this->assertCount(1, $sessions->sessions);
        $this->assertSame('sess-teacher', $sessions->sessions[0]->remotesessionid);
        $this->assertEquals($page->cmid, $sessions->sessions[0]->cmid);

        $config = $writer->get_data([
            get_string('pluginname', self::COMPONENT),
            get_string('privacy:export:course_config', self::COMPONENT),
        ]);
        $this->assertNotEmpty($config->timemodified);
        $this->assertFalse(property_exists($config, 'custom_prompt'), 'The per-course prompt no longer exists');
    }

    public function test_delete_data_for_user_removes_only_that_users_rows(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $other = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-student');
        $this->add_session((int)$student->id, (int)$othercourse->id, 'sess-student-other-course');
        $this->add_session((int)$other->id, (int)$course->id, 'sess-other');
        $this->setUser($student);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $fake = $this->fake_remote_api();

        $contextlist = new approved_contextlist($student, self::COMPONENT, [\context_course::instance($course->id)->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'sess-student']));
        $this->assertTrue($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'sess-student-other-course']));
        $this->assertTrue($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'sess-other']));
        $this->assertSame(['DELETE /chat/session/sess-student'], $fake->get_call_signatures());
        $config = $DB->get_record('local_dttutor_course_config', ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(0, $config->usermodified);
    }

    public function test_delete_data_for_all_users_in_context_clears_the_course(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $studenta = $generator->create_and_enrol($course, 'student');
        $studentb = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$studenta->id, (int)$course->id, 'sess-a');
        $this->add_session((int)$studentb->id, (int)$course->id, 'sess-b');
        $this->add_session((int)$studenta->id, (int)$othercourse->id, 'sess-a-other');
        $this->setUser($studenta);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $fake = $this->fake_remote_api();

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertSame(0, $DB->count_records('local_dttutor_session', ['courseid' => $course->id]));
        $this->assertSame(1, $DB->count_records('local_dttutor_session', ['courseid' => $othercourse->id]));
        $this->assertEqualsCanonicalizing(
            ['DELETE /chat/session/sess-a', 'DELETE /chat/session/sess-b'],
            $fake->get_call_signatures()
        );
        $this->assertEquals(0, $DB->get_field('local_dttutor_course_config', 'usermodified', ['courseid' => $course->id]));
    }

    public function test_delete_data_for_all_users_ignores_non_course_contexts(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-a');
        $this->fake_remote_api();

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $this->assertSame(1, $DB->count_records('local_dttutor_session'));
    }

    public function test_delete_data_for_users_removes_the_listed_users_only(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $studenta = $generator->create_and_enrol($course, 'student');
        $studentb = $generator->create_and_enrol($course, 'student');
        $studentc = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$studenta->id, (int)$course->id, 'sess-a');
        $this->add_session((int)$studentb->id, (int)$course->id, 'sess-b');
        $this->add_session((int)$studentc->id, (int)$course->id, 'sess-c');
        $this->setUser($studentc);
        course_config::update($course->id, ['indexing_enabled' => 1]);
        $fake = $this->fake_remote_api();

        $userlist = new approved_userlist(
            \context_course::instance($course->id),
            self::COMPONENT,
            [(int)$studenta->id, (int)$studentb->id]
        );
        provider::delete_data_for_users($userlist);

        $remaining = $DB->get_fieldset_select('local_dttutor_session', 'remotesessionid', '1 = 1');
        $this->assertSame(['sess-c'], $remaining);
        $this->assertCount(2, $fake->calls);
        // Student C modified the config and was not in the list, so the reference is kept.
        $this->assertEquals(
            $studentc->id,
            $DB->get_field('local_dttutor_course_config', 'usermodified', ['courseid' => $course->id])
        );
    }

    public function test_remote_deletion_failure_does_not_abort_local_deletion(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-fails');
        $fake = $this->fake_remote_api();
        $fake->enqueue(new \moodle_exception('error_unexpected', 'local_dttutor'));

        $contextlist = new approved_contextlist($student, self::COMPONENT, [\context_course::instance($course->id)->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertDebuggingCalled();
        $this->assertSame(0, $DB->count_records('local_dttutor_session'));
    }

    public function test_deletion_completes_when_the_remote_client_cannot_be_built(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $this->add_session((int)$student->id, (int)$course->id, 'sess-unreachable');
        // Resolving the AI client throws, as when the provider is unconfigured or not installed.
        fake_ai_client::bind_unavailable();

        $contextlist = new approved_contextlist($student, self::COMPONENT, [\context_course::instance($course->id)->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertDebuggingCalled();
        $this->assertSame(0, $DB->count_records('local_dttutor_session'));
    }
}
