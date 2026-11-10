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

use local_dttutor\fixtures\provider_dependency_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/provider_dependency_trait.php');

/**
 * Tests for the plugin state read from the Datacurso AI provider on every page.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\local\provider_state
 */
final class provider_state_test extends \advanced_testcase {
    use provider_dependency_trait;

    /**
     * A provider too old to keep the plugin state leaves the tutor disabled instead of failing.
     *
     * @return void
     */
    public function test_missing_provider_class_reports_the_tutor_disabled(): void {
        $this->assertFalse(provider_state::is_enabled('\\aiprovider_datacurso\\local\\missing_plugin_settings'));
    }

    /**
     * The tutor is enabled while the provider keeps it enabled.
     *
     * @return void
     */
    public function test_follows_the_provider_when_enabled(): void {
        $this->require_provider();
        $this->resetAfterTest();
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', true);

        $this->assertTrue(provider_state::is_enabled());
    }

    /**
     * The tutor is disabled while the provider disables it.
     *
     * @return void
     */
    public function test_follows_the_provider_when_disabled(): void {
        $this->require_provider();
        $this->resetAfterTest();
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', false);

        $this->assertFalse(provider_state::is_enabled());
    }
}
