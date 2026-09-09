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

use local_dttutor\upgrade\service_bot;

/**
 * Tests for the upgrade step that retires the legacy service bot user.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\upgrade\service_bot
 */
final class upgrade_bot_removal_test extends \advanced_testcase {
    /**
     * Create the legacy bot user enrolled as editing teacher in two courses.
     *
     * @param bool $storeconfig Whether to store the id in the serviceuserid config.
     * @return array [bot user, course A, course B]
     */
    private function create_enrolled_bot(bool $storeconfig = true): array {
        $generator = $this->getDataGenerator();
        $bot = $generator->create_user(['username' => 'tutoriabot_datacurso', 'email' => 'tutoria@example.com']);
        $coursea = $generator->create_course();
        $courseb = $generator->create_course();
        $generator->enrol_user($bot->id, $coursea->id, 'editingteacher');
        $generator->enrol_user($bot->id, $courseb->id, 'editingteacher');
        if ($storeconfig) {
            set_config('serviceuserid', $bot->id, 'local_dttutor');
        }
        return [$bot, $coursea, $courseb];
    }

    public function test_bot_is_suspended_unenrolled_and_config_removed(): void {
        global $DB;
        $this->resetAfterTest();
        [$bot, $coursea, $courseb] = $this->create_enrolled_bot();
        $this->assertTrue(is_enrolled(\context_course::instance($coursea->id), $bot->id));

        service_bot::retire();

        $record = $DB->get_record('user', ['id' => $bot->id], '*', MUST_EXIST);
        $this->assertEquals(1, $record->suspended);
        $this->assertEquals(0, $record->deleted);
        $this->assertFalse(is_enrolled(\context_course::instance($coursea->id), $bot->id));
        $this->assertFalse(is_enrolled(\context_course::instance($courseb->id), $bot->id));
        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $bot->id]));
        $this->assertFalse($DB->record_exists('role_assignments', ['userid' => $bot->id]));
        $this->assertFalse(get_config('local_dttutor', 'serviceuserid'));
    }

    public function test_bot_is_found_by_username_when_config_is_missing(): void {
        global $DB;
        $this->resetAfterTest();
        [$bot] = $this->create_enrolled_bot(false);

        service_bot::retire();

        $this->assertEquals(1, $DB->get_field('user', 'suspended', ['id' => $bot->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['userid' => $bot->id]));
    }

    public function test_other_users_are_untouched(): void {
        global $DB;
        $this->resetAfterTest();
        [, $coursea] = $this->create_enrolled_bot();
        $student = $this->getDataGenerator()->create_and_enrol($coursea, 'student');

        service_bot::retire();

        $this->assertTrue(is_enrolled(\context_course::instance($coursea->id), $student->id));
        $this->assertEquals(0, $DB->get_field('user', 'suspended', ['id' => $student->id]));
    }

    public function test_retire_is_a_noop_when_bot_is_absent(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('serviceuserid', 999999, 'local_dttutor');
        $usercount = $DB->count_records('user');

        service_bot::retire();

        $this->assertSame($usercount, $DB->count_records('user'));
        $this->assertFalse(get_config('local_dttutor', 'serviceuserid'));
    }
}
