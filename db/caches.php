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
 * Cache definitions for local_dttutor.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Pre-loaded course knowledge (structure, activities, dates, max grades) for the chat proxy.
    // Keyed by "{courseid}_{userid}" because it only lists modules visible to that user; the stored
    // payload carries the course cacherev so stale entries are rebuilt automatically on course edits.
    'course_knowledge' => [
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 86400, // 24 hours; also invalidated by course cacherev mismatch.
        'simpletest' => true,
    ],
    // Remote chat session handles (session id, TTL, validation timestamps) keyed by
    // "session_v2_{courseid}_{userid}[_{cmid}]", so a session is reused instead of being
    // re-created on every request. The durable copy lives in local_dttutor_session.
    'sessions' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
