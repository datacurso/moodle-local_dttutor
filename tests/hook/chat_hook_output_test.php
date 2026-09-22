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

namespace local_dttutor\hook;

use core\hook\output\before_footer_html_generation;
use local_dttutor\course_config;

/**
 * What the footer hook adds to a page, and when it adds nothing at all.
 *
 * The hook is driven through its public entry point and only its output is inspected,
 * which is what a browser would receive.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\hook\chat_hook
 */
final class chat_hook_output_test extends \advanced_testcase {
    /** @var string Marker of the floating toggle button in the generated HTML. */
    private const TOGGLE_MARKER = 'data-action="tutor-ia-toggle"';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create a course with the tutor enabled site wide and for the course itself.
     *
     * @return \stdClass The course record.
     */
    private function create_enabled_course(): \stdClass {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        return $course;
    }

    /**
     * Point the current page at the main page of a course.
     *
     * @param \stdClass $course
     * @param string $pagelayout
     * @param string $pagetype
     */
    private function set_course_page(
        \stdClass $course,
        string $pagelayout = 'course',
        string $pagetype = 'course-view'
    ): void {
        global $PAGE;
        $PAGE->set_context(\context_course::instance((int)$course->id));
        $PAGE->set_course($course);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_pagelayout($pagelayout);
        $PAGE->set_pagetype($pagetype);
    }

    /**
     * Point the current page at an activity of a course.
     *
     * @param \stdClass $course
     * @param int $cmid
     * @param string $modname
     */
    private function set_activity_page(\stdClass $course, int $cmid, string $modname): void {
        global $PAGE;
        $cm = get_coursemodule_from_id($modname, $cmid, 0, false, MUST_EXIST);
        $PAGE->set_cm($cm, $course);
        $PAGE->set_url('/mod/' . $modname . '/view.php', ['id' => $cmid]);
        $PAGE->set_pagelayout('incourse');
        $PAGE->set_pagetype('mod-' . $modname . '-view');
    }

    /**
     * Everything the plugin adds to the footer of the current page.
     *
     * @return string
     */
    private function footer_output(): string {
        global $PAGE;
        $PAGE->initialise_theme_and_output();
        $hook = new before_footer_html_generation($PAGE->get_renderer('core'));
        chat_hook::before_footer_html_generation($hook);
        return $hook->get_output();
    }

    /**
     * MDL-INT-026: the three conditions met, the course page carries the floating button.
     */
    public function test_the_button_reaches_a_course_page_when_every_condition_is_met(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString(self::TOGGLE_MARKER, $this->footer_output());
    }

