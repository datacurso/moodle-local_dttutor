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
use local_dttutor\hook\chat_hook;

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
        $this->resetAfterTest();
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
     * S7: the upgrade step removes the former "Enabled" setting.
     *
     * @covers ::xmldb_local_dttutor_upgrade
     * @return void
     */
    public function test_upgrade_removes_the_former_enabled_setting(): void {
        $this->resetAfterTest();
        set_config('enabled', '1', 'local_dttutor');
        set_config('version', 2026110400, 'local_dttutor');

        $this->assertTrue(xmldb_local_dttutor_upgrade(2026110400));

        $this->assertFalse(get_config('local_dttutor', 'enabled'));
        $this->assertEquals(2026110500, get_config('local_dttutor', 'version'));
    }
}
