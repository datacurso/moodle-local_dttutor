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

use aiprovider_datacurso\httpclient\ai_services_api;
use aiprovider_datacurso\httpclient\datacurso_api_base;
use aiprovider_datacurso\local\ratelimiter;

/**
 * Adapter of the {@see ai_client} port to the aiprovider_datacurso plugin.
 *
 * This is the only production class that references the provider namespace. Construction
 * fails (an exception, or an \Error when the provider is not installed) when the provider
 * client cannot be built, exactly like instantiating the provider client directly.
 *
 * The provider stores its configuration differently depending on the Moodle release, and this
 * adapter absorbs that difference so the rest of the plugin never has to know about it:
 *  - Moodle 4.5: the license key and the rate limit live in the plugin configuration, and
 *    {@see ratelimiter} is built without arguments.
 *  - Moodle 5.0+: the AI subsystem stores provider configuration in per-instance records
 *    (the ai_providers table), so both are read from the enabled provider instance, which
 *    {@see ratelimiter} also requires as a constructor argument.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class datacurso_ai_client implements ai_client {
    /** @var string Service identifier declared to the provider rate limiter. */
    private const SERVICE_ID = 'local_dttutor';

    /** @var string Frankenstyle name of the provider this adapter talks to. */
    private const PROVIDER_NAME = 'aiprovider_datacurso';

    /** @var ai_services_api Provider HTTP client. */
    private ai_services_api $api;

    /** @var object|null The enabled provider instance on Moodle 5.0+, null on Moodle 4.5 or when there is none. */
    private ?object $instance = null;

    /** @var bool Whether the provider instance lookup has already been attempted. */
    private bool $instanceresolved = false;

    /**
     * Build the provider client eagerly so that misconfiguration surfaces at resolution time.
     *
     * @throws \moodle_exception When the provider license key is not configured.
     */
    public function __construct() {
        $this->api = new ai_services_api();
    }

    /**
     * Perform an authenticated request against the AI service.
     *
     * @param string $method HTTP method.
     * @param string $path Endpoint path relative to the base URL.
     * @param array $body Request body.
     * @return array|null Decoded JSON response.
     */
    public function request(string $method, string $path, array $body = []): ?array {
        return $this->api->request($method, $path, $body);
    }

    /**
     * Base URL configured in the provider.
     *
     * @return string
     */
    public function get_base_url(): string {
        return $this->api->get_base_url();
    }

    /**
     * License key configured in the provider.
     *
     * Reads the enabled provider instance first (Moodle 5.0+) and falls back to the plugin
     * configuration (Moodle 4.5), so the License-Key header is sent on every supported release.
     *
     * @return string
     */
    public function get_license_key(): string {
        $instance = $this->get_provider_instance();
        if ($instance !== null) {
            $config = (array)($instance->config ?? []);
            $licensekey = $config['licensekey'] ?? '';
            if (is_string($licensekey) && $licensekey !== '') {
                return $licensekey;
            }
        }

        return (string)(get_config(self::PROVIDER_NAME, 'licensekey') ?: '');
    }

    /**
     * Anonymous site identifier managed by the provider.
     *
     * @return string
     */
    public function get_site_id(): string {
        return datacurso_api_base::get_site_uuid();
    }

    /**
     * Rate limit headers configured in the provider for this plugin.
     *
     * The rate limit is an optional refinement of the request: when it cannot be resolved the
     * headers are simply omitted, never letting the chat request fail because of it.
     *
     * @return string[]
     */
    public function get_rate_limit_headers(): array {
        try {
            $ratelimiter = $this->build_rate_limiter();
            if ($ratelimiter === null) {
                return [];
            }
            return $ratelimiter->get_rate_limit_headers(self::SERVICE_ID);
        } catch (\Throwable $e) {
            debugging(
                '[local_dttutor] RATE_LIMIT_HEADERS_UNAVAILABLE ' . get_class($e),
                DEBUG_DEVELOPER
            );
            return [];
        }
    }

    /**
     * Build the provider rate limiter for whichever provider release is installed.
     *
     * The Moodle 5.0 provider requires the AI provider instance in the constructor; the
     * Moodle 4.5 one takes no arguments. The constructor is inspected instead of the Moodle
     * version so the adapter follows the provider actually installed on the site.
     *
     * @return ratelimiter|null The rate limiter, or null when the provider instance it needs is unavailable.
     */
    private function build_rate_limiter(): ?ratelimiter {
        $constructor = (new \ReflectionClass(ratelimiter::class))->getConstructor();
        $required = $constructor === null ? 0 : $constructor->getNumberOfRequiredParameters();

        if ($required === 0) {
            // Moodle 4.5 provider: the rate limit is read from the plugin configuration.
            return new ratelimiter();
        }

        $instance = $this->get_provider_instance();
        if ($instance === null) {
            return null;
        }

        return new ratelimiter($instance);
    }

    /**
     * Resolve the enabled Datacurso provider instance, if this Moodle release has provider instances.
     *
     * Moodle 5.0 moved AI provider configuration out of the plugin settings and into per-instance
     * records; Moodle 4.5 has no provider instances, so this returns null there and the callers
     * fall back to the plugin configuration. The lookup is performed at most once per adapter.
     *
     * @return object|null The enabled aiprovider_datacurso instance, or null when there is none.
     */
    private function get_provider_instance(): ?object {
        global $DB;

        if ($this->instanceresolved) {
            return $this->instance;
        }
        $this->instanceresolved = true;

        if (!class_exists('\core_ai\manager') || !method_exists('\core_ai\manager', 'get_provider_instances')) {
            // Moodle 4.5: provider configuration lives in the plugin settings.
            return null;
        }

        try {
            $manager = new \core_ai\manager($DB);
            $fallback = null;
            foreach ($manager->get_provider_instances() as $instance) {
                if ($instance->get_name() !== self::PROVIDER_NAME || empty($instance->enabled)) {
                    continue;
                }
                $config = (array)($instance->config ?? []);
                if (!empty($config['licensekey'])) {
                    $this->instance = $instance;
                    return $this->instance;
                }
                $fallback ??= $instance;
            }
            // No enabled instance carries a license key: keep the first enabled one, which is
            // still enough for the rate limit configuration.
            $this->instance = $fallback;
        } catch (\Throwable $e) {
            debugging(
                '[local_dttutor] AI_PROVIDER_INSTANCE_LOOKUP_FAILED ' . get_class($e),
                DEBUG_DEVELOPER
            );
            $this->instance = null;
        }

        return $this->instance;
    }
}
