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

namespace local_dttutor\httpclient;

/**
 * Tests for reading the configuration of the AI provider on every supported Moodle release.
 *
 * Each release keeps it somewhere else, so the tests of the release that is not running are skipped.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\httpclient\provider_config
 * @covers     \local_dttutor\httpclient\datacurso_ai_client
 */
final class provider_config_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Generator of the plugin, which configures the provider the way the running release keeps it.
     *
     * @return \local_dttutor_generator
     */
    private function generator(): \local_dttutor_generator {
        return $this->getDataGenerator()->get_plugin_generator('local_dttutor');
    }

    /**
     * Skip the test unless the running release configures providers through instances.
     */
    private function require_instances(): void {
        if (!provider_config::has_instances()) {
            $this->markTestSkipped('This Moodle release configures AI providers as plugins.');
        }
    }

    /**
     * Skip the test unless the running release configures providers as plugins.
     */
    private function require_plugin_configuration(): void {
        if (provider_config::has_instances()) {
            $this->markTestSkipped('This Moodle release configures AI providers through instances.');
        }
    }

    /**
     * Skip the test unless the provider itself is installed, which not every test site has.
     */
    private function require_installed_provider(): void {
        if (!class_exists(provider_config::PROVIDER_CLASS)) {
            $this->markTestSkipped('The aiprovider_datacurso plugin is not installed on this site.');
        }
    }

    /**
     * A site where nobody configured the provider has it switched off.
     */
    public function test_the_provider_is_off_until_it_is_configured(): void {
        $this->assertFalse(provider_config::is_enabled());
        $this->assertSame('', provider_config::get_license_key());
    }

    /**
     * Configured and switched off again, the provider is off on every release.
     */
    public function test_the_provider_follows_the_administrator_switching_it_off(): void {
        $this->generator()->create_ai_provider();
        $this->assertTrue(provider_config::is_enabled());

        $this->generator()->disable_ai_provider();

        $this->assertFalse(provider_config::is_enabled());
    }

    /**
     * Moodle 4.5: the provider is on when its plugin is on, and the key is a setting of the plugin.
     */
    public function test_moodle_45_reads_the_plugin_configuration(): void {
        $this->require_plugin_configuration();

        $this->generator()->create_ai_provider(['licensekey' => 'plugin-key']);

        $this->assertTrue(provider_config::is_enabled());
        $this->assertSame('plugin-key', provider_config::get_license_key());
        $this->assertNull(provider_config::get_instance());
    }

    /**
     * Moodle 5.0+: an enabled instance switches the provider on and carries the key.
     */
    public function test_moodle_50_reads_the_enabled_instance(): void {
        $this->require_instances();
        $this->require_installed_provider();

        $this->generator()->create_ai_provider(['licensekey' => 'instance-key']);

        $this->assertTrue(provider_config::is_enabled());
        $this->assertSame('instance-key', provider_config::get_license_key());
        $this->assertSame(provider_config::PROVIDER_CLASS, get_class(provider_config::get_instance()));
    }

    /**
     * Moodle 5.0+: a disabled instance does not count, whatever the plugin settings say.
     */
    public function test_moodle_50_ignores_disabled_instances_and_leftover_settings(): void {
        $this->require_instances();

        $this->generator()->create_ai_provider(['enabled' => 0, 'licensekey' => 'instance-key']);
        set_config('enabled', 1, provider_config::COMPONENT);
        set_config('licensekey', 'leftover-key', provider_config::COMPONENT);

        $this->assertFalse(provider_config::is_enabled());
        $this->assertNull(provider_config::get_instance());
        $this->assertSame('', provider_config::get_license_key());
    }

    /**
     * Moodle 5.0+: an enabled instance without a key is still the decision of the administrator.
     */
    public function test_moodle_50_counts_an_enabled_instance_without_a_key(): void {
        $this->require_instances();
        $this->require_installed_provider();

        $this->generator()->create_ai_provider(['licensekey' => '']);

        $this->assertTrue(provider_config::is_enabled());
        $this->assertNotNull(provider_config::get_instance());
        $this->assertSame('', provider_config::get_license_key());
    }

    /**
     * Moodle 5.0+: with several enabled instances, the one with a key is the one requests go through.
     */
    public function test_moodle_50_prefers_the_instance_that_holds_a_key(): void {
        $this->require_instances();
        $this->require_installed_provider();

        $this->generator()->create_ai_provider(['licensekey' => '']);
        $this->generator()->create_ai_provider(['licensekey' => 'second-key']);

        $this->assertSame('second-key', provider_config::get_license_key());
    }

    /**
     * The adapter sends the key and builds the rate limiter the way the installed provider expects.
     */
    public function test_the_adapter_reads_the_key_and_the_rate_limit_on_this_release(): void {
        $this->require_installed_provider();
        $this->generator()->create_ai_provider(['licensekey' => 'adapter-key']);

        $client = new datacurso_ai_client();

        $this->assertSame('adapter-key', $client->get_license_key());
        $this->assertIsArray($client->get_rate_limit_headers());
        $this->assertDebuggingNotCalled();
    }
}
