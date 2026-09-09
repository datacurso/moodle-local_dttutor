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
 * Test double for the Datacurso AI services HTTP client.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\fixtures;

use aiprovider_datacurso\httpclient\ai_services_api;

/**
 * Records every request and hands out queued responses instead of touching the network.
 *
 * Inject it through the tutoria_api constructor and, for code that resolves the client
 * from the DI container, register the wrapper with \core\di::set(tutoria_api::class, ...).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_ai_services_api extends ai_services_api {
    /** @var array<int, array|\Throwable> Responses (or exceptions to throw) handed out one per request. */
    public array $responses = [];

    /** @var array<int, array{method: string, path: string, body: array}> Requests received, in order. */
    public array $calls = [];

    /**
     * Build the double without a license key, region lookup or network access.
     */
    public function __construct() {
        // Deliberately does not call the parent constructor: it queries the shop for the region.
        $this->baseurl = 'https://ai.example.test';
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
     * Paths of the requests received so far, as "METHOD /path".
     *
     * @return string[]
     */
    public function get_call_signatures(): array {
        return array_map(static fn(array $call): string => $call['method'] . ' ' . $call['path'], $this->calls);
    }
}
