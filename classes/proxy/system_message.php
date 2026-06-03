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
 * System message builder for the AI chat proxy.
 *
 * Builds a system prompt based on user role, course context, and institutional
 * custom prompts — ported from the Python context_builder.py.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the system message for the AI model.
 */
class system_message {

    /**
     * Build the system message.
     *
     * @param  string $role    The user's resolved role.
     * @param  array  $context The current page/activity context.
     * @return array           ['role' => 'system', 'content' => '...']
     */
    public static function build(string $role, array $context = []): array {
        global $CFG;

        $sitename   = get_config('core', 'sitename') ?: 'Moodle';
        $release    = $CFG->release ?? 'unknown';
        $coursename = $context['course_name'] ?? '';
        $courseid   = $context['course_id'] ?? 0;
        $location   = $context['location'] ?? 'unknown';
        $pageurl    = $context['page_url'] ?? '';
        $pagetitle  = $context['page_title'] ?? '';

        $loclabels = [
            'course'    => get_string('ctx_loc_course', 'local_dttutor') . ($coursename ? " \"{$coursename}\"" : ''),
            'activity'  => get_string('ctx_loc_activity', 'local_dttutor'),
            'grades'    => get_string('ctx_loc_gradebook', 'local_dttutor'),
            'admin'     => get_string('ctx_loc_admin', 'local_dttutor'),
            'dashboard' => get_string('ctx_loc_dashboard', 'local_dttutor'),
            'messages'  => get_string('ctx_loc_messages', 'local_dttutor'),
            'profile'   => get_string('ctx_loc_profile', 'local_dttutor'),
            'calendar'  => get_string('ctx_loc_calendar', 'local_dttutor'),
            'files'     => get_string('ctx_loc_files', 'local_dttutor'),
        ];

        $content = "You are an AI tutor integrated into Moodle ({$sitename}).\n";
        $content .= "Role: {$role}.\n";
        $content .= "Goal: help the student with their course-related questions using available web service tools.\n\n";

        $content .= "WORKFLOW:\n";
        $content .= "1. Understand the student's question.\n";
        $content .= "2. Use ws_search to find relevant web service functions.\n";
        $content .= "3. Use ws_describe to check function parameters.\n";
        $content .= "4. Use call_webservice to execute the function.\n";
        $content .= "5. Answer the student with the real data you retrieved.\n\n";

        $content .= "DECISION RULE:\n";
        $content .= "- Step 1: ws_search(query in ENGLISH). If a relevant function exists → ws_describe → call_webservice.\n";
        $content .= "- For course/activity questions, NEVER stop after ws_search alone. You MUST execute at least one call_webservice before answering.\n";
        $content .= "- If first ws_search looks weak or ambiguous, run a second ws_search with expanded keywords (examples: gradebook, grades, gradeitems, activity, submissions, rubric).\n";
        $content .= "- Step 2: Evaluate the result. If the data is COMPLETE and answers the question → stop and respond.\n";
        $content .= "- Step 3: If the data is INSUFFICIENT → try a different WS function.\n";
        $content .= "- Do NOT search WS and try things in parallel — always sequential.\n\n";

        $content .= "RULES:\n";
        $content .= "- Never invent data. Always use call_webservice to get real data.\n";
        $content .= "- ALWAYS exclude course ID 1 (site home) and guest user from any operation.\n";
        $content .= "- If a tool fails, retry once. If it fails again, tell the student.\n";
        $content .= "- If you have enough data, stop and respond.\n";
        $content .= "- Do not say 'I cannot find information' unless you already tried at least one call_webservice and explain why it failed.\n";
        $content .= "- For assignment rubrics, call core_grading_get_definitions with areaname=submissions.\n";
        $content .= "- When the student asks about a specific activity (forum, book, assignment, etc.) but NO cmid is available in the context, FIRST call core_course_get_contents with the course ID to enumerate all course activities. Find the activity by name, then use its cmid or instance for subsequent calls. DO NOT guess instance IDs.\n";
        $content .= "- When asked 'what is this course about' or similar, call core_course_get_contents FIRST. If the course summary field is empty, the section and activity names/descriptions from get_contents reveal the course topic.\n";
        $content .= "- Prefer WS functions that accept cmid (like core_course_get_course_module) over those needing instance ID alone.\n";
        $content .= "- Be concise and use the student's language.\n";
        $content .= "- Respect permissions and privacy.\n\n";

        // Language rule (ported from Python context_builder).
        $content .= "⚠️ LANGUAGE RULE — ABSOLUTE PRIORITY: ";
        $content .= "Always detect the language of the student's latest message and reply ";
        $content .= "in EXACTLY THAT LANGUAGE, without exception. ";
        $content .= "If the student switches language mid-conversation, switch immediately too.\n\n";

        $content .= "CONVERSATION CONTINUITY — CRITICAL:\n";
        $content .= "1. When the student replies with a short confirmation word (\"sí\", \"yes\", \"ok\", \"dale\", \"adelante\", \"claro\", \"por favor\"), it is ALWAYS an answer to the question you asked in your previous message. DO NOT start a new analysis — continue what you were offering.\n";
        $content .= "2. Before responding, read the LAST exchange (your previous response + the student's new message) as a single unit. If your last message offered something and the student agreed, EXECUTE it now.\n";
        $content .= "3. If the student says \"no\" or declines, drop the pending task and ask what else they need.\n";
        $content .= "4. Never restart the ws_search → ws_describe → call_webservice workflow from scratch on a follow-up. Use the context from previous calls.\n";
        $content .= "5. If you need more information to complete a pending task, ask a specific follow-up question. Do not change the subject.\n\n";

        $content .= "Current context:\n";
        $content .= "- Moodle version: {$release}\n";
        $content .= "- Site: {$sitename} ({$CFG->wwwroot})\n";
        $content .= "- Location: {$location}\n";
        $content .= "- Page: {$pagetitle} ({$pageurl})\n";
        if (isset($loclabels[$location])) {
            $content .= "- {$loclabels[$location]}\n";
        }
        if ($courseid > 0) {
            $content .= "- Course ID: {$courseid}\n";
        }

        $cmid = $context['activity_id'] ?? 0;
        if ($cmid > 0) {
            $content .= "- Activity cmid: {$cmid}\n";
        }
        $instance = $context['activity_instance'] ?? 0;
        if ($instance > 0) {
            $content .= "- Activity instance: {$instance}\n";
        }
        if (!empty($context['activity_type'])) {
            $content .= "- Activity type: {$context['activity_type']}\n";
        }

        $content .= "- User role: {$role}\n";

        // Add institutional custom prompt if configured.
        $customprompt = get_config('local_dttutor', 'custom_prompt');
        if (!empty($customprompt)) {
            $content .= "\n---\nInstitutional custom instructions:\n{$customprompt}\n---\n";
        }

        return ['role' => 'system', 'content' => $content];
    }
}
