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

use local_dttutor\httpclient\tutoria_api;

/**
 * The text of the documents a course hands out.
 *
 * Nothing in Moodle reads a PDF, so the document is sent to the Datacurso AI service, which
 * reads it and hands the text back. The text is then kept against the content hash of the file:
 * a document is read once and not once per question, and a file that never changes is never
 * sent twice.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_content {
    /** @var int Characters one document may contribute, unless configured otherwise. */
    public const DEFAULT_CHARS_PER_FILE = 4000;

    /** @var int Documents read in one request, which is also what one course may contribute. */
    public const MAX_FILES = 25;

    /** @var string Prefix of the text read out of a document. */
    public const DOCUMENT_LABEL = 'Document ';

    /** @var int Bytes one document may weigh to be worth sending. */
    public const MAX_BYTES = 10485760;

    /**
     * @var string[] Extensions the service can read.
     *
     * Checked here as well as there: a folder may hold a video of half a gigabyte, and encoding
     * it to send it away only to be told that it cannot be read would be a poor trade.
     */
    public const READABLE_EXTENSIONS = ['pdf', 'docx', 'pptx', 'txt', 'md', 'csv', 'html', 'htm'];

    /**
     * Whether the administrator has allowed the documents of the course to be read.
     *
     * Off unless asked for. Sending a whole document to an external service is a wider step than
     * sending the text written in the activity itself, so it is a decision of its own.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool)get_config('local_dttutor', 'include_files')
            && activity_content::is_enabled();
    }

    /**
     * Characters one document may contribute.
     *
     * @return int
     */
    public static function chars_per_file(): int {
        $configured = (int)get_config('local_dttutor', 'file_chars');
        return $configured > 0 ? $configured : self::DEFAULT_CHARS_PER_FILE;
    }

    /**
     * Read the documents of a course that are not read yet, in one request.
     *
     * Called before the knowledge block is built so that the whole course costs a single call
     * instead of one per activity. Whatever cannot be read is remembered as well, so a document
     * that has no text in it is not sent again with every rebuild.
     *
     * @param \course_modinfo $modinfo Course the documents belong to.
     * @param int[] $cmids Activities whose documents may be read, in the order they are listed.
     */
    public static function prefetch(\course_modinfo $modinfo, array $cmids): void {
        if (!self::is_enabled()) {
            return;
        }

        $cache = \cache::make('local_dttutor', 'file_text');
        $pending = [];
        foreach ($cmids as $cmid) {
            foreach (self::files_of($modinfo, (int)$cmid) as $file) {
                $hash = $file->get_contenthash();
                if (isset($pending[$hash]) || $cache->get($hash) !== false) {
                    continue;
                }
                if (!self::is_worth_sending($file)) {
                    $cache->set($hash, ['text' => '', 'reason' => 'unsupported_type']);
                    continue;
                }
                if (count($pending) >= self::MAX_FILES) {
                    break 2;
                }
                $pending[$hash] = $file;
            }
        }

        if ($pending === []) {
            return;
        }

        try {
            $response = \core\di::get(tutoria_api::class)->extract_material(
                self::as_payload($pending),
                self::chars_per_file()
            );
        } catch (\Throwable $e) {
            // The documents simply stay unread: the activity still travels with its name and its
            // description, and the next build tries again.
            debugging('Reading the documents of the course failed: ' . get_class($e), DEBUG_DEVELOPER);
            return;
        }

        foreach (($response['extracted'] ?? []) as $entry) {
            $hash = (string)($entry['sha1'] ?? '');
            if ($hash !== '') {
                $cache->set($hash, ['text' => (string)($entry['text'] ?? ''), 'reason' => '']);
            }
        }
        foreach (($response['skipped'] ?? []) as $entry) {
            $hash = (string)($entry['sha1'] ?? '');
            $reason = (string)($entry['reason'] ?? 'unknown');
            // A document left out for want of budget is not remembered: the next build, with a
            // budget of its own, has to try it again.
            if ($hash !== '' && $reason !== 'no_budget_left' && $reason !== 'too_many_files') {
                $cache->set($hash, ['text' => '', 'reason' => $reason]);
            }
        }
    }

    /**
     * The text read out of the documents of one activity, within the budget that is left.
     *
     * The documents spend the same budget as the rest of the material. A course that hands out
     * a library would otherwise push the whole message past what a question can carry.
     *
     * @param \course_modinfo $modinfo Course the activity belongs to.
     * @param int $cmid Activity whose documents are wanted.
     * @param int $budget Characters still available for the whole message.
     * @return string The text of each document, or an empty string when none could be read.
     */
    public static function text_of(\course_modinfo $modinfo, int $cmid, int $budget): string {
        if (!self::is_enabled() || $budget <= 0) {
            return '';
        }

        $cache = \cache::make('local_dttutor', 'file_text');
        $parts = [];
        foreach (self::files_of($modinfo, $cmid) as $file) {
            if ($budget <= 0) {
                break;
            }
            $cached = $cache->get($file->get_contenthash());
            if (!is_array($cached) || trim((string)($cached['text'] ?? '')) === '') {
                continue;
            }

            $line = self::DOCUMENT_LABEL . $file->get_filename() . ': ' . $cached['text'];
            if (\core_text::strlen($line) > $budget) {
                $line = \core_text::substr($line, 0, $budget) . activity_content::TRUNCATION_MARK;
            }
            $budget -= \core_text::strlen($line);
            $parts[] = $line;
        }

        return implode("\n", $parts);
    }

    /**
     * The documents of an activity that were written by the teaching side.
     *
     * @param \course_modinfo $modinfo
     * @param int $cmid
     * @return \stored_file[]
     */
    public static function files_of(\course_modinfo $modinfo, int $cmid): array {
        $cms = $modinfo->get_cms();
        if (!isset($cms[$cmid])) {
            return [];
        }
        $cm = $cms[$cmid];
        [$component, $filearea] = context_preloader::file_area_of($cm->modname);
        if ($component === null || !$cm->uservisible) {
            return [];
        }

        try {
            return get_file_storage()->get_area_files(
                \context_module::instance($cmid)->id,
                $component,
                $filearea,
                false,
                'filename',
                false
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Whether a document is worth the journey.
     *
     * @param \stored_file $file
     * @return bool
     */
    private static function is_worth_sending(\stored_file $file): bool {
        if ($file->get_filesize() <= 0 || $file->get_filesize() > self::MAX_BYTES) {
            return false;
        }
        $extension = \core_text::strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
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
                'content_base64' => base64_encode($file->get_content()),
            ];
        }
        return $payload;
    }
}
