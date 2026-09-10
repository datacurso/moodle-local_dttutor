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

namespace local_dttutor;

use local_dttutor\fixtures\racing_session_store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/racing_session_store.php');

/**
 * Tests for the session store: upsert semantics and best-effort remote deletion.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\session_store
 */
final class session_store_test extends \advanced_testcase {
    public function test_purge_removes_local_rows_when_the_remote_client_cannot_be_built(): void {
        global $DB;
        $this->resetAfterTest();
        // No DI binding and no license key: constructing the real client throws.
        unset_config('licensekey', 'aiprovider_datacurso');
        session_store::upsert(3, 7, null, 'remote-a');
        session_store::upsert(4, 7, null, 'remote-b');
        session_store::upsert(3, 8, null, 'remote-other-course');

        session_store::purge('courseid = :courseid', ['courseid' => 7]);

        $debug = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertCount(1, $debug);
        $this->assertStringContainsString('SESSION_PURGE_REMOTE_UNAVAILABLE', $debug[0]->message);
        $this->assertStringContainsString(\moodle_exception::class, $debug[0]->message);
        $this->assertStringNotContainsString('remote-a', $debug[0]->message);
        $this->assertSame(0, $DB->count_records(session_store::TABLE, ['courseid' => 7]));
        $this->assertSame(1, $DB->count_records(session_store::TABLE, ['courseid' => 8]));
    }

    public function test_purge_without_matching_rows_never_resolves_the_remote_client(): void {
        $this->resetAfterTest();
        unset_config('licensekey', 'aiprovider_datacurso');

        session_store::purge('courseid = :courseid', ['courseid' => 99]);

        $this->assertDebuggingNotCalled();
    }

    public function test_upsert_recovers_when_a_concurrent_request_inserts_the_same_key_first(): void {
        global $DB;
        $this->resetAfterTest();
        // The duplicate must hit the real unique index; a wrapping test transaction would mask it on some drivers.
        $this->preventResetByRollback();

        racing_session_store::upsert(3, 7, null, 'remote-second');

        $rows = $DB->get_records(session_store::TABLE, ['userid' => 3, 'courseid' => 7]);
        $this->assertCount(1, $rows);
        $this->assertSame('remote-second', reset($rows)->remotesessionid, 'Last writer wins, as for a plain update');
    }

    public function test_upsert_treats_a_null_cmid_and_zero_as_the_same_key(): void {
        global $DB;
        $this->resetAfterTest();

        session_store::upsert(3, 7, null, 'first');
        session_store::upsert(3, 7, 0, 'second');

        $rows = $DB->get_records(session_store::TABLE, ['userid' => 3, 'courseid' => 7]);
        $this->assertCount(1, $rows);
        $this->assertSame(0, (int)reset($rows)->cmid);
        $this->assertSame('second', reset($rows)->remotesessionid);
    }
}
