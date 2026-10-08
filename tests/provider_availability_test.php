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

use local_dttutor\external\get_chat_history;
use local_dttutor\fixtures\fake_ai_client;
use local_dttutor\httpclient\ai_client;
use local_dttutor\httpclient\client_factory;
use local_dttutor\httpclient\provider_config;
use local_dttutor\proxy\request_guard;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/fake_ai_client.php');

/**
 * How the tutor reacts to the state of the AI provider it depends on.
 *
 * See MDL-INT-038 and MDL-INT-039 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\request_guard::assert_provider_enabled
 * @covers     \local_dttutor\httpclient\client_factory::is_provider_enabled
 * @covers     \local_dttutor\httpclient\datacurso_ai_client
 */
final class provider_availability_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * A course with the tutor on everywhere and one enrolled student logged in.
     *
     * @return array{0: \stdClass, 1: \stdClass} The course and the student.
     */
    private function course_with_the_tutor_on(): array {
        $this->setAdminUser();
        set_config('enabled', 1, 'local_dttutor');
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider();
        $course = $this->getDataGenerator()->create_course();
        course_config::update((int)$course->id, ['indexing_enabled' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        return [$course, $student];
    }

    /**
     * Switch the AI provider off, as an administrator does in the AI administration of Moodle.
     */
    private function disable_the_provider(): void {
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->disable_ai_provider();
    }

    /**
     * A chat request for a course.
     *
     * @param int $courseid
     * @return array
     */
    private function chat_request(int $courseid): array {
        return [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'context' => ['course_id' => $courseid],
        ];
    }

    /**
     * MDL-INT-038: with the provider enabled the tutor works, which is the reference for the rest.
     */
    public function test_a_query_is_authorised_while_the_provider_is_enabled(): void {
        [$course] = $this->course_with_the_tutor_on();

        $this->assertTrue(client_factory::is_provider_enabled());
        $this->assertNotEmpty(request_guard::authorize($this->chat_request((int)$course->id)));
    }

    /**
     * MDL-INT-038: the tutor stops accepting queries once the administrator disables the provider.
     */
    public function test_a_query_is_refused_when_the_provider_is_disabled(): void {
        [$course] = $this->course_with_the_tutor_on();
        $this->disable_the_provider();

        $this->assertFalse(client_factory::is_provider_enabled());
        $this->expectException(\moodle_exception::class);
        request_guard::authorize($this->chat_request((int)$course->id));
    }

    /**
     * MDL-INT-038: the refusal tells the user that the service is not available.
     */
    public function test_the_refusal_explains_that_the_service_is_not_available(): void {
        [$course] = $this->course_with_the_tutor_on();
        $this->disable_the_provider();

        try {
            request_guard::authorize($this->chat_request((int)$course->id));
            $this->fail('The provider is disabled, so the request had to be refused.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(get_string('error_provider_disabled', 'local_dttutor'), $e->getMessage());
        }
    }

    /**
     * MDL-INT-038: nothing travels to the AI service while the provider is disabled.
     */
    public function test_nothing_is_sent_to_the_service_while_the_provider_is_disabled(): void {
        [$course, $student] = $this->course_with_the_tutor_on();
        session_store::upsert((int)$student->id, (int)$course->id, null, 'remote-1');
        $fake = new fake_ai_client();
        \core\di::set(ai_client::class, $fake);
        $this->disable_the_provider();

        try {
            get_chat_history::execute((int)$course->id);
            $this->fail('The provider is disabled, so the history had to be refused.');
        } catch (\moodle_exception $e) {
            $this->assertEquals(get_string('error_provider_disabled', 'local_dttutor'), $e->getMessage());
        }

        $this->assertSame([], $fake->calls, 'No request may reach the AI service with the provider disabled.');
    }

    /**
     * MDL-INT-038: switching the provider back on restores the tutor with no further intervention.
     */
    public function test_switching_the_provider_back_on_restores_the_tutor(): void {
        [$course] = $this->course_with_the_tutor_on();
        $this->disable_the_provider();
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider();

        $this->assertNotEmpty(request_guard::authorize($this->chat_request((int)$course->id)));
    }

    /**
     * MDL-INT-039: the region of the licence is resolved once and kept.
     */
    public function test_the_licence_region_is_resolved_once_and_kept(): void {
        if (!class_exists(\aiprovider_datacurso\local\license_region::class) || provider_config::has_instances()) {
            $this->markTestSkipped('Needs the Moodle 4.5 provider that keeps the region of the licence (1.5.2+).');
        }
        $this->setAdminUser();
        $this->getDataGenerator()->get_plugin_generator('local_dttutor')->create_ai_provider(['licensekey' => 'DC-KEPT-LICENCE']);

        // The European region of this licence is already known, and the licence store cannot be
        // reached: were the region asked for again, building the client would fail.
        $this->keep_region('1', sha1('DC-KEPT-LICENCE'));
        set_config('curlsecurityblockedhosts', 'shop.datacurso.com');

        foreach ([1, 2] as $request) {
            $client = new \local_dttutor\httpclient\datacurso_ai_client();
            $this->assertStringContainsString('eu.', $client->get_base_url(), "Request {$request}");
        }
    }

    /**
     * Keep a resolved region where the provider keeps it for the current user.
     *
     * That is the site configuration without tenancy, and the tenant of the user on Workplace.
     *
     * @param string $region '1' for the European deployment.
     * @param string $fingerprint Fingerprint of the licence the region belongs to.
     */
    private function keep_region(string $region, string $fingerprint): void {
        $values = [
            \aiprovider_datacurso\local\license_region::REGION => $region,
            \aiprovider_datacurso\local\license_region::FINGERPRINT => $fingerprint,
            \aiprovider_datacurso\local\license_region::CHECKED => (string)time(),
        ];
        foreach ($values as $name => $value) {
            if (provider_config::has_tenants()) {
                \aiprovider_datacurso\local\tenant_config::set(
                    provider_config::COMPONENT,
                    provider_config::get_tenant_id(),
                    $name,
                    $value
                );
            } else {
                set_config($name, $value, provider_config::COMPONENT);
            }
        }
    }
}
