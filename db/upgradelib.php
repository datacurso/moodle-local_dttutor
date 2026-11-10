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
 * Helpers used by the upgrade steps of local_dttutor.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Moves the former "Enabled" switch of the tutor into the Datacurso AI provider.
 *
 * The tutor only had a site wide switch (local_dttutor/enabled), never a tenant one, so only the provider site
 * default can receive it. A value the provider already holds is never overwritten. The former value is deleted.
 *
 * @return void
 */
function local_dttutor_upgrade_move_switch_to_provider(): void {
    $component = 'local_dttutor';
    $site = get_config($component, 'enabled');

    if (
        $site !== false && $site !== ''
        && class_exists(\aiprovider_datacurso\local\plugin_settings::class)
        && get_config('aiprovider_datacurso', 'plugin_enabled_' . $component) === false
    ) {
        \aiprovider_datacurso\local\plugin_settings::set_enabled($component, (bool) $site, null);
    }

    unset_config('enabled', $component);
}
