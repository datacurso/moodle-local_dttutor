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
 * The provider stores its configuration differently depending on the Moodle release; where it
 * lives is resolved by {@see provider_config}, and this adapter only builds {@see ratelimiter}
 * the way the installed provider expects it: without arguments on Moodle 4.5, with the enabled
 * provider instance on Moodle 5.0+.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class datacurso_ai_client implements ai_client {
    /** @var string Service identifier declared to the provider rate limiter. */
    private const SERVICE_ID = 'local_dttutor';

    /** @var ai_services_api Provider HTTP client. */
    private ai_services_api $api;

    /** @var \core_ai\provider|null The enabled provider instance on Moodle 5.0+, null on Moodle 4.5 or when there is none. */
    private ?\core_ai\provider $instance = null;

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
     * Read from the enabled provider instance on Moodle 5.0+ and from the plugin configuration on
     * Moodle 4.5, so the License-Key header is sent on every supported release.
     *
     * @return string
     */
    public function get_license_key(): string {
        return provider_config::get_license_key($this->get_provider_instance());
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
     * Resolve the enabled provider instance, at most once per adapter.
     *
     * @return \core_ai\provider|null The instance, or null on Moodle 4.5 or when none is enabled.
     */
    private function get_provider_instance(): ?\core_ai\provider {
        if (!$this->instanceresolved) {
            $this->instance = provider_config::get_instance();
            $this->instanceresolved = true;
        }

        return $this->instance;
    }
}
