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
 * ws_search tool — searches Moodle web service functions by keyword.
 *
 * Uses the ws_indexer (semantic search over WS function definitions)
 * to find relevant functions matching the user's intent.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\agent\tool;

defined('MOODLE_INTERNAL') || die();

use local_dttutor\schema\ws_indexer;

/**
 * Searches Moodle WS functions by intent/keywords.
 */
class ws_search_tool implements agent_tool {

    public function get_name(): string {
        return 'ws_search';
    }

    public function get_definition(): array {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'ws_search',
                'description' => 'Search Moodle web service functions by intent or keywords. Returns function name, component, description, params, returns.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Intent or keywords in ENGLISH (e.g. "create user", "enrol student", "get course grades").',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum results (default: 20, max: 30).',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    public function execute(\stdClass $params): string {
        $query = trim($params->query ?? '');
        if (empty($query)) {
            return json_encode(['error' => 'Search query required.']);
        }

        $start = microtime(true);
        $limit = isset($params->limit) ? min((int) $params->limit, 30) : 20;
        if ($limit < 1) {
            $limit = 20;
        }

        try {
            $entries = ws_indexer::search($query, [], $limit);

            if (empty($entries)) {
                return json_encode([
                    'success' => true,
                    'count' => 0,
                    'row_count' => 0,
                    'functions' => [],
                    'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                    'message' => "No web services found matching: {$query}",
                ]);
            }

            $functions = [];
            foreach ($entries as $entry) {
                $functions[] = [
                    'name' => $entry['name'],
                    'component' => $entry['component'] ?? '',
                    'description' => $entry['description'] ?? '',
                    'params' => $entry['params'] ?? '(none)',
                    'returns' => $entry['returns'] ?? 'void',
                ];
            }

            return json_encode([
                'success' => true,
                'count' => count($functions),
                'row_count' => count($functions),
                'functions' => $functions,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return json_encode([
                'error' => 'ws_search failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);
        }
    }
}
