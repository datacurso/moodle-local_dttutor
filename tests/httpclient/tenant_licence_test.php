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

use aiprovider_datacurso\local\license_region;
use aiprovider_datacurso\local\tenant_config;

/**
 * The tutor authenticates with the licence of the tenant of the user on Workplace.
 *
 * Only Workplace has tenants, so every test is skipped elsewhere.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\httpclient\provider_config
 * @covers     \local_dttutor\httpclient\datacurso_ai_client
 * @covers     \local_dttutor\httpclient\client_factory
 * @covers     \local_dttutor\proxy\handler
 */
final class tenant_licence_test extends \advanced_testcase {
    /** @var \stdClass A user of a tenant with a licence of its own. */
    private \stdClass $owninglicence;

    /** @var \stdClass A user of a tenant without a licence of its own. */
    private \stdClass $sitelicence;

    /** @var int Tenant with a licence of its own. */
    private int $owningtenant;

    /** @var int Tenant without a licence of its own. */
    private int $plaintenant;

    protected function setUp(): void {
        parent::setUp();
        // Before anything is created, so a skipped test leaves nothing behind.
        if (!provider_config::has_tenants() || !class_exists(license_region::class)) {
            $this->markTestSkipped('Tenants exist only on Moodle Workplace with a tenant-aware provider (1.5.3-wp+).');
        }
        $this->resetAfterTest();

        $tenants = $this->getDataGenerator()->get_plugin_generator('tool_tenant');
        $this->owningtenant = (int)$tenants->create_tenant()->id;
        $this->plaintenant = (int)$tenants->create_tenant()->id;
        $this->owninglicence = $this->getDataGenerator()->create_user();
        $this->sitelicence = $this->getDataGenerator()->create_user();
        $tenants->allocate_user((int)$this->owninglicence->id, $this->owningtenant);
        $tenants->allocate_user((int)$this->sitelicence->id, $this->plaintenant);

        set_config('licensekey', 'DC-SITE-LICENCE', provider_config::COMPONENT);
        tenant_config::set(provider_config::COMPONENT, $this->owningtenant, 'licensekey', 'DC-TENANT-LICENCE');

        // The region of both licences is already known, so building a client never asks the shop.
        $this->remember_region($this->owningtenant, 'DC-TENANT-LICENCE');
        $this->remember_region($this->plaintenant, 'DC-SITE-LICENCE');
    }

    /**
     * Keep the standard region of a licence for a tenant, as the provider does once resolved.
     *
     * @param int $tenantid
     * @param string $licence
     */
    private function remember_region(int $tenantid, string $licence): void {
        tenant_config::set(provider_config::COMPONENT, $tenantid, license_region::REGION, '0');
        tenant_config::set(provider_config::COMPONENT, $tenantid, license_region::FINGERPRINT, sha1($licence));
        tenant_config::set(provider_config::COMPONENT, $tenantid, license_region::CHECKED, (string)time());
    }

    /**
     * A tenant with a licence of its own is authenticated with it; one without, with the site one.
     */
    public function test_the_licence_is_the_one_of_the_tenant_of_the_user(): void {
        $this->assertSame('DC-TENANT-LICENCE', provider_config::get_license_key(null, (int)$this->owninglicence->id));
        $this->assertSame('DC-SITE-LICENCE', provider_config::get_license_key(null, (int)$this->sitelicence->id));
    }

    /**
     * Without a user the licence follows the current user.
     */
    public function test_the_licence_follows_the_current_user(): void {
        $this->setUser($this->owninglicence);

        $this->assertSame('DC-TENANT-LICENCE', provider_config::get_license_key());
        $this->assertSame('DC-TENANT-LICENCE', (new datacurso_ai_client())->get_license_key());
    }

    /**
     * A client built for a user carries the licence of their tenant, whoever the current user is.
     */
    public function test_a_client_for_a_user_carries_their_licence(): void {
        $this->setAdminUser();

        $client = client_factory::for_user((int)$this->owninglicence->id);

        $this->assertSame('DC-TENANT-LICENCE', $client->get_license_key());
    }

    /**
     * The chat request names the tenant of the user, as the requests of the provider do.
     */
    public function test_the_chat_payload_carries_the_tenant(): void {
        $payload = \local_dttutor\proxy\handler::build_payload(
            'gemini-2.5-flash',
            [['role' => 'user', 'content' => 'Hi']],
            (int)$this->owninglicence->id,
            'fake-site'
        );

        $this->assertSame((string)$this->owningtenant, $payload['tenant_id']);
    }
}
