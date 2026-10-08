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
 * Where the configuration of the AI provider lives, on every supported Moodle release.
 *
 * The AI subsystem keeps it in two different places, and this is the only class that knows:
 *  - Moodle 4.5: the provider is switched on and off as a plugin, and its licence key is a
 *    setting of the plugin.
 *  - Moodle 5.0+: the provider is configured through instances (the ai_providers table), each
 *    one switched on and off on its own and carrying its own licence key.
 *  - Moodle Workplace 4.5: each tenant can hold its own licence key in the tenant configuration
 *    of the provider, falling back to the one of the site.
 *
 * The release is recognised by the API the AI subsystem offers, never by the version number, so
 * the plugin follows whatever is actually installed on the site.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_config {
    /** @var string Frankenstyle name of the provider the tutor depends on. */
    public const COMPONENT = 'aiprovider_datacurso';

    /** @var string Class the AI subsystem stores for the instances of that provider. */
    public const PROVIDER_CLASS = self::COMPONENT . '\\provider';

    /**
     * Whether the AI subsystem configures providers through instances (Moodle 5.0+).
     *
     * @return bool
     */
    public static function has_instances(): bool {
        return class_exists(\core_ai\manager::class)
            && method_exists(\core_ai\manager::class, 'get_provider_instances');
    }

    /**
     * Whether the administrator keeps the provider enabled in the AI administration of Moodle.
     *
     * On Moodle 5.0+ that means at least one enabled instance. Whether it holds a licence key is
     * a different question: a provider switched on without one is still the decision of the
     * administrator, and the failure it causes reaches the chat as a failure of the service.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        global $DB;

        if (!self::has_instances()) {
            return \core\plugininfo\aiprovider::is_plugin_enabled(client_factory::PROVIDER);
        }

        $manager = new \core_ai\manager($DB);
        return $manager->get_provider_records(['provider' => self::PROVIDER_CLASS, 'enabled' => 1]) !== [];
    }

    /**
     * The enabled instance of the provider the requests go through, on Moodle 5.0+.
     *
     * An instance with a licence key is preferred, which is the one the HTTP client of the
     * provider authenticates with; failing that, the first enabled one, which still carries the
     * rate limit configuration.
     *
     * @return \core_ai\provider|null The instance, or null on Moodle 4.5 or when none is enabled.
     */
    public static function get_instance(): ?\core_ai\provider {
        global $DB;

        if (!self::has_instances()) {
            return null;
        }

        try {
            $manager = new \core_ai\manager($DB);
            $instances = $manager->get_provider_instances(['provider' => self::PROVIDER_CLASS, 'enabled' => 1]);
        } catch (\Throwable $e) {
            debugging('[local_dttutor] AI_PROVIDER_INSTANCE_LOOKUP_FAILED ' . get_class($e), DEBUG_DEVELOPER);
            return null;
        }

        foreach ($instances as $instance) {
            if (self::read_license_key($instance) !== '') {
                return $instance;
            }
        }

        return reset($instances) ?: null;
    }

    /**
     * Whether the site provides tenancy and the provider keeps its configuration per tenant.
     *
     * True on Moodle Workplace with a provider release that stores the configuration per tenant.
     *
     * @return bool
     */
    public static function has_tenants(): bool {
        return class_exists(\aiprovider_datacurso\local\tenant_resolver::class)
            && class_exists(\aiprovider_datacurso\local\tenant_config::class)
            && \aiprovider_datacurso\local\tenant_resolver::is_tenancy_available();
    }

    /**
     * Tenant a user belongs to, as the provider resolves it.
     *
     * @param int|null $userid User to resolve; defaults to the current user.
     * @return int The tenant, or 0 on a site without tenancy.
     */
    public static function get_tenant_id(?int $userid = null): int {
        if (!class_exists(\aiprovider_datacurso\local\tenant_resolver::class)) {
            return 0;
        }

        return \aiprovider_datacurso\local\tenant_resolver::get_tenant_id($userid);
    }

    /**
     * Licence key the requests to the AI service are authenticated with.
     *
     * On Workplace it is the licence of the tenant of the user, or the one of the site when the
     * tenant has none, which is the licence the provider resolves the region with.
     *
     * @param \core_ai\provider|null $instance Instance already resolved, to spare a second lookup.
     * @param int|null $userid User the request is made for; defaults to the current user.
     * @return string The key, or an empty string when none is configured.
     */
    public static function get_license_key(?\core_ai\provider $instance = null, ?int $userid = null): string {
        if (!self::has_instances()) {
            if (self::has_tenants()) {
                $licensekey = \aiprovider_datacurso\local\tenant_config::get(
                    self::COMPONENT,
                    self::get_tenant_id($userid),
                    'licensekey',
                    ''
                );
                return trim((string)$licensekey);
            }
            return (string)(get_config(self::COMPONENT, 'licensekey') ?: '');
        }

        $instance ??= self::get_instance();
        return $instance === null ? '' : self::read_license_key($instance);
    }

    /**
     * Licence key held in the configuration of an instance.
     *
     * @param \core_ai\provider $instance
     * @return string
     */
    private static function read_license_key(\core_ai\provider $instance): string {
        $licensekey = ((array)($instance->config ?? []))['licensekey'] ?? '';
        return is_string($licensekey) ? $licensekey : '';
    }
}
