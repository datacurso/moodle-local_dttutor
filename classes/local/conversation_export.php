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

namespace local_dttutor\local;

use core_privacy\local\request\transform;
use local_dttutor\httpclient\tutoria_api;
use local_dttutor\proxy\document_read_failure;

/**
 * The messages of a conversation, as a privacy export hands them to the person they belong to.
 *
 * Moodle holds only the handle of a conversation: the messages live in the AI service. An export
 * that stopped at the handle told a person that a conversation existed without telling them what
 * was in it (Mindfree DTT-PRIV-001-R1). The messages are read from the service, page by page, and
 * whatever could not be read is said so in the export instead of leaving it silently short.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class conversation_export {
    /** @var int Messages asked for in one request, the largest page the history endpoint serves. */
    public const PAGE_SIZE = 100;

    /** @var int Pages read at most for one conversation, so a service that never says "no more" cannot loop. */
    public const MAX_PAGES = 100;

    /** @var tutoria_api|null API the messages are read through, or null when it cannot be built. */
    private ?tutoria_api $api;

    /** @var string|null Why the API could not be built, when it could not. */
    private ?string $unavailable = null;

    /**
     * Resolve the API once for the whole export.
     */
    public function __construct() {
        try {
            $this->api = \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            $this->api = null;
            $this->unavailable = pending_deletion::PROVIDER_UNAVAILABLE;
        }
    }

    /**
     * Every message of a conversation, oldest first.
     *
     * @param string $remotesessionid Session identifier in the AI service.
     * @return array{messages: \stdClass[], unavailable: string|null} The messages, and why they
     *         could not all be read when they could not.
     */
    public function messages_of(string $remotesessionid): array {
        if ($this->api === null) {
            return ['messages' => [], 'unavailable' => $this->unavailable];
        }

        $messages = [];
        try {
            for ($page = 0; $page < self::MAX_PAGES; $page++) {
                $response = $this->api->get_history($remotesessionid, self::PAGE_SIZE, $page * self::PAGE_SIZE);
                foreach ((array)($response['messages'] ?? []) as $message) {
                    $messages[] = self::as_export($message);
                }
                if (empty($response['pagination']['has_more']) || empty($response['messages'])) {
                    return ['messages' => $messages, 'unavailable' => null];
                }
            }
            return ['messages' => $messages, 'unavailable' => 'too_many_messages'];
        } catch (\Throwable $e) {
            if (pending_deletion::is_already_gone($e)) {
                // The service no longer has the conversation: nothing is left to hand over.
                return ['messages' => [], 'unavailable' => null];
            }
            return ['messages' => $messages, 'unavailable' => document_read_failure::reason_of($e)];
        }
    }

    /**
     * One message as it is exported: who wrote it, what it says and when.
     *
     * @param mixed $message A message as the history endpoint returns it.
     * @return \stdClass
     */
    private static function as_export($message): \stdClass {
        $message = is_array($message) ? $message : [];
        $timestamp = (int)($message['timestamp'] ?? 0);
        return (object)[
            'role' => (string)($message['role'] ?? ''),
            'content' => (string)($message['content'] ?? ''),
            'time' => $timestamp > 0 ? transform::datetime($timestamp) : '',
        ];
    }
}
