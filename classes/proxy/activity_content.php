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
 * The text of an activity that may travel to the AI service.
 *
 * Only material written by whoever teaches the course is ever read: the description of an
 * activity, the body of a page, the visible chapters of a book, the instructions of an assignment
 * and the address of a URL. Anything written by the people taking the course lives in other tables
 * that this class never opens, and material that is being assessed is left out on purpose.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_content {
    /** @var int Characters of content one activity may contribute, unless configured otherwise. */
    public const DEFAULT_CHARS_PER_ACTIVITY = 1500;

    /** @var int Characters of content one message may carry in total, unless configured otherwise. */
    public const DEFAULT_CHARS_TOTAL = 15000;

    /** @var string Marker that tells the model it is reading part of a longer text. */
    public const TRUNCATION_MARK = ' […]';

    /**
     * Activities whose body is written by the teaching side and can therefore be read.
     *
     * Everything not listed here contributes its description and nothing else. That is the point
     * of the list: a module joins it only after someone has checked where its text comes from.
     *
     * @var string[]
     */
    public const READABLE_BODY = ['page', 'book', 'assign', 'url'];

    /**
     * Whether the administrator has allowed the content of the course to reach the AI service.
     *
     * Off unless asked for: sending the material of a course to an external service is a decision
     * about data protection, not a default.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool)get_config('local_dttutor', 'include_content');
    }

    /**
     * Characters of content one activity may contribute.
     *
     * @return int
     */
    public static function chars_per_activity(): int {
        $configured = (int)get_config('local_dttutor', 'content_chars_per_activity');
        return $configured > 0 ? $configured : self::DEFAULT_CHARS_PER_ACTIVITY;
    }

    /**
     * Characters of content one message may carry in total.
     *
     * @return int
     */
    public static function chars_total(): int {
        $configured = (int)get_config('local_dttutor', 'content_chars_total');
        return $configured > 0 ? $configured : self::DEFAULT_CHARS_TOTAL;
    }

    /**
     * The text of one activity, within the budget that is left.
     *
     * @param \cm_info $cm The activity, already known to be visible to the user.
     * @param \stdClass|null $record Its instance record, or null when it could not be read.
     * @param int $budget Characters still available for the whole message.
     * @return string The text, or an empty string when there is nothing to say.
     */
    public static function extract(\cm_info $cm, ?\stdClass $record, int $budget): string {
        if ($budget <= 0 || $record === null) {
            return '';
        }

        $limit = min(self::chars_per_activity(), $budget);
        $context = \context_module::instance((int)$cm->id);

        $parts = [];

        $intro = self::to_text($record->intro ?? '', (int)($record->introformat ?? FORMAT_HTML), $context);
        if ($intro !== '') {
            $parts[] = $intro;
        }

        if (in_array($cm->modname, self::READABLE_BODY, true)) {
            $body = self::extract_body($cm, $record, $context);
            if ($body !== '') {
                $parts[] = $body;
            }
        }

        if (empty($parts)) {
            return '';
        }

        return self::truncate(implode("\n", $parts), $limit);
    }

    /**
     * The body of an activity whose text is written by the teaching side.
     *
     * @param \cm_info $cm
     * @param \stdClass $record
     * @param \context $context
     * @return string
     */
    private static function extract_body(\cm_info $cm, \stdClass $record, \context $context): string {
        switch ($cm->modname) {
            case 'page':
                return self::to_text(
                    $record->content ?? '',
                    (int)($record->contentformat ?? FORMAT_HTML),
                    $context
                );

            case 'assign':
                // The instructions the student reads on the submission page.
                return self::to_text(
                    $record->activity ?? '',
                    (int)($record->activityformat ?? FORMAT_HTML),
                    $context
                );

            case 'url':
                $url = trim((string)($record->externalurl ?? ''));
                return $url === '' ? '' : 'Link: ' . $url;

            case 'book':
                return self::extract_book_chapters((int)$record->id, $context);

            default:
                return '';
        }
    }

    /**
     * The visible chapters of a book, in the order a reader meets them.
     *
     * A chapter hidden by the teacher is skipped, the same way the student never sees it.
     *
     * @param int $bookid
     * @param \context $context
     * @return string
     */
    private static function extract_book_chapters(int $bookid, \context $context): string {
        global $DB;

        try {
            $chapters = $DB->get_records(
                'book_chapters',
                ['bookid' => $bookid, 'hidden' => 0],
                'pagenum ASC',
                'id, title, content, contentformat'
            );
        } catch (\Throwable $e) {
            return '';
        }

        $parts = [];
        foreach ($chapters as $chapter) {
            $title = trim(format_string($chapter->title));
            $text = self::to_text($chapter->content ?? '', (int)($chapter->contentformat ?? FORMAT_HTML), $context);
            if ($title === '' && $text === '') {
                continue;
            }
            $parts[] = trim($title . "\n" . $text);
        }

        return implode("\n", $parts);
    }

    /**
     * Turn stored content into the plain text the model reads.
     *
     * Filters run as they would for a reader, so what travels is the same text the student sees,
     * in one language, with no markup and no references to files that the model cannot open.
     *
     * @param string $content
     * @param int $format
     * @param \context $context
     * @return string
     */
    private static function to_text(string $content, int $format, \context $context): string {
        if (trim($content) === '') {
            return '';
        }

        // References to embedded files mean nothing to the model and would only spend characters.
        $content = str_replace('@@PLUGINFILE@@', '', $content);

        try {
            $html = format_text($content, $format, ['context' => $context, 'para' => false, 'newlines' => false]);
        } catch (\Throwable $e) {
            return '';
        }

        // Turning HTML into text shouts bold, headings and table headers in capitals. The model
        // would read that as emphasis nobody wrote, so those tags are levelled beforehand and the
        // words reach it as the author typed them.
        $html = preg_replace('#</?(b|strong)(\s[^>]*)?>#i', '', (string)$html);
        $html = preg_replace('#<h[1-6](\s[^>]*)?>#i', '<p>', (string)$html);
        $html = preg_replace('#</h[1-6]>#i', '</p>', (string)$html);
        $html = preg_replace('#<(/?)th(\s[^>]*)?>#i', '<$1td>', (string)$html);

        $text = html_to_text((string)$html, 0, false);
        $text = preg_replace('/\n{3,}/', "\n\n", (string)$text);

        return trim((string)$text);
    }

    /**
     * Cut a text to the characters allowed, saying so when something was left out.
     *
     * @param string $text
     * @param int $limit
     * @return string
     */
    private static function truncate(string $text, int $limit): string {
        if ($limit <= 0) {
            return '';
        }
        if (\core_text::strlen($text) <= $limit) {
            return $text;
        }

        return \core_text::substr($text, 0, $limit) . self::TRUNCATION_MARK;
    }
}
