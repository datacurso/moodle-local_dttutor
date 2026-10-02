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

/**
 * The text of the documents a course hands out.
 *
 * Nothing in Moodle reads a PDF, so the document is sent to the Datacurso AI service, which
 * reads it and hands the text back ({@see document_reader}). What it read is handed out here,
 * within the budget of the message.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_content {
    /** @var int Characters one document may contribute, unless configured otherwise. */
    public const DEFAULT_CHARS_PER_FILE = 4000;

    /** @var string Prefix of the text read out of a document. */
    public const DOCUMENT_LABEL = 'Document ';

    /**
     * Whether the documents of the course are read.
     *
     * On unless a site says otherwise, like the material they belong to, and off whenever that is:
     * a document is material, and reading one when the rest of the course stays behind would make
     * no sense.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        $configured = get_config('local_dttutor', 'include_files');
        if ($configured !== false && !(bool)$configured) {
            return false;
        }
        return activity_content::is_enabled();
    }

    /**
     * Characters one document may contribute.
     *
     * @return int
     */
    public static function chars_per_file(): int {
        $configured = (int)get_config('local_dttutor', 'file_chars');
        if ($configured > 0) {
            return $configured;
        }
        return self::DEFAULT_CHARS_PER_FILE;
    }

    /**
     * Read the documents of a course that are not read yet, in one request.
     *
     * Called before the knowledge block is built so that the whole course costs a single call
     * instead of one per activity.
     *
     * @param \course_modinfo $modinfo Course the documents belong to.
     * @param int[] $cmids Activities whose documents may be read, in the order they are listed.
     * @return bool False when the service could not be asked, so the documents are still unread.
     */
    public static function prefetch(\course_modinfo $modinfo, array $cmids): bool {
        return document_reader::prefetch($modinfo, $cmids);
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
        return implode("\n", self::documents_of($modinfo, $cmid, $budget));
    }

    /**
     * The text read out of each document of one activity, sharing the budget between them.
     *
     * Each document gets an equal share of what is left, and what one leaves unused passes on
     * to the next. Handing the whole budget to the first document would leave the others of a
     * folder with nothing, however short the first one is.
     *
     * @param \course_modinfo $modinfo Course the activity belongs to.
     * @param int $cmid Activity whose documents are wanted.
     * @param int $budget Characters available for the documents of this activity.
     * @return string[] The text of each document that travels, by file name.
     */
    public static function documents_of(\course_modinfo $modinfo, int $cmid, int $budget): array {
        if (!self::is_enabled() || $budget <= 0) {
            return [];
        }

        $texts = self::readable_texts($modinfo, $cmid);
        $parts = [];
        $left = count($texts);
        foreach ($texts as $filename => $text) {
            $share = intdiv($budget, $left--);
            if ($share <= 0) {
                break;
            }

            $line = self::DOCUMENT_LABEL . $filename . ': ' . $text;
            if (\core_text::strlen($line) > $share) {
                $line = \core_text::substr($line, 0, $share) . activity_content::TRUNCATION_MARK;
            }
            $budget -= \core_text::strlen($line);
            $parts[$filename] = $line;
        }

        return $parts;
    }

    /**
     * Whether any document of an activity has text that could travel.
     *
     * @param \course_modinfo $modinfo Course the activity belongs to.
     * @param int $cmid Activity whose documents are checked.
     * @return bool
     */
    public static function has_text(\course_modinfo $modinfo, int $cmid): bool {
        return self::is_enabled() && self::readable_texts($modinfo, $cmid) !== [];
    }

    /**
     * The text already read out of the documents of an activity.
     *
     * @param \course_modinfo $modinfo Course the activity belongs to.
     * @param int $cmid Activity whose documents are wanted.
     * @return string[] Text of each document that has any, by file name.
     */
    private static function readable_texts(\course_modinfo $modinfo, int $cmid): array {
        $cache = \cache::make('local_dttutor', 'file_text');
        $texts = [];
        foreach (self::files_of($modinfo, $cmid) as $file) {
            $cached = $cache->get($file->get_contenthash());
            if (is_array($cached) && trim((string)($cached['text'] ?? '')) !== '') {
                $texts[$file->get_filename()] = (string)$cached['text'];
            }
        }
        return $texts;
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
}
