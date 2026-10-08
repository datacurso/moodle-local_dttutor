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

use local_dttutor\httpclient\client_factory;
use local_dttutor\httpclient\tutoria_api;

/**
 * Sends the unread documents of a course to the AI service and keeps the text that comes back.
 *
 * The text is kept against the content hash of the file: a document is read once and not once
 * per question, and a file that never changes is never sent twice. {@see file_content} hands out
 * what is kept here.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_reader {
    /** @var int Documents read in one request, which is also what one course may contribute. */
    public const MAX_FILES = 25;

    /** @var int Bytes one document may weigh to be worth sending. */
    public const MAX_BYTES = 10485760;

    /**
     * @var int Bytes of documents one request carries at most, unless configured otherwise.
     *
     * The documents are encoded in memory to be sent. Twenty-five documents of ten megabytes each
     * came to more than a gigabyte of strings between the content, its encoding and the request,
     * past the memory of any PHP worker, and a fatal error left nothing cached, so the next
     * question tried again. The documents that do not fit wait for the next build.
     */
    public const DEFAULT_MAX_REQUEST_BYTES = 16777216;

    /** @var int Seconds after which the reading of a course is let go even if its request died. */
    private const LOCK_MAX_LIFETIME = 300;

    /** @var string Type of the lock factory the readings of a course share. */
    private const LOCK_TYPE = 'local_dttutor_documents';

    /**
     * @var string[] Extensions the service can read.
     *
     * Checked here as well as there: a folder may hold a video of half a gigabyte, and encoding
     * it to send it away only to be told that it cannot be read would be a poor trade.
     */
    public const READABLE_EXTENSIONS = ['pdf', 'docx', 'pptx', 'txt', 'md', 'csv', 'html', 'htm'];

    /** @var string[] Reasons for leaving a document out that only hold for one request. */
    private const RETRYABLE_SKIPS = ['no_budget_left', 'too_many_files'];

    /**
     * Read the documents of a course that are not read yet, in one request.
     *
     * Whatever cannot be read is remembered as well, so a document that has no text in it is not
     * sent again with every rebuild.
     *
     * One course is read by one request at a time: the students who open the tutor together on a
     * course nobody has asked about yet would otherwise each send the same documents.
     *
     * @param \course_modinfo $modinfo Course the documents belong to.
     * @param int[] $cmids Activities whose documents may be read, in the order they are listed.
     * @return bool False when documents are still unread: the service could not be asked, another
     *              request is reading them, or they did not all fit in one request.
     */
    public static function prefetch(\course_modinfo $modinfo, array $cmids): bool {
        if (!file_content::is_enabled()) {
            return true;
        }

        $cache = \cache::make('local_dttutor', 'file_text');
        [$pending] = self::pending_files($modinfo, $cmids, $cache);
        if ($pending === []) {
            return true;
        }

        $courseid = (int)$modinfo->get_course_id();
        $factory = \core\lock\lock_config::get_lock_factory(self::LOCK_TYPE);
        $lock = $factory->get_lock('course_' . $courseid, 0, self::LOCK_MAX_LIFETIME);
        if ($lock === false) {
            return false;
        }

        try {
            // Read again under the lock: the request that held it may have read them meanwhile.
            [$pending, $more] = self::pending_files($modinfo, $cmids, $cache);
            if ($pending === []) {
                return true;
            }

            $api = self::resolve_api();
            if ($api === null) {
                return false;
            }

            $response = self::request_texts($api, $pending, $courseid);
            if ($response === null) {
                return false;
            }

            self::remember_extracted($response['extracted'] ?? [], $cache);
            self::remember_skipped($response['skipped'] ?? [], $cache);
            return !$more;
        } finally {
            $lock->release();
        }
    }

    /**
     * Bytes of documents one request carries at most.
     *
     * @return int
     */
    public static function max_request_bytes(): int {
        $configured = (int)get_config('local_dttutor', 'document_request_bytes');
        return $configured > 0 ? $configured : self::DEFAULT_MAX_REQUEST_BYTES;
    }

    /**
     * The documents of the activities that still have to be read, by content hash.
     *
     * @param \course_modinfo $modinfo Course the documents belong to.
     * @param int[] $cmids Activities whose documents may be read.
     * @param \cache $cache Text already read, by content hash.
     * @return array{0: \stored_file[], 1: bool} The documents that fit in one request, by content
     *         hash, and whether more are left for the next one.
     */
    private static function pending_files(\course_modinfo $modinfo, array $cmids, \cache $cache): array {
        $pending = [];
        $bytes = 0;
        $more = false;
        foreach ($cmids as $cmid) {
            $files = file_content::files_of($modinfo, (int)$cmid);
            [$pending, $bytes, $left] = self::add_pending($files, $pending, $bytes, $cache);
            $more = $more || $left;
        }
        return [$pending, $more];
    }

    /**
     * Add the files of one activity that still have to be read, up to the limit of a request.
     *
     * A file of a kind the service cannot read is remembered as such, so it is never looked at
     * again.
     *
     * @param \stored_file[] $files Files of the activity.
     * @param \stored_file[] $pending Documents already waiting, by content hash.
     * @param int $bytes Bytes of the documents already waiting.
     * @param \cache $cache Text already read, by content hash.
     * @return array{0: \stored_file[], 1: int, 2: bool} The documents waiting, by content hash,
     *         their bytes, and whether a document of this activity was left for a later request.
     */
    private static function add_pending(array $files, array $pending, int $bytes, \cache $cache): array {
        $left = false;
        foreach ($files as $file) {
            $hash = $file->get_contenthash();
            if (isset($pending[$hash]) || $cache->get($hash) !== false) {
                continue;
            }
            if (!self::is_worth_sending($file)) {
                $cache->set($hash, ['text' => '', 'reason' => 'unsupported_type']);
                continue;
            }
            // The first document always fits, so one larger than the budget is still read alone.
            $size = (int)$file->get_filesize();
            $overbudget = $pending !== [] && $bytes + $size > self::max_request_bytes();
            if (count($pending) >= self::MAX_FILES || $overbudget) {
                $left = true;
                continue;
            }
            $pending[$hash] = $file;
            $bytes += $size;
        }
        return [$pending, $bytes, $left];
    }

    /**
     * The API the documents are read through, or null when it cannot be built.
     *
     * The client is resolved here on purpose. Asking the API for it would resolve it deep inside
     * the request, where a provider that is not installed arrives as a failure of the reading
     * rather than as what it is: a site where the tutor answers nothing at all. Nothing is
     * recorded either: the chat reports that first.
     *
     * @return tutoria_api|null
     */
    private static function resolve_api(): ?tutoria_api {
        try {
            client_factory::get();
            return \core\di::get(tutoria_api::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ask the service for the text of the documents.
     *
     * A failure is recorded with its reason. The documents stay unread: the activity still
     * travels with its name and its description, and the next build tries again.
     *
     * @param tutoria_api $api API the documents are read through.
     * @param \stored_file[] $pending Documents to read, by content hash.
     * @param int $courseid Course the documents belong to.
     * @return array|null The response of the service, or null when it could not answer.
     */
    private static function request_texts(tutoria_api $api, array $pending, int $courseid): ?array {
        try {
            $payload = self::as_payload($pending);
            $charsperfile = file_content::chars_per_file();
            return $api->extract_material($payload, $charsperfile);
        } catch (\Throwable $e) {
            $reason = document_read_failure::record($e, $courseid);
            debugging('Reading the documents of the course failed: ' . $reason, DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Keep the text of each document the service read.
     *
     * @param array[] $entries Documents read, as the service lists them.
     * @param \cache $cache Text already read, by content hash.
     */
    private static function remember_extracted(array $entries, \cache $cache): void {
        foreach ($entries as $entry) {
            $hash = (string)($entry['sha1'] ?? '');
            if ($hash !== '') {
                $cache->set($hash, ['text' => (string)($entry['text'] ?? ''), 'reason' => '']);
            }
        }
    }

    /**
     * Remember each document the service could not read, and why.
     *
     * A document left out for want of budget is not remembered: the next build, with a budget of
     * its own, has to try it again.
     *
     * @param array[] $entries Documents left out, as the service lists them.
     * @param \cache $cache Text already read, by content hash.
     */
    private static function remember_skipped(array $entries, \cache $cache): void {
        foreach ($entries as $entry) {
            $hash = (string)($entry['sha1'] ?? '');
            $reason = (string)($entry['reason'] ?? 'unknown');
            if ($hash === '' || in_array($reason, self::RETRYABLE_SKIPS, true)) {
                continue;
            }
            $cache->set($hash, ['text' => '', 'reason' => $reason]);
        }
    }

    /**
     * Whether a document is worth the journey.
     *
     * @param \stored_file $file
     * @return bool
     */
    private static function is_worth_sending(\stored_file $file): bool {
        $size = $file->get_filesize();
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return false;
        }
        $filename = \core_text::strtolower($file->get_filename());
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($extension, self::READABLE_EXTENSIONS, true);
    }

    /**
     * Turn the documents into what the service expects to receive.
     *
     * @param \stored_file[] $files Documents to read, by content hash.
     * @return array[] One entry per document, as the service expects it.
     */
    private static function as_payload(array $files): array {
        $payload = [];
        foreach ($files as $hash => $file) {
            $payload[] = [
                'filename' => $file->get_filename(),
                'mimetype' => (string)$file->get_mimetype(),
                'sha1' => (string)$hash,
                // Encoded straight from the storage: the raw content is never kept beside it.
                'content_base64' => base64_encode($file->get_content()),
            ];
        }
        return $payload;
    }
}
