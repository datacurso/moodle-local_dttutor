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

/**
 * Test double for the AI service client port.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\fixtures;

use local_dttutor\httpclient\ai_client;

/**
 * Records every request and hands out queued responses instead of touching the network.
 *
 * Inject it through the tutoria_api constructor or bind it for the whole plugin with
 * \core\di::set(ai_client::class, $fake); client_factory then hands it out everywhere.
 * It implements the port directly, so the AI provider plugin need not be installed.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fake_ai_client implements ai_client {
    /** @var array<int, array|\Throwable> Responses (or exceptions to throw) handed out one per request. */
    public array $responses = [];

    /** @var array<int, array{method: string, path: string, body: array}> Requests received, in order. */
    public array $calls = [];

    /** @var string License key handed out by get_license_key(). */
    private string $licensekey = 'fake-license';

    /** @var string[] Headers handed out by get_rate_limit_headers(). */
    private array $ratelimitheaders = [];

    /**
     * Bind a client that cannot be resolved: every resolution of the port throws.
     *
     * Mirrors the production failure when the provider is unconfigured or not installed, without
     * depending on the provider itself. The exception class is deliberately not part of the
     * contract callers should assert on.
     */
    public static function bind_unavailable(): void {
        \core\di::set(ai_client::class, static function (): ai_client {
            throw new \RuntimeException('AI client unavailable');
        });
    }

    /**
     * Queue a response or an exception for the next request.
     *
     * @param array|\Throwable $response Decoded response body, or an exception to throw.
     */
    public function enqueue(array|\Throwable $response): void {
        $this->responses[] = $response;
    }

    /**
     * Record the request and return the next queued response.
     *
     * @param string $method HTTP method.
     * @param string $path Relative endpoint.
     * @param array $body Request body.
     * @return array|null
     * @throws \Throwable When the queued response is an exception.
     */
    public function request(string $method, string $path, array $body = []): ?array {
        $this->calls[] = ['method' => $method, 'path' => $path, 'body' => $body];
        $response = array_shift($this->responses) ?? [];
        if ($response instanceof \Throwable) {
            throw $response;
        }
        return $response;
    }

    /**
     * Fixed base URL; never contacted.
     *
     * @return string
     */
    public function get_base_url(): string {
        return 'https://fake.invalid';
    }

    /**
     * Override the license key (an empty string mimics an unconfigured site).
     *
     * @param string $licensekey
     */
    public function set_license_key(string $licensekey): void {
        $this->licensekey = $licensekey;
    }

    /**
     * Override the rate limit headers the double forwards.
     *
     * @param string[] $headers "Name: value" lines.
     */
    public function set_rate_limit_headers(array $headers): void {
        $this->ratelimitheaders = $headers;
    }

    /**
     * License key; 'fake-license' unless overridden.
     *
     * @return string
     */
    public function get_license_key(): string {
        return $this->licensekey;
    }

    /**
     * Fixed site identifier.
     *
     * @return string
     */
    public function get_site_id(): string {
        return 'fake-site';
    }

    /**
     * Rate limit headers; none unless overridden.
     *
     * @return string[]
     */
    public function get_rate_limit_headers(): array {
        return $this->ratelimitheaders;
    }

    /**
     * Paths of the requests received so far, as "METHOD /path".
     *
     * @return string[]
     */
    public function get_call_signatures(): array {
        return array_map(static fn(array $call): string => $call['method'] . ' ' . $call['path'], $this->calls);
    }
}
