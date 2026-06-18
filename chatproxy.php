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
 * AI Chat Proxy — SSE endpoint for tool-calling loop.
 *
 * Thin entry point: validates auth, builds system message, delegates to handler.
 * The tool loop runs entirely in PHP, calling the Datacurso AI proxy (which
 * holds the actual API key). The client's License-Key is read from the
 * aiprovider_datacurso plugin — zero additional configuration required.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use local_dttutor\proxy\system_message;
use local_dttutor\proxy\handler;
use local_dttutor\httpclient\tutoria_api;

// Method check.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain');
    die('Method Not Allowed');
}

// Auth — must be logged in with valid sesskey.
require_login();

// Parse input.
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['messages'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'invalid_request']));
}

$sesskey = $input['sesskey'] ?? '';
if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'invalid_sesskey']));
}

// Ensure user has capability to use the plugin.
$sysctx = context_system::instance();
if (!has_capability('local/dttutor:use', $sysctx)) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'permission_denied']));
}

// Model is a placeholder — the Datacurso AI proxy decides server-side.
$model    = 'gemini-2.5-flash';
$messages = array_values($input['messages']);
$context  = $input['context'] ?? [];
$resetsession = !empty($input['reset_session']);

// Detect user role and build context.
$role = \local_dttutor_get_user_role();

// Build page context if not provided.
if (empty($context)) {
    $context = \local_dttutor_get_page_context();
}

// Resolve instance ID from cmid so the AI doesn't have to guess it.
$cmid = $context['activity_id'] ?? 0;
if ($cmid > 0 && empty($context['activity_instance'])) {
    $cminfo = get_coursemodule_from_id('', $cmid);
    if ($cminfo) {
        $context['activity_instance'] = (int)$cminfo->instance;
        if (empty($context['activity_type'])) {
            $context['activity_type'] = $cminfo->modname;
        }
    }
}

// Redis persistence: start session + save user message.
// Must happen BEFORE system message is prepended to $messages.
$sessionid = null;
$tutoriaapi = null;
$courseid = (int)($context['course_id'] ?? 0);
$cmid = isset($context['activity_id']) ? (int)$context['activity_id'] : null;
$usermessage = '';
for ($i = count($messages) - 1; $i >= 0; $i--) {
    if (($messages[$i]['role'] ?? '') === 'user') {
        $usermessage = $messages[$i]['content'] ?? '';
        break;
    }
}

if ($courseid > 0 && class_exists('\\local_dttutor\\httpclient\\tutoria_api')) {
    try {
        $tutoriaapi = new tutoria_api();
        $session = $resetsession ?
            $tutoriaapi->reset_session_v2($courseid, $USER->id, $cmid) :
            $tutoriaapi->start_session_v2($courseid, $USER->id, $cmid);
        $sessionid = $session['session_id'] ?? null;

        if ($sessionid && $resetsession) {
            foreach ($messages as $message) {
                $role = $message['role'] ?? '';
                $content = trim((string)($message['content'] ?? ''));
                if (($role === 'user' || $role === 'assistant') && $content !== '') {
                    $tutoriaapi->append_message($sessionid, $role, $content);
                }
            }
        } else if ($sessionid && $usermessage !== '') {
            $tutoriaapi->append_message($sessionid, 'user', $usermessage);
        }
    } catch (\Throwable $e) {
        debugging('Failed to persist user message: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

// Pre-load deterministic course knowledge (structure, activities, dates, grades) so the model
// can answer most questions in a single LLM call instead of the expensive tool-discovery loop.
$preloaded = '';
if ($courseid > 0) {
    try {
        $preloaded = \local_dttutor\proxy\context_preloader::build($courseid, (int)$USER->id, $context);
    } catch (\Throwable $e) {
        debugging('Failed to pre-load course knowledge: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

// Build system message.
$system = system_message::build($role, $context, $preloaded);
$messages = array_merge([$system], $messages);

// SSE headers.
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
ob_implicit_flush(true);

// Run tool loop.
$responsetext = handler::run($model, $messages, $role);

// Post-response: save AI response to Redis.
if ($sessionid !== null && $responsetext !== null && $tutoriaapi !== null) {
    try {
        $tutoriaapi->append_message($sessionid, 'assistant', $responsetext);
    } catch (\Throwable $e) {
        debugging('Failed to persist AI response: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}
