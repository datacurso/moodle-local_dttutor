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

use aiprovider_datacurso\local\plugin_settings;
use core\hook\output\before_footer_html_generation;
use local_dttutor\fixtures\provider_dependency_trait;
use local_dttutor\hook\chat_hook;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/provider_dependency_trait.php');

/**
 * Tests that the plugin state comes from the Datacurso AI provider instead of the plugin's own setting.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\hook\chat_hook
 */
final class plugin_state_test extends \advanced_testcase {
    use provider_dependency_trait;

    /**
     * Load the plugin library and upgrade code under test.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/local/dttutor/lib.php');
        require_once($CFG->libdir . '/adminlib.php');
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/dttutor/db/upgrade.php');
    }

    /**
     * Build the course navigation of a course for the current user.
     *
     * @param \stdClass $course The course.
     * @return \navigation_node The node the plugin extends.
     */
    private function extend_course_navigation(\stdClass $course): \navigation_node {
        $node = \navigation_node::create('course');
        local_dttutor_extend_navigation_course($node, $course, \context_course::instance($course->id));
        return $node;
    }

    /**
     * Render the footer hook on a course page where the tutor is enabled for the course.
     *
     * @return string The HTML the plugin added to the footer.
     */
    private function render_footer_on_enabled_course(): string {
        global $PAGE;
        $this->setAdminUser();
        // The chat is only offered when an AI provider instance can serve it.
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider();
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $PAGE->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));
        $PAGE->set_course($course);
        $PAGE->set_pagelayout('course');

        $hook = new before_footer_html_generation($PAGE->get_renderer('core'));
        chat_hook::before_footer_html_generation($hook);
        return $hook->get_output();
    }

    /**
     * S6: the "Manage tutor" course navigation entry is shown while the provider keeps the plugin enabled.
     *
     * @covers ::local_dttutor_extend_navigation_course
     * @return void
     */
    public function test_navigation_entry_is_shown_when_enabled_in_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        set_config('enabled', '0', 'local_dttutor');

        $node = $this->extend_course_navigation($course);

        $this->assertNotFalse($node->find('dttutormanage', \navigation_node::TYPE_SETTING));
    }

    /**
     * S6: the "Manage tutor" course navigation entry is hidden when the provider disables the plugin.
     *
     * @covers ::local_dttutor_extend_navigation_course
     * @return void
     */
    public function test_navigation_entry_is_hidden_when_disabled_in_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        plugin_settings::set_enabled('local_dttutor', false);

        $node = $this->extend_course_navigation($course);

        $this->assertFalse($node->find('dttutormanage', \navigation_node::TYPE_SETTING));
    }

    /**
     * S6: the floating chat is rendered while the provider keeps the plugin enabled.
     *
     * @return void
     */
    public function test_floating_chat_is_rendered_when_enabled_in_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        set_config('enabled', '0', 'local_dttutor');

        $this->assertNotSame('', $this->render_footer_on_enabled_course());
    }

    /**
     * S6: the floating chat is not rendered when the provider disables the plugin.
     *
     * @return void
     */
    public function test_floating_chat_is_not_rendered_when_disabled_in_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        plugin_settings::set_enabled('local_dttutor', false);

        $this->assertSame('', $this->render_footer_on_enabled_course());
    }

    /**
     * S7: the plugin settings page no longer defines its own "Enabled" setting.
     *
     * @return void
     */
    public function test_settings_page_has_no_enabled_setting(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $page = admin_get_root(true, true)->locate('local_dttutor');

        $this->assertInstanceOf(\admin_settingpage::class, $page);
        $this->assertTrue(property_exists($page->settings, 'local_dttutoravatar'));
        $this->assertFalse(property_exists($page->settings, 'local_dttutorenabled'));
    }

    /**
     * Moodle Workplace: disabling the tutor for tenant A in the provider leaves the users of tenant B with it.
     *
     * @covers \local_dttutor\local\provider_state::is_enabled
     * @return void
     */
    public function test_tenant_a_disabled_while_tenant_b_enabled(): void {
        $this->require_tenancy();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $tenanta = (int) $generator->create_tenant()->id;
        $tenantb = (int) $generator->create_tenant()->id;
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $generator->allocate_user($usera->id, $tenanta);
        $generator->allocate_user($userb->id, $tenantb);

        plugin_settings::set_enabled('local_dttutor', false, $tenanta);
        plugin_settings::set_enabled('local_dttutor', true, $tenantb);

        $this->setUser($usera);
        $this->assertFalse(local\provider_state::is_enabled());
        $this->setUser($userb);
        $this->assertTrue(local\provider_state::is_enabled());
    }

    /**
     * S7: the upgrade step removes the former "Enabled" setting.
     *
     * @covers ::xmldb_local_dttutor_upgrade
     * @return void
     */
    public function test_upgrade_removes_the_former_enabled_setting(): void {
        $this->resetAfterTest();
        set_config('enabled', '1', 'local_dttutor');
        set_config('version', 2026110901, 'local_dttutor');

        $this->assertTrue(xmldb_local_dttutor_upgrade(2026110901));

        $this->assertFalse(get_config('local_dttutor', 'enabled'));
        $this->assertEquals(2026110902, get_config('local_dttutor', 'version'));
    }

    /**
     * The upgrade copies the former site value into the site default of the provider before deleting it.
     *
     * @covers ::local_dttutor_upgrade_move_switch_to_provider
     * @return void
     */
    public function test_upgrade_copies_the_former_setting_to_the_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        unset_config(plugin_settings::CONFIG_PREFIX . 'local_dttutor', 'aiprovider_datacurso');
        set_config('enabled', '0', 'local_dttutor');
        set_config('version', 2026110901, 'local_dttutor');

        xmldb_local_dttutor_upgrade(2026110901);

        // Without a current user the provider reads its site default.
        $this->assertFalse(plugin_settings::is_enabled('local_dttutor'));
        $this->assertFalse(get_config('local_dttutor', 'enabled'));
    }

    /**
     * The upgrade never overwrites a value the provider already holds.
     *
     * @covers ::local_dttutor_upgrade_move_switch_to_provider
     * @return void
     */
    public function test_upgrade_does_not_overwrite_the_provider(): void {
        $this->require_provider();
        $this->resetAfterTest();
        plugin_settings::set_enabled('local_dttutor', true, null);
        set_config('enabled', '0', 'local_dttutor');
        set_config('version', 2026110901, 'local_dttutor');

        xmldb_local_dttutor_upgrade(2026110901);

        $this->assertTrue(plugin_settings::is_enabled('local_dttutor'));
        $this->assertFalse(get_config('local_dttutor', 'enabled'));
    }

    /**
     * Skips the current test when Moodle Workplace tenancy or the Datacurso AI provider is not installed.
     *
     * @return void
     */
    private function require_tenancy(): void {
        if (!class_exists('\\tool_tenant\\tenancy')) {
            $this->markTestSkipped('Moodle Workplace (tool_tenant) is not installed.');
        }
        $this->require_provider();
    }
}
