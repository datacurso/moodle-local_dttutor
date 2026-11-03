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
 * Bootstrap class names for the Bootstrap release the running Moodle ships.
 *
 * Moodle 4.5 ships Bootstrap 4, which has no visually-hidden, me-* or form-switch classes.
 * Moodle 5.0 moved to Bootstrap 5 and keeps the Bootstrap 4 names only through a deprecated
 * compatibility layer that flags them on Behat sites and in theme designer mode, and that a
 * later release removes. The templates take their class names from here so that the same
 * markup is correct on both.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bootstrap_classes {
    /** @var int First Moodle branch that ships Bootstrap 5. */
    public const BOOTSTRAP5_BRANCH = 500;

    /**
     * Whether the running Moodle ships Bootstrap 5.
     *
     * @param int|null $branch Moodle branch to check (e.g. 405, 503); the running one when null.
     * @return bool
     */
    public static function is_bootstrap5(?int $branch = null): bool {
        global $CFG;

        $branch ??= (int)$CFG->branch;
        return $branch >= self::BOOTSTRAP5_BRANCH;
    }

    /**
     * Class names for the templates, keyed by purpose.
     *
     * @param int|null $branch Moodle branch to resolve for; the running one when null.
     * @return array<string, string>
     */
    public static function get(?int $branch = null): array {
        if (self::is_bootstrap5($branch)) {
            return [
                'visuallyhidden' => 'visually-hidden',
                'iconspacing1' => 'me-1',
                'iconspacing2' => 'me-2',
                'switchwrapper' => 'form-check form-switch',
                'switchinput' => 'form-check-input',
                'switchlabel' => 'form-check-label',
            ];
        }

        return [
            'visuallyhidden' => 'sr-only',
            'iconspacing1' => 'mr-1',
            'iconspacing2' => 'mr-2',
            'switchwrapper' => 'custom-control custom-switch',
            'switchinput' => 'custom-control-input',
            'switchlabel' => 'custom-control-label',
        ];
    }
}
