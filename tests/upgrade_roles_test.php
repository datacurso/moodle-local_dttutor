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

use local_dttutor\upgrade\roles;

/**
 * Tests for the upgrade step that revokes system-level capabilities granted by earlier versions.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\upgrade\roles
 */
final class upgrade_roles_test extends \advanced_testcase {
    /**
     * Reproduce the legacy grants made by upgrade steps 2026012201 and 2026060104.
     *
     * @return \context_system
     */
    private function apply_legacy_grants(): \context_system {
        $systemcontext = \context_system::instance();
        foreach (['editingteacher', 'teacher'] as $archetype) {
            foreach (get_archetype_roles($archetype) as $role) {
                assign_capability('moodle/course:view', CAP_ALLOW, $role->id, $systemcontext->id, true);
            }
        }
        foreach (get_archetype_roles('user') as $role) {
            assign_capability('local/dttutor:use', CAP_ALLOW, $role->id, $systemcontext->id, true);
        }
        return $systemcontext;
    }

    public function test_course_view_is_revoked_from_teacher_archetypes_at_system_context(): void {
        global $DB;
        $this->resetAfterTest();
        $systemcontext = $this->apply_legacy_grants();
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        role_assign($editingteacherrole->id, $teacher->id, \context_coursecat::instance($category->id)->id);
        $coursecontext = \context_course::instance($course->id);
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext, $teacher));

        roles::revoke_system_capabilities();

        $this->assertFalse(has_capability('moodle/course:view', $coursecontext, $teacher));
        foreach (['editingteacher', 'teacher'] as $archetype) {
            foreach (get_archetype_roles($archetype) as $role) {
                $this->assertFalse($DB->record_exists('role_capabilities', [
                    'roleid' => $role->id,
                    'capability' => 'moodle/course:view',
                    'contextid' => $systemcontext->id,
                ]), "Role {$role->shortname} still has moodle/course:view at system context");
            }
        }
    }

    public function test_dttutor_use_is_revoked_from_user_archetype_at_system_context(): void {
        global $DB;
        $this->resetAfterTest();
        $systemcontext = $this->apply_legacy_grants();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $outsider = $this->getDataGenerator()->create_user();
        $this->assertTrue(has_capability('local/dttutor:use', $coursecontext, $outsider));

        roles::revoke_system_capabilities();

        $this->assertFalse(has_capability('local/dttutor:use', $coursecontext, $outsider));
        foreach (get_archetype_roles('user') as $role) {
            $this->assertFalse($DB->record_exists('role_capabilities', [
                'roleid' => $role->id,
                'capability' => 'local/dttutor:use',
                'contextid' => $systemcontext->id,
            ]));
        }
    }

    public function test_enrolled_student_keeps_dttutor_use_after_revocation(): void {
        $this->resetAfterTest();
        $this->apply_legacy_grants();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        roles::revoke_system_capabilities();

        $this->assertTrue(has_capability('local/dttutor:use', \context_course::instance($course->id), $student));
    }

    public function test_explicit_prohibit_is_preserved_and_call_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();
        $systemcontext = \context_system::instance();
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);
        assign_capability('moodle/course:view', CAP_PROHIBIT, $teacherrole->id, $systemcontext->id, true);

        roles::revoke_system_capabilities();
        roles::revoke_system_capabilities();

        $this->assertEquals(CAP_PROHIBIT, $DB->get_field('role_capabilities', 'permission', [
            'roleid' => $teacherrole->id,
            'capability' => 'moodle/course:view',
            'contextid' => $systemcontext->id,
        ]));
    }

    public function test_capability_definition_no_longer_targets_user_archetype(): void {
        global $CFG;
        $capabilities = [];
        require($CFG->dirroot . '/local/dttutor/db/access.php');

        $this->assertSame(CONTEXT_COURSE, $capabilities['local/dttutor:use']['contextlevel']);
        $this->assertArrayNotHasKey('user', $capabilities['local/dttutor:use']['archetypes']);
        $this->assertArrayHasKey('student', $capabilities['local/dttutor:use']['archetypes']);
    }
}
