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

namespace local_dttutor\httpclient;

use local_dttutor\fixtures\fake_ai_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fake_ai_client.php');

/**
 * Tests for the Tutor-IA HTTP client session bookkeeping.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\httpclient\tutoria_api
 * @covers     \local_dttutor\session_store
 */
final class tutoria_api_test extends \advanced_testcase {
    /**
     * A remote "session started" response.
     *
     * @param string $sessionid
     * @return array
     */
    private function session_response(string $sessionid): array {
        return ['session_id' => $sessionid, 'ready' => true, 'session_ttl_seconds' => 604800];
    }

    public function test_sessions_cache_definition_resolves(): void {
        $this->resetAfterTest();
        $cache = \cache::make('local_dttutor', 'sessions');

        $this->assertInstanceOf(\cache_application::class, $cache);
        $this->assertTrue($cache->set('session_v2_2_3', ['session_id' => 'x']));
        $this->assertSame(['session_id' => 'x'], $cache->get('session_v2_2_3'));
    }

    public function test_start_session_v2_persists_a_row_and_reuses_the_cached_session(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $fake = new fake_ai_client();
        $fake->enqueue($this->session_response('remote-1'));
        $api = new tutoria_api($fake);

        $first = $api->start_session_v2((int)$course->id, (int)$user->id);
        $second = $api->start_session_v2((int)$course->id, (int)$user->id);

        $this->assertSame('remote-1', $first['session_id']);
        $this->assertSame('remote-1', $second['session_id']);
        $this->assertSame(['POST /chat/start/v2'], $fake->get_call_signatures());
        $row = $DB->get_record('local_dttutor_session', ['userid' => $user->id, 'courseid' => $course->id], '*', MUST_EXIST);
        $this->assertSame('remote-1', $row->remotesessionid);
        $this->assertEquals(0, $row->cmid);
        $this->assertSame(1, $DB->count_records('local_dttutor_session'));
    }

    public function test_start_session_v2_persists_the_module_id(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $fake = new fake_ai_client();
        $fake->enqueue($this->session_response('remote-cm'));
        $api = new tutoria_api($fake);

        $api->start_session_v2((int)$course->id, (int)$user->id, (int)$page->cmid);

        $this->assertSame(['POST /chat/start/v2'], $fake->get_call_signatures());
        $row = $DB->get_record('local_dttutor_session', ['remotesessionid' => 'remote-cm'], '*', MUST_EXIST);
        $this->assertEquals($page->cmid, $row->cmid);
        $this->assertEquals($user->id, $row->userid);
    }

    public function test_forget_cached_session_clears_the_v2_key(): void {
        $this->resetAfterTest();
        $fake = new fake_ai_client();
        $fake->enqueue($this->session_response('remote-forget'));
        $api = new tutoria_api($fake);
        $api->start_session_v2(2, 3, 7);
        $cache = \cache::make('local_dttutor', 'sessions');
        $this->assertSame('remote-forget', $cache->get('session_v2_2_3_7')['session_id']);

        $api->forget_cached_session(2, 3, 7);

        $this->assertFalse($cache->get('session_v2_2_3_7'));
    }

    public function test_reset_session_v2_replaces_the_stored_row(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $fake = new fake_ai_client();
        $fake->enqueue($this->session_response('remote-old'));
        $fake->enqueue(['deleted' => true]);
        $fake->enqueue($this->session_response('remote-new'));
        $api = new tutoria_api($fake);

        $api->start_session_v2((int)$course->id, (int)$user->id);
        $fresh = $api->reset_session_v2((int)$course->id, (int)$user->id);

        $this->assertSame('remote-new', $fresh['session_id']);
        $this->assertSame(
            ['POST /chat/start/v2', 'DELETE /chat/session/remote-old', 'POST /chat/start/v2'],
            $fake->get_call_signatures()
        );
        $rows = $DB->get_records('local_dttutor_session', ['userid' => $user->id, 'courseid' => $course->id]);
        $this->assertCount(1, $rows);
        $this->assertSame('remote-new', reset($rows)->remotesessionid);
    }

    public function test_delete_session_removes_the_stored_row(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $fake = new fake_ai_client();
        $fake->enqueue($this->session_response('remote-del'));
        $fake->enqueue(['deleted' => true]);
        $api = new tutoria_api($fake);
        $api->start_session_v2((int)$course->id, (int)$user->id);

        $api->delete_session('remote-del');

        $this->assertFalse($DB->record_exists('local_dttutor_session', ['remotesessionid' => 'remote-del']));
    }

    public function test_session_without_id_is_not_stored(): void {
        global $DB;
        $this->resetAfterTest();
        $fake = new fake_ai_client();
        $fake->enqueue(['ready' => false]);
        $api = new tutoria_api($fake);

        $api->start_session_v2(2, 3);

        $this->assertSame(0, $DB->count_records('local_dttutor_session'));
    }
}
