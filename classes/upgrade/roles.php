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

namespace local_dttutor\upgrade;

/**
 * Reverts the system-level capability grants made by earlier plugin versions.
 *
 * Versions up to 2.0.8 granted moodle/course:view to every role with the
 * teacher/editingteacher archetype and local/dttutor:use to every role with
 * the user archetype, both at system context. The tutor is now authorised in
 * course context, so those grants are removed.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roles {
    /**
     * Remove the legacy system-context CAP_ALLOW grants.
     *
     * Only CAP_ALLOW rows are removed: an explicit prevent/prohibit set by an
     * administrator is left untouched. Safe to call repeatedly.
     */
    public static function revoke_system_capabilities(): void {
        $systemcontext = \context_system::instance();

        foreach (['editingteacher', 'teacher'] as $archetype) {
            foreach (get_archetype_roles($archetype) as $role) {
                self::unassign_if_allowed('moodle/course:view', (int)$role->id, $systemcontext);
            }
        }

        foreach (get_archetype_roles('user') as $role) {
            self::unassign_if_allowed('local/dttutor:use', (int)$role->id, $systemcontext);
        }
    }

    /**
     * Unassign a capability from a role in a context when it is currently CAP_ALLOW.
     *
     * @param string   $capability The capability name.
     * @param int      $roleid     The role id.
     * @param \context $context    The context of the grant.
     */
    private static function unassign_if_allowed(string $capability, int $roleid, \context $context): void {
        global $DB;

        $isallowed = $DB->record_exists('role_capabilities', [
            'roleid' => $roleid,
            'capability' => $capability,
            'contextid' => $context->id,
            'permission' => CAP_ALLOW,
        ]);
        if ($isallowed) {
            unassign_capability($capability, $roleid, $context);
        }
    }
}
