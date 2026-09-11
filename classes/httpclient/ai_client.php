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
 * Port to the AI service HTTP layer.
 *
 * Everything the plugin needs from the AI provider is expressed here, so that the rest of the
 * code never depends on a concrete provider class. Production binds {@see datacurso_ai_client};
 * tests bind a fake through \core\di::set(ai_client::class, ...).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface ai_client {
    /**
     * Perform an authenticated request against the AI service.
     *
     * @param string $method HTTP method (GET, POST, DELETE...).
     * @param string $path Endpoint path relative to the base URL.
     * @param array $body Request body, sent as JSON when not empty.
     * @return array|null Decoded JSON response, or null when the service returned no body.
     * @throws \moodle_exception When the request fails.
     */
    public function request(string $method, string $path, array $body = []): ?array;

    /**
     * Base URL of the AI service, without trailing slash guarantees.
     *
     * @return string
     */
    public function get_base_url(): string;

    /**
     * License key that authenticates this site against the AI service.
     *
     * @return string Empty string when no key is configured.
     */
    public function get_license_key(): string;

    /**
     * Anonymous identifier of this site as known by the AI service.
     *
     * @return string
     */
    public function get_site_id(): string;

    /**
     * HTTP headers ("Name: value") that forward the rate limit configured for this plugin.
     *
     * The list is empty when no rate limit is configured for the plugin.
     *
     * @return string[]
     */
    public function get_rate_limit_headers(): array;
}
