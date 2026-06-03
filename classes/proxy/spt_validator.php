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

/**
 * SPT (Signed Permission Token) validator for dttutor.
 *
 * Validates SPT signature, TTL, and sesskey binding.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates Signed Permission Tokens from the AI gateway.
 */
class spt_validator {

    /**
     * Validate an SPT payload.
     *
     * @param  array $spt The signed permission token.
     * @return string     The resolved role (admin|manager|teacher|student).
     * @throws \moodle_exception
     */
    public static function validate(array $spt): string {
        // Check sesskey binding.
        $expectedhash = substr(hash('sha256', sesskey()), 0, 16);
        if (!hash_equals($expectedhash, $spt['sesskey_hash'] ?? '')) {
            throw new \moodle_exception('invalid_session', 'local_dttutor');
        }

        // Check TTL.
        if (time() >= ($spt['expires'] ?? 0)) {
            throw new \moodle_exception('token_expired', 'local_dttutor');
        }

        // Reconstruct and verify signature.
        $secret = get_config('local_dttutor', 'spt_secret') ?: 'dttutor-spt-secret';
        $payload = $spt;
        unset($payload['signature']);
        ksort($payload);
        $expected = hash_hmac('sha256', json_encode($payload), $secret);

        if (!hash_equals($expected, $spt['signature'] ?? '')) {
            throw new \moodle_exception('invalid_signature', 'local_dttutor');
        }

        return $spt['role'] ?? 'student';
    }

    /**
     * Get tool definitions for a given role in OpenAI function-calling format.
     *
     * @param  string $role User role: admin, manager, teacher, student.
     * @return array        Array of OpenAI-style tool definitions.
     */
    public static function get_tool_definitions(string $role): array {
        return \local_dttutor_get_tool_definitions($role);
    }
}
