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
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class datacurso_ai_client implements ai_client {
    /** @var string Service identifier declared to the provider rate limiter. */
    private const SERVICE_ID = 'local_dttutor';

    /** @var ai_services_api Provider HTTP client. */
    private ai_services_api $api;

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
     * @return string
     */
    public function get_license_key(): string {
        return (string)(get_config('aiprovider_datacurso', 'licensekey') ?: '');
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
     * @return string[]
     */
    public function get_rate_limit_headers(): array {
        return (new ratelimiter())->get_rate_limit_headers(self::SERVICE_ID);
    }
}
