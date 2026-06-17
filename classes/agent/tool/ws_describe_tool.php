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
 * ws_describe tool — gets full documentation for a specific Moodle WS function.
 *
 * Uses ws_indexer::describe() to return the complete parameter
 * and return structure for a known web service function. The AI should
 * call this AFTER ws_search and BEFORE call_webservice to verify required
 * parameters and return types.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\agent\tool;

use local_dttutor\schema\ws_indexer;

/**
 * Describes a single Moodle WS function with full parameter/return info.
 */
class ws_describe_tool implements agent_tool {
    /**
     * Get the unique tool name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'ws_describe';
    }

    /**
     * Get the OpenAI-compatible tool definition.
     *
     * @return array
     */
    public function get_definition(): array {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'ws_describe',
                'description' => 'Get full documentation for a specific Moodle web service function: '
                    . 'description, parameters with types, and return structure.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'wsname' => [
                            'type' => 'string',
                            'description' => 'Exact web service function name '
                                . '(e.g. core_user_create_users, core_course_get_courses).',
                        ],
                    ],
                    'required' => ['wsname'],
                ],
            ],
        ];
    }

    /**
     * Execute the tool: describe a single web service function.
     *
     * @param  \stdClass $params Decoded arguments (expects wsname).
     * @return string            JSON-encoded function documentation or error.
     */
    public function execute(\stdClass $params): string {
        $wsname = trim($params->wsname ?? '');
        if (empty($wsname)) {
            return json_encode(['error' => 'Web service name required.']);
        }

        $start = microtime(true);

        try {
            $info = ws_indexer::describe($wsname);

            if (isset($info['error'])) {
                return json_encode([
                    'error' => "Web service not found: {$wsname}",
                    'detail' => $info['error'],
                    'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                ]);
            }

            return json_encode([
                'success' => true,
                'function' => $info,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return json_encode([
                'error' => 'ws_describe failed: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);
        }
    }
}
