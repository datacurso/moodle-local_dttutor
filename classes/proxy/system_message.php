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
 * Builds a system prompt based on user role, course context, pre-loaded course
 * knowledge and institutional custom prompts.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\proxy;

/**
 * Builds the system message for the AI model.
 */
class system_message {
    /**
     * Build the system message.
     *
     * @param  string $role      The user's resolved role.
     * @param  array  $context   The current page/activity context.
     * @param  string $preloaded Pre-fetched course knowledge to inject (may be empty).
     * @return array             ['role' => 'system', 'content' => '...']
     */
    public static function build(string $role, array $context = [], string $preloaded = ''): array {
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
        $content .= "Goal: help the student with their course-related questions using ONLY the COURSE KNOWLEDGE "
            . "block below.\n\n";

        $content .= "WORKFLOW:\n";
        $content .= "1. Understand the student's question.\n";
        $content .= "2. Look for the answer in the COURSE KNOWLEDGE block (course info, activities, dates, "
            . "max grades, the student's own grades when provided).\n";
        $content .= "3. Answer with the real data you found, citing the activity it comes from.\n";
        $content .= "4. If the information is not available to you, say so clearly and suggest where the "
            . "student can find it in the course or whom to ask (their teacher).\n\n";

        $content .= "RULES:\n";
        $content .= "- Never invent data. The COURSE KNOWLEDGE block is your only source of truth about "
            . "this course.\n";
        $content .= "- You cannot access Moodle beyond what is provided here: do not claim to have looked "
            . "anything up, and do not pretend to perform actions in the platform.\n";
        $content .= "- When something is not available, say \"That information is not available to me\" "
            . "(in the student's language) instead of guessing.\n";
        $content .= "- Only discuss this course. Do not reveal information about other users.\n";
        $content .= "- Be concise and use the student's language.\n";
        $content .= "- Respect permissions and privacy.\n\n";

        // Language rule.
        $content .= "⚠️ LANGUAGE RULE — ABSOLUTE PRIORITY: ";
        $content .= "Always detect the language of the student's latest message and reply ";
        $content .= "in EXACTLY THAT LANGUAGE, without exception. ";
        $content .= "If the student switches language mid-conversation, switch immediately too.\n\n";

        $content .= "CONVERSATION CONTINUITY — CRITICAL:\n";
        $content .= "1. When the student replies with a short confirmation word (\"sí\", \"yes\", \"ok\", "
            . "\"dale\", \"adelante\", \"claro\", \"por favor\"), it is ALWAYS an answer to the question you "
            . "asked in your previous message. DO NOT start a new analysis — continue what you were offering.\n";
        $content .= "2. Before responding, read the LAST exchange (your previous response + the student's "
            . "new message) as a single unit. If your last message offered something and the student "
            . "agreed, EXECUTE it now.\n";
        $content .= "3. If the student says \"no\" or declines, drop the pending task and ask what else they need.\n";
        $content .= "4. If you need more information to complete a pending task, ask a specific follow-up "
            . "question. Do not change the subject.\n\n";

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

        // Inject the pre-loaded course knowledge (already filtered to what this user may see).
        if ($preloaded !== '') {
            $content .= "\n" . $preloaded;
        }

        // Add the institutional (site-level) custom prompt if configured; it is the only custom prompt.
        $customprompt = get_config('local_dttutor', 'custom_prompt');
        if (!empty($customprompt)) {
            $content .= "\n---\nInstitutional custom instructions:\n{$customprompt}\n---\n";
        }

        return ['role' => 'system', 'content' => $content];
    }
}
