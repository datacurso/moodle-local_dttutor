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
 * AI Chat Proxy — SSE endpoint for the Tutor-AI chat.
 *
 * Thin entry point: authorizes the request against the requested course,
 * builds the system message and delegates the streaming call to the handler.
 * The Datacurso AI proxy holds the actual API key; the client's License-Key
 * is read from the aiprovider_datacurso plugin.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Never let debug output leak into the SSE stream.
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use local_dttutor\httpclient\tutoria_api;
use local_dttutor\proxy\context_preloader;
use local_dttutor\proxy\handler;
use local_dttutor\proxy\request_guard;
use local_dttutor\proxy\system_message;

// Method check.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain');
    die('Method Not Allowed');
}

// A session must exist; the course-level access check is performed by request_guard.
try {
    require_login(null, false, null, false, true);
} catch (\moodle_exception $e) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'permission_denied']));
}

// Parse input, refusing oversized bodies before decoding them.
$maxbodybytes = 512 * 1024;
$rawbody = file_get_contents('php://input', false, null, 0, $maxbodybytes + 1);
if ($rawbody === false || strlen($rawbody) > $maxbodybytes) {
    http_response_code(413);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'request_too_large']));
}

$input = json_decode($rawbody, true);
unset($rawbody);
if (!is_array($input) || !isset($input['messages'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'invalid_request']));
}

$sesskey = $input['sesskey'] ?? '';
if (!is_string($sesskey) || !confirm_sesskey($sesskey)) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'invalid_sesskey']));
}

// Authorize against the requested course: login, enrolment, capability, enablement, module.
try {
    $auth = request_guard::authorize($input);
} catch (\moodle_exception $e) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'permission_denied']));
}

$course   = $auth->course;
$courseid = (int)$course->id;
$cm       = $auth->cm;
$messages = $auth->messages;

if ($messages === []) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'invalid_request']));
}

// Model is a placeholder — the Datacurso AI proxy decides server-side.
$model        = 'gemini-2.5-flash';
$resetsession = !empty($input['reset_session']);

// Only presentation hints are taken from the client; course, activity and location come from the guard.
$context = is_array($input['context'] ?? null) ? $input['context'] : [];
$context['course_id']   = $courseid;
$context['course_name'] = format_string($course->fullname, true, ['context' => $auth->context]);
$context['location']    = $auth->location;
unset($context['activity_id'], $context['activity_instance'], $context['activity_type'], $context['pagetype']);
$cmid = null;
if ($cm !== null) {
    $cmid = (int)$cm->id;
    $context['activity_id']       = $cmid;
    $context['activity_instance'] = (int)$cm->instance;
    $context['activity_type']     = $cm->modname;
}

// Detect user role (course-level roles resolve because require_login() set $COURSE).
$role = \local_dttutor_get_user_role();

// Redis persistence: start session + save user message.
// Must happen BEFORE system message is prepended to $messages.
$sessionid   = null;
$tutoriaapi  = null;
$usermessage = '';
for ($i = count($messages) - 1; $i >= 0; $i--) {
    if ($messages[$i]['role'] === 'user') {
        $usermessage = $messages[$i]['content'];
        break;
    }
}

try {
    $tutoriaapi = new tutoria_api();
    $session = $resetsession ?
        $tutoriaapi->reset_session_v2($courseid, $USER->id, $cmid) :
        $tutoriaapi->start_session_v2($courseid, $USER->id, $cmid);
    $sessionid = $session['session_id'] ?? null;

    if ($sessionid && $resetsession) {
        foreach ($messages as $message) {
            $replayrole = $message['role'];
            $content = trim($message['content']);
            if ($content !== '') {
                $tutoriaapi->append_message($sessionid, $replayrole, $content);
            }
        }
    } else if ($sessionid && $usermessage !== '') {
        $tutoriaapi->append_message($sessionid, 'user', $usermessage);
    }
} catch (\Throwable $e) {
    \local_dttutor_log('SESSION_PERSIST_FAILED', ['exception' => get_class($e)], true);
}

// Pre-load deterministic course knowledge (structure, activities, dates, grades) visible to this user.
$preloaded = '';
try {
    $preloaded = context_preloader::build($courseid, (int)$USER->id, $context);
} catch (\Throwable $e) {
    \local_dttutor_log('CONTEXT_PRELOAD_FAILED', ['exception' => get_class($e)], true);
}

// Build system message.
$system = system_message::build($role, $context, $preloaded);
$messages = array_merge([$system], $messages);

// SSE headers.
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
ob_implicit_flush(true);

// Release the session lock before the long-running upstream call; nothing below writes to the session.
\core\session\manager::write_close();

// Stream the model response.
$responsetext = handler::run($model, $messages, $role);

// Post-response: save AI response to Redis.
if ($sessionid !== null && $responsetext !== null && $tutoriaapi !== null) {
    try {
        $tutoriaapi->append_message($sessionid, 'assistant', $responsetext);
    } catch (\Throwable $e) {
        \local_dttutor_log('RESPONSE_PERSIST_FAILED', ['exception' => get_class($e)], true);
    }
}
