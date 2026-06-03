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
 * Agent tool interface — each tool implements this contract.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\agent\tool;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract for AI-callable tools.
 *
 * Each tool has a name (used by the AI in function_calling),
 * an OpenAI-compatible JSON schema definition, and an execute()
 * method that receives pre-decoded arguments and returns a string
 * result that is fed back to the AI as a "tool" role message.
 */
interface agent_tool {

    /**
     * Unique tool name used in OpenAI function_calling.
     * @return string e.g. "call_webservice"
     */
    public function get_name(): string;

    /**
     * OpenAI-compatible tool definition array.
     * @return array e.g. ['type' => 'function', 'function' => [...]]
     */
    public function get_definition(): array;

    /**
     * Execute the tool with the given arguments.
     *
     * @param  \stdClass $params Decoded arguments from the AI's tool_call.
     * @return string            Result to return to the AI (JSON or text).
     */
    public function execute(\stdClass $params): string;
}