    /**
     * MDL-INT-026: the chat switched off site wide removes the button from every course.
     */
    public function test_no_button_when_the_chat_is_off_site_wide(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        set_config('enabled', 0, 'local_dttutor');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-026: the tutor switched off for the course removes the button from that course.
     */
    public function test_no_button_when_the_tutor_is_off_for_the_course(): void {
        $course = $this->create_enabled_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 0]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-027: someone without the capability in the course never sees the button.
     */
    public function test_no_button_for_a_user_without_the_capability(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $roleid = $this->getDataGenerator()->create_role();
        role_assign($roleid, $student->id, \context_course::instance((int)$course->id));
        assign_capability(
            'local/dttutor:use',
            CAP_PROHIBIT,
            $roleid,
            \context_course::instance((int)$course->id)->id,
            true
        );
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-027: a user with no role in the course never sees the button.
     */
    public function test_no_button_for_a_user_who_is_not_in_the_course(): void {
        $course = $this->create_enabled_course();
        $outsider = $this->getDataGenerator()->create_user();
        $this->setUser($outsider);
        $this->set_course_page($course);

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-027: the button stays out of the pages of a quiz.
     */
    public function test_no_button_on_a_quiz_page(): void {
        $course = $this->create_enabled_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_activity_page($course, (int)$quiz->cmid, 'quiz');

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-026: activities other than quizzes keep the button.
     */
    public function test_the_button_reaches_an_activity_that_is_not_a_quiz(): void {
        $course = $this->create_enabled_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_activity_page($course, (int)$page->cmid, 'page');

        $this->assertStringContainsString(self::TOGGLE_MARKER, $this->footer_output());
    }

    /**
     * Page layouts that must never carry the button.
     *
     * @return array<string, array{0: string}>
     */
    public static function excluded_layout_provider(): array {
        return [
            'embedded' => ['embedded'],
            'popup' => ['popup'],
            'frametop' => ['frametop'],
        ];
    }

    /**
     * MDL-INT-027: embedded and pop-up pages do not repeat the button.
     *
     * @param string $pagelayout
     * @dataProvider excluded_layout_provider
     */
    public function test_no_button_on_embedded_or_popup_pages(string $pagelayout): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course, $pagelayout);

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-027: pages outside any course do not carry the button.
     */
    public function test_no_button_outside_a_course(): void {
        global $PAGE;
        $this->create_enabled_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/my/index.php');
        $PAGE->set_pagelayout('mydashboard');
        $PAGE->set_pagetype('my-index');

        $this->assertSame('', $this->footer_output());
    }

    /**
     * MDL-INT-028: with no custom image the gallery avatar chosen by the administrator is used.
     */
    public function test_the_gallery_avatar_chosen_by_the_administrator_is_used(): void {
        $course = $this->create_enabled_course();
        set_config('avatar', '07', 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString('avatar_profesor_07.png', $this->footer_output());
    }

    /**
     * MDL-INT-028: a gallery avatar whose file is missing falls back to the first one.
     */
    public function test_a_missing_gallery_avatar_falls_back_to_the_first_one(): void {
        $course = $this->create_enabled_course();
        set_config('avatar', '99', 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $output = $this->footer_output();
        $this->assertStringContainsString('avatar_profesor_01.png', $output);
        $this->assertStringNotContainsString('avatar_profesor_99.png', $output);
    }

    /**
     * MDL-INT-028: a custom image takes precedence over the gallery avatar.
     */
    public function test_a_custom_image_takes_precedence_over_the_gallery_avatar(): void {
        $course = $this->create_enabled_course();
        set_config('avatar', '07', 'local_dttutor');
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'local_dttutor',
            'filearea' => 'customavatar',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'tutor.png',
        ], 'not-a-real-png');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $output = $this->footer_output();
        $this->assertStringContainsString('customavatar', $output);
        $this->assertStringContainsString('tutor.png', $output);
        $this->assertStringNotContainsString('avatar_profesor_07.png', $output);
    }

    /**
     * MDL-INT-029: the placeholders of the welcome message are resolved with real data.
     */
    public function test_the_placeholders_are_resolved_with_course_and_user_data(): void {
        $course = $this->create_enabled_course();
        $this->getDataGenerator()->create_and_enrol(
            $course,
            'editingteacher',
            ['firstname' => 'Ada', 'lastname' => 'Lovelace']
        );
        $student = $this->getDataGenerator()->create_and_enrol(
            $course,
            'student',
            ['firstname' => 'Grace', 'lastname' => 'Hopper']
        );
        set_config('welcomemessage', 'Hi {firstname}, I am {teachername} from {coursename}. You are {username}.', 'local_dttutor');
        set_config('tutorname', 'Assistant of {coursename}', 'local_dttutor');
        $this->setUser($student);
        $this->set_course_page($course);

        $output = $this->footer_output();
        $this->assertStringContainsString('Hi Grace, I am Ada Lovelace from ' . $course->fullname, $output);
        $this->assertStringContainsString('You are Grace Hopper', $output);
        $this->assertStringContainsString('Assistant of ' . $course->fullname, $output);
    }

    /**
     * MDL-INT-029: the teacher placeholder takes the first teacher by surname.
     */
    public function test_the_teacher_placeholder_takes_the_first_teacher_by_surname(): void {
        $course = $this->create_enabled_course();
        $this->getDataGenerator()->create_and_enrol(
            $course,
            'editingteacher',
            ['firstname' => 'Zoe', 'lastname' => 'Bravo']
        );
        $this->getDataGenerator()->create_and_enrol(
            $course,
            'editingteacher',
            ['firstname' => 'Ann', 'lastname' => 'Alvarez']
        );
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        set_config('welcomemessage', 'Your teacher is {teachername}.', 'local_dttutor');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString('Your teacher is Ann Alvarez.', $this->footer_output());
    }

    /**
     * MDL-INT-029: a course with no teacher falls back to the default tutor name.
     */
    public function test_a_course_without_a_teacher_falls_back_to_the_default_name(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        set_config('welcomemessage', 'Your teacher is {teachername}.', 'local_dttutor');
        $this->setUser($student);
        $this->set_course_page($course);

        $expected = 'Your teacher is ' . get_string('tutorname_default', 'local_dttutor') . '.';
        $this->assertStringContainsString($expected, $this->footer_output());
    }

    /**
     * MDL-INT-030: the header labels an editing teacher as a teacher.
     */
    public function test_the_header_labels_an_editing_teacher_as_a_teacher(): void {
        $course = $this->create_enabled_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->set_course_page($course);

        $expected = '<span class="tutor-ia-role">' . get_string('teacher', 'local_dttutor') . '</span>';
        $this->assertStringContainsString($expected, $this->footer_output());
    }

    /**
     * MDL-UNIT-005: with no position saved the button sits in the bottom right corner.
     */
    public function test_with_no_position_saved_the_button_sits_in_the_default_corner(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString('right: 2rem; bottom: 6rem;', $this->footer_output());
    }

    /**
     * MDL-UNIT-005: an unreadable position falls back to the default corner instead of failing.
     */
    public function test_an_unreadable_position_falls_back_to_the_default_corner(): void {
        $course = $this->create_enabled_course();
        set_config('avatar_position_data', 'not a position', 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString('right: 2rem; bottom: 6rem;', $this->footer_output());
    }

    /**
     * MDL-UNIT-005: the bottom left preset anchors the button to the left edge.
     */
    public function test_the_bottom_left_preset_anchors_the_button_to_the_left_edge(): void {
        $course = $this->create_enabled_course();
        set_config('avatar_position_data', json_encode([
            'preset' => 'left',
            'x' => '2rem',
            'y' => '6rem',
            'drawerside' => 'left',
            'xref' => 'left',
            'yref' => 'bottom',
        ]), 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $this->assertStringContainsString('left: 2rem; bottom: 6rem;', $this->footer_output());
    }

    /**
     * MDL-UNIT-005: a custom position is anchored to the reference edges it was measured from.
     */
    public function test_a_custom_position_is_anchored_to_its_reference_edges(): void {
        $course = $this->create_enabled_course();
        set_config('avatar_position_data', json_encode([
            'preset' => 'custom',
            'x' => '-3rem',
            'y' => '-4rem',
            'drawerside' => 'right',
            'xref' => 'right',
            'yref' => 'top',
        ]), 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $output = $this->footer_output();
        $this->assertStringContainsString('right: 3rem;', $output);
        $this->assertStringContainsString('top: 4rem;', $output);
    }

    /**
     * MDL-UNIT-005: the side the panel opens from does not follow the side of the button.
     */
    public function test_the_side_the_panel_opens_from_is_independent_of_the_button(): void {
        $course = $this->create_enabled_course();
        set_config('avatar_position_data', json_encode([
            'preset' => 'left',
            'x' => '2rem',
            'y' => '6rem',
            'drawerside' => 'right',
            'xref' => 'left',
            'yref' => 'bottom',
        ]), 'local_dttutor');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $output = $this->footer_output();
        $this->assertStringContainsString('left: 2rem; bottom: 6rem;', $output);
        $this->assertStringContainsString('tutor-ia-drawer-right', $output);
    }

    /**
     * MDL-INT-030: the header labels anyone else as a student.
     */
    public function test_the_header_labels_an_enrolled_student_as_a_student(): void {
        $course = $this->create_enabled_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->set_course_page($course);

        $expected = '<span class="tutor-ia-role">' . get_string('student', 'local_dttutor') . '</span>';
        $this->assertStringContainsString($expected, $this->footer_output());
    }
}
