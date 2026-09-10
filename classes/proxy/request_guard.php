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

namespace local_dttutor\proxy;

use local_dttutor\course_config;

/**
 * Authorization guard for the chat proxy endpoint.
 *
 * Validates that the requesting user may use the AI tutor in the requested
 * course (login, enrolment, capability, plugin and course enablement), that
 * the optional course module belongs to that course and is visible to the
 * user, and sanitises the client-supplied conversation.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_guard {
    /** @var int Maximum number of conversation messages accepted from the client. */
    public const MAX_MESSAGES = 40;

    /** @var int Maximum length (in characters) of a single message. */
    public const MAX_MESSAGE_LENGTH = 8000;

    /** @var string[] Message roles the client is allowed to send. */
    private const ALLOWED_ROLES = ['user', 'assistant'];

    /** @var int Maximum length (in characters) of the client-supplied page type. */
    private const MAX_PAGETYPE_LENGTH = 100;

    /**
     * Page type prefixes mapped to the location keys the system message understands.
     *
     * Order matters: 'user-files' must win over 'user-'.
     *
     * @var string[]
     */
    private const LOCATION_PREFIXES = [
        'course-view' => 'course',
        'mod-' => 'activity',
        'grade-' => 'grades',
        'admin-' => 'admin',
        'my-' => 'dashboard',
        'message-' => 'messages',
        'user-files' => 'files',
        'files-' => 'files',
        'user-' => 'profile',
        'calendar-' => 'calendar',
    ];

    /**
     * Authorize a decoded chat proxy request for the current user.
     *
     * @param array $input The decoded JSON body ('messages' and 'context' keys).
     * @return \stdClass Object with 'course', 'context' (context_course), 'cm' (cm_info|null), 'messages'
     *                   and 'location' (the page location key derived from the client page type).
     * @throws \moodle_exception When the request must be refused.
     */
    public static function authorize(array $input): \stdClass {
        global $USER;

        $context = is_array($input['context'] ?? null) ? $input['context'] : [];
        $courseid = (int)($context['course_id'] ?? 0);
        if ($courseid <= 1) {
            throw new \moodle_exception('invalidcourseid');
        }

        $course = get_course($courseid);

        $cmid = (int)($context['activity_id'] ?? 0);
        $cmrecord = null;
        if ($cmid > 0) {
            $cmrecord = get_coursemodule_from_id('', $cmid, $course->id, false, MUST_EXIST);
        }

        // Throws instead of redirecting: this endpoint streams JSON/SSE, never HTML.
        require_login($course, false, $cmrecord, false, true);

        $coursecontext = \context_course::instance($course->id);
        require_capability('local/dttutor:use', $coursecontext);

        self::assert_course_enabled($course->id);

        $cm = null;
        if ($cmrecord !== null) {
            $cm = self::resolve_cm((int)$cmrecord->id, $course->id, (int)$USER->id);
        }

        $pagetype = is_string($context['pagetype'] ?? null) ? $context['pagetype'] : '';

        return (object)[
            'course' => $course,
            'context' => $coursecontext,
            'cm' => $cm,
            'messages' => self::sanitise_messages($input['messages'] ?? []),
            'location' => self::location_from_pagetype($pagetype),
        ];
    }

    /**
     * Map a Moodle page type (e.g. "mod-forum-discuss") to a location key of the system message.
     *
     * The page type is untrusted client input: it is truncated, reduced to [a-zA-Z0-9_-] and
     * lower-cased before matching. Anything unrecognised maps to 'unknown'.
     *
     * @param string $pagetype The client-supplied page type.
     * @return string One of course, activity, grades, admin, dashboard, messages, profile, calendar, files or unknown.
     */
    public static function location_from_pagetype(string $pagetype): string {
        $pagetype = \core_text::substr($pagetype, 0, self::MAX_PAGETYPE_LENGTH);
        $pagetype = \core_text::strtolower(clean_param($pagetype, PARAM_ALPHANUMEXT));
        if ($pagetype === '') {
            return 'unknown';
        }

        foreach (self::LOCATION_PREFIXES as $prefix => $location) {
            if (str_starts_with($pagetype, $prefix)) {
                return $location;
            }
        }

        return 'unknown';
    }

    /**
     * Refuse the request unless the tutor is enabled both site-wide and for the course.
     *
     * Shared by the chat proxy and the AJAX external functions so that every entry point
     * applies the same per-course gate.
     *
     * @param int $courseid Course id.
     * @throws \moodle_exception When the tutor is not available for the course.
     */
    public static function assert_course_enabled(int $courseid): void {
        if (!get_config('local_dttutor', 'enabled') || !course_config::is_enabled_for_course($courseid)) {
            throw new \moodle_exception('error_tutor_not_available', 'local_dttutor');
        }
    }

    /**
     * Resolve a course module that must belong to the course and be visible to the user.
     *
     * @param int $cmid Course module id supplied by the client.
     * @param int $courseid Course the module must belong to.
     * @param int|null $userid User the module must be visible to (null for the current user).
     * @return \cm_info The resolved module.
     * @throws \moodle_exception When the module is not part of the course.
     * @throws \require_login_exception When the module is not visible to the user.
     */
    public static function resolve_cm(int $cmid, int $courseid, ?int $userid = null): \cm_info {
        // Note: get_cm() throws when the module does not belong to this course.
        $cm = get_fast_modinfo($courseid, $userid)->get_cm($cmid);
        if (!$cm->uservisible) {
            throw new \require_login_exception('Activity is hidden');
        }
        return $cm;
    }

    /**
     * Keep only well-formed user/assistant messages, capped in count and length.
     *
     * When more than MAX_MESSAGES are supplied, the most recent ones are kept.
     *
     * @param mixed $messages The raw client-supplied messages.
     * @return array List of ['role' => string, 'content' => string].
     */
    public static function sanitise_messages($messages): array {
        if (!is_array($messages)) {
            return [];
        }

        $clean = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role = $message['role'] ?? null;
            $content = $message['content'] ?? null;
            if (!in_array($role, self::ALLOWED_ROLES, true) || !is_string($content)) {
                continue;
            }
            $clean[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, self::MAX_MESSAGE_LENGTH),
            ];
        }

        return array_slice($clean, -self::MAX_MESSAGES);
    }
}
