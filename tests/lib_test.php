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

/**
 * Course navigation entry point and role resolution helper.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_dttutor_extend_navigation_course
 * @covers     ::local_dttutor_get_user_role
 * @covers     ::local_dttutor_pluginfile
 */
final class lib_test extends \advanced_testcase {
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/dttutor/lib.php');
    }

    /**
     * Run the course navigation callback and report whether it added the management link.
     *
     * @param \stdClass $course
     * @return bool
     */
    private function navigation_has_manage_link(\stdClass $course): bool {
        $parent = \navigation_node::create('Course settings', null, \navigation_node::TYPE_COURSE, null, 'courseadmin');
        local_dttutor_extend_navigation_course($parent, $course, \context_course::instance((int)$course->id));
        return $parent->get('dttutormanage', \navigation_node::TYPE_SETTING) !== false;
    }

    /**
     * MDL-INT-025: whoever can edit the course reaches the tutor management page from the course menu.
     */
    public function test_the_management_link_is_offered_to_an_editing_teacher(): void {
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', true);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $this->assertTrue($this->navigation_has_manage_link($course));
    }

    /**
     * MDL-INT-025: a student never sees the management link.
     */
    public function test_the_management_link_is_hidden_from_a_student(): void {
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', true);
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->assertFalse($this->navigation_has_manage_link($course));
    }

    /**
     * MDL-INT-025: the link disappears when the chat is switched off for the whole site.
     */
    public function test_the_management_link_disappears_when_the_chat_is_off_site_wide(): void {
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', false);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $this->assertFalse($this->navigation_has_manage_link($course));
    }

    /**
     * MDL-INT-030: someone who can manage the activities of the course is handled as a teacher.
     */
    public function test_a_user_who_manages_activities_is_reported_as_a_teacher(): void {
        global $COURSE;
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $COURSE = $course;

        $this->assertEquals('teacher', local_dttutor_get_user_role());
    }

    /**
     * MDL-INT-030: an enrolled student is handled as a student.
     */
    public function test_an_enrolled_student_is_reported_as_a_student(): void {
        global $COURSE;
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $COURSE = $course;

        $this->assertEquals('student', local_dttutor_get_user_role());
    }

    /**
     * MDL-INT-030: the site administrator is reported with their own role.
     */
    public function test_the_site_administrator_is_reported_as_admin(): void {
        global $COURSE;
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $COURSE = $course;

        $this->assertEquals('admin', local_dttutor_get_user_role());
    }

    /**
     * MDL-INT-030: outside a course the role falls back to student.
     */
    public function test_outside_a_course_the_role_falls_back_to_student(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals('student', local_dttutor_get_user_role());
    }

    /**
     * The custom avatar is not served while the plugin is disabled in the Datacurso AI provider.
     */
    public function test_the_custom_avatar_is_not_served_when_the_plugin_is_disabled(): void {
        $context = \context_system::instance();
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_dttutor',
            'filearea' => 'customavatar',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'avatar.png',
        ], 'image');
        \aiprovider_datacurso\local\plugin_settings::set_enabled('local_dttutor', false);

        $this->assertFalse(local_dttutor_pluginfile(null, null, $context, 'customavatar', [0, 'avatar.png'], false));
    }

    /**
     * The management page refuses through the provider right after the login check.
     *
     * Page scripts cannot run inside PHPUnit, so the contract is checked on the script itself.
     */
    public function test_the_management_page_requires_the_plugin_enabled(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/dttutor/manage.php');

        $login = strpos($source, 'require_login($courseid);');
        $gate = strpos($source, "plugin_settings::require_enabled('local_dttutor');");
        $this->assertNotFalse($login);
        $this->assertNotFalse($gate);
        $this->assertGreaterThan($login, $gate);
        $this->assertStringNotContainsString('error_tutor_disabled_site', $source);
    }
}
