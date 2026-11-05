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

use local_dttutor\proxy\document_read_failure;

/**
 * Whether a failure means that the site has no licence to reach the AI service with.
 *
 * Without a licence key, or on Moodle 5.0+ without an enabled provider instance holding one, the
 * client of the provider cannot be built and the request never leaves the site. No retry changes
 * that until an administrator configures the provider.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class licence {
    /**
     * @var string[] Error codes of the AI provider when the site has no licence to use it.
     *
     * invalidlicensekey: no licence key (every release). On Moodle 5.0+, instance_disabled: no
     * enabled provider instance, and licensekey_missing: an enabled instance without a key.
     */
    private const MISSING_REASONS = ['invalidlicensekey', 'instance_disabled', 'licensekey_missing'];

    /**
     * Whether the failure comes from a site without a licence for the AI provider.
     *
     * @param \Throwable $error What the request failed with.
     * @return bool
     */
    public static function is_missing(\Throwable $error): bool {
        return in_array(document_read_failure::reason_of($error), self::MISSING_REASONS, true);
    }
}
