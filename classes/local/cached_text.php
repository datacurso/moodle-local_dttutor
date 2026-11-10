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

/**
 * Removes from the caches the course text the tutor keeps, when a course or a user goes (DTT-PRIV-005).
 *
 * Two caches hold text taken from a course:
 *
 * - file_text: the text read out of each document, keyed by the content hash of the file;
 * - course_knowledge: the block of course knowledge built for a user, keyed by course and user,
 *   which carries that text too.
 *
 * Both expire on their own (a week and a day), but a deleted course or user, or a privacy
 * deletion, must not leave that text behind until then.
 *
 * The cache API cannot list keys. The documents of a course are found through its files, so this
 * must run while they still exist (before the course is deleted). The knowledge blocks of a course,
 * or of a user, cannot be found without knowing the other half of the key, so in those cases the
 * whole knowledge cache is purged: it is built from Moodle data alone, and a question pays the
 * rebuild without contacting any service. A deletion for one user in one course removes only that key.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cached_text {
    /** @var int Keys deleted per call to the cache store. */
    private const BATCH = 500;

    /**
     * Key of the knowledge block of a user in a course.
     *
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    public static function knowledge_key(int $courseid, int $userid): string {
        return $courseid . '_' . $userid;
    }

    /**
     * Forget everything cached from a course: the text of its documents and the knowledge blocks.
     *
     * Must run while the files of the course still exist.
     *
     * @param int $courseid
     */
    public static function forget_course(int $courseid): void {
        self::forget_course_documents($courseid);
        self::purge_knowledge();
    }

    /**
     * Forget the knowledge blocks built for a user, in every course.
     *
     * @param int $userid
     */
    public static function forget_user(int $userid): void {
        self::purge_knowledge();
    }

    /**
     * Forget the knowledge block built for some users in a course.
     *
     * @param int $courseid
     * @param int[] $userids
     */
    public static function forget_users_in_course(int $courseid, array $userids): void {
        $keys = [];
        foreach ($userids as $userid) {
            $keys[] = self::knowledge_key($courseid, (int)$userid);
        }
        if ($keys !== []) {
            \cache::make('local_dttutor', 'course_knowledge')->delete_many($keys);
        }
    }

    /**
     * Forget the text read out of the documents of a course.
     *
     * Every file of the course and its activities is looked up, read or not: deleting a key that
     * was never stored costs nothing. A document shared with another course is forgotten as well,
     * and read again there when it is next needed.
     *
     * @param int $courseid
     */
    public static function forget_course_documents(int $courseid): void {
        global $DB;

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }

        $sql = "SELECT DISTINCT f.contenthash
                  FROM {files} f
                  JOIN {context} ctx ON ctx.id = f.contextid
                 WHERE (ctx.id = :contextid OR " . $DB->sql_like('ctx.path', ':path') . ")
                   AND f.filename <> '.'";
        $hashes = $DB->get_fieldset_sql($sql, [
            'contextid' => $context->id,
            'path' => $DB->sql_like_escape($context->path) . '/%',
        ]);

        $cache = \cache::make('local_dttutor', 'file_text');
        foreach (array_chunk($hashes, self::BATCH) as $chunk) {
            $cache->delete_many($chunk);
        }
    }

    /**
     * Purge every knowledge block.
     */
    private static function purge_knowledge(): void {
        \cache::make('local_dttutor', 'course_knowledge')->purge();
    }
}
