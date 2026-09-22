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

use core\plugininfo\aiprovider;
use local_dttutor\proxy\request_guard;

/**
 * How the tutor reacts to the state of the AI provider it depends on.
 *
 * See MDL-INT-038 ([Pendiente:fail]) and MDL-INT-039 ([Pendiente:skip]) of
 * cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\request_guard
 */
final class provider_availability_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * MDL-INT-038: the tutor stops accepting queries once the administrator disables the AI provider.
     *
     * [Pendiente:fail] Today the tutor keeps answering as long as a licence key exists, because it
     * never checks whether the provider is enabled, so course and user data keep travelling to an
     * external service the administrator switched off.
     */
    public function test_a_query_is_refused_when_the_ai_provider_is_disabled(): void {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $input = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'context' => ['course_id' => (int)$course->id],
        ];

        // With the provider enabled the very same query is authorised, so a refusal below can only
        // come from the provider being switched off.
        aiprovider::enable_plugin('datacurso', 1);
        $this->assertNotEmpty(request_guard::authorize($input));

        aiprovider::enable_plugin('datacurso', 0);

        $this->expectException(\moodle_exception::class);
        request_guard::authorize($input);
    }

    /**
     * MDL-INT-039: the region of the licence is resolved once and kept.
     */
    public function test_the_licence_region_is_resolved_once_and_kept(): void {
        $this->markTestSkipped(
            '[Pendiente:skip] The licence region is still looked up against the licence store on every '
            . 'call, which delays each message and leaves the chat unusable when that store is down.'
        );
    }
}
