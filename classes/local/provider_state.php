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

/**
 * Whether the Datacurso AI provider lets the tutor run, read from the callbacks of every page.
 *
 * The footer hook and the library callbacks run on every page, including the upgrade pages that
 * report a missing dependency. When the code of the tutor is deployed before the provider that
 * holds its state, the provider class does not exist yet: the tutor then stays out of the page
 * instead of breaking it, so the administrator can still reach the upgrade.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_state {
    /** @var string Class of the provider that keeps the state of each suite plugin. */
    public const SETTINGS_CLASS = '\\aiprovider_datacurso\\local\\plugin_settings';

    /**
     * Whether the tutor is enabled in the Datacurso AI provider.
     *
     * @param string $settingsclass Class that keeps the plugin state; only tests name another one.
     * @return bool False when the provider is too old to keep the state, or the tutor is disabled there.
     */
    public static function is_enabled(string $settingsclass = self::SETTINGS_CLASS): bool {
        if (!class_exists($settingsclass)) {
            return false;
        }
        return $settingsclass::is_enabled('local_dttutor');
    }
}
