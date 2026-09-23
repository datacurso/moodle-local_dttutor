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

namespace local_dttutor\external;

use core_external\external_api;
use local_dttutor\course_config;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;
use local_dttutor\session_store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Declared contract of the web service functions the chat interface calls.
 *
 * See MDL-CTR-001 and MDL-INT-020 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\external\get_chat_history
 * @covers     \local_dttutor\external\delete_chat_session
 * @covers     \local_dttutor\external\save_course_config
 */
final class services_contract_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Functions declared by the plugin.
     *
     * @return array<string, array>
     */
    private function declared_functions(): array {
        global $CFG;
        $functions = [];
        include($CFG->dirroot . '/local/dttutor/db/services.php');
        return $functions;
    }

    /**
     * A course with the tutor on, one enrolled student logged in and a fake AI service bound.
     *
     * @return array{0: \stdClass, 1: \stdClass, 2: fake_ai_client}
     */
    private function ready_course(): array {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        set_config('enabled', 1, 'aiprovider_datacurso');
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        return [$course, $student, $fake];
    }

    /**
     * MDL-CTR-001: the three functions are declared for the interface with the right permission.
     */
    public function test_the_declared_functions_are_available_to_the_interface(): void {
        $functions = $this->declared_functions();

        $this->assertEqualsCanonicalizing(
            [
                'local_dttutor_get_chat_history',
                'local_dttutor_delete_chat_session',
                'local_dttutor_save_course_config',
            ],
            array_keys($functions)
        );
        foreach ($functions as $name => $definition) {
            $this->assertTrue((bool)$definition['ajax'], $name . ' must be callable from the interface.');
            $this->assertNotEmpty($definition['capabilities'], $name . ' must declare its permission.');
            $this->assertTrue(
                class_exists($definition['classname']),
                $name . ' points at a class that does not exist.'
            );
        }
        $this->assertEquals('local/dttutor:use', $functions['local_dttutor_get_chat_history']['capabilities']);
        $this->assertEquals('local/dttutor:use', $functions['local_dttutor_delete_chat_session']['capabilities']);
        $this->assertEquals('moodle/course:update', $functions['local_dttutor_save_course_config']['capabilities']);
    }

    /**
     * MDL-CTR-001: reading the history answers with the declared structure.
     */
    public function test_reading_the_history_answers_with_the_declared_structure(): void {
        [$course, $student, $fake] = $this->ready_course();
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-1');
        $fake->enqueue([
            'success' => true,
            'session_id' => 'remote-1',
            'total_messages' => 1,
            'messages' => [
                ['id' => 'm1', 'role' => 'user', 'content' => 'Hello', 'timestamp' => time()],
            ],
            'pagination' => ['limit' => 20, 'offset' => 0, 'has_more' => false],
        ]);

        $result = get_chat_history::execute((int)$course->id);
        $clean = external_api::clean_returnvalue(get_chat_history::execute_returns(), $result);

        $this->assertTrue($clean['success']);
        $this->assertArrayHasKey('pagination', $clean);
        $this->assertEquals('user', $clean['messages'][0]['role']);
    }

    /**
     * MDL-CTR-001: an empty history also answers with the declared structure.
     */
    public function test_an_empty_history_answers_with_the_declared_structure(): void {
        [$course] = $this->ready_course();

        $result = get_chat_history::execute((int)$course->id);
        $clean = external_api::clean_returnvalue(get_chat_history::execute_returns(), $result);

        $this->assertTrue($clean['success']);
        $this->assertSame(0, $clean['total_messages']);
        $this->assertFalse($clean['pagination']['has_more']);
    }

    /**
     * MDL-CTR-001: deleting a conversation answers with the declared structure.
     */
    public function test_deleting_a_conversation_answers_with_the_declared_structure(): void {
        [$course, $student, $fake] = $this->ready_course();
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-2');
        $fake->enqueue(['deleted' => true]);

        $result = delete_chat_session::execute((int)$course->id);
        $clean = external_api::clean_returnvalue(delete_chat_session::execute_returns(), $result);

        $this->assertTrue($clean['deleted']);
    }

    /**
     * MDL-CTR-001: saving the course switch answers with the declared structure.
     */
    public function test_saving_the_course_switch_answers_with_the_declared_structure(): void {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        set_config('enabled', 1, 'aiprovider_datacurso');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $result = save_course_config::execute((int)$course->id, true);
        $clean = external_api::clean_returnvalue(save_course_config::execute_returns(), $result);

        $this->assertTrue($clean['success']);
        $this->assertNotEmpty($clean['message']);
    }

    /**
     * Pagination values outside the admitted range and the value the service must end up using.
     *
     * @return array<string, array{0: int, 1: int, 2: string}>
     */
    public static function pagination_provider(): array {
        return [
            'a page size below the minimum' => [0, 0, 'limit=20'],
            'a page size above the maximum' => [500, 0, 'limit=100'],
            'a negative offset' => [20, -5, 'offset=0'],
        ];
    }

    /**
     * MDL-INT-020: pagination values outside the admitted range are brought back into it.
     *
     * @param int $limit Page size asked for.
     * @param int $offset Offset asked for.
     * @param string $expected Fragment the remote request must carry.
     * @dataProvider pagination_provider
     */
    public function test_pagination_values_out_of_range_are_normalised(int $limit, int $offset, string $expected): void {
        [$course, $student, $fake] = $this->ready_course();
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-3');
        $fake->enqueue([
            'success' => true,
            'session_id' => 'remote-3',
            'total_messages' => 0,
            'messages' => [],
            'pagination' => ['limit' => 20, 'offset' => 0, 'has_more' => false],
        ]);

        get_chat_history::execute((int)$course->id, null, $limit, $offset);

        $this->assertStringContainsString($expected, $fake->calls[0]['path']);
    }
}
