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
 * Tool executor — receives AI tool_calls, dispatches to the right handler,
 * and returns results.
 *
 * Simplified registry for dttutor — only 3 base tools:
 *   - call_webservice
 *   - ws_search
 *   - ws_describe
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\agent;

/**
 * Static facade for tool dispatch.
 */
class tool_executor {
    /** @var tool\agent_tool[] Cached tool instances. */
    private static array $instances = [];

    /**
     * Build all tool definitions for the gateway payload.
     *
     * @param  int $userid User ID for WS permission checks.
     * @return array Array of tool definitions compatible with OpenAI's "tools" field.
     */
    public static function get_definitions(int $userid = 0): array {
        $tools = self::get_tools($userid);
        $defs = [];
        foreach ($tools as $tool) {
            $defs[] = $tool->get_definition();
        }
        return $defs;
    }

    /**
     * Execute a single tool call.
     *
     * @param  string $name      Tool name (e.g. "call_webservice").
     * @param  string $arguments JSON-encoded arguments from the AI.
     * @return string            Result to return as a "tool" role message.
     */
    public static function execute(string $name, string $arguments): string {
        $tools = self::get_tools();
        $tool = $tools[$name] ?? null;

        if (!$tool) {
            return json_encode(['error' => "Unknown tool: {$name}"]);
        }

        $decoded = json_decode($arguments);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(['error' => 'Invalid arguments JSON: ' . json_last_error_msg()]);
        }

        return $tool->execute($decoded);
    }

    /**
     * Get all registered tool instances, keyed by name.
     *
     * @param  int $userid
     * @return tool\agent_tool[]
     */
    private static function get_tools(int $userid = 0): array {
        if (empty(self::$instances) || $userid > 0) {
            $wstool = new tool\ws_tool($userid);
            self::$instances = [
                'call_webservice' => $wstool,
                'ws_search' => new tool\ws_search_tool(),
                'ws_describe' => new tool\ws_describe_tool(),
            ];
        }
        return self::$instances;
    }
}
