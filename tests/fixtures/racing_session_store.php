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

namespace local_dttutor\fixtures;

use local_dttutor\session_store;

/**
 * Session store whose insert is always beaten by a concurrent request for the same key.
 *
 * The competitor row is written directly to the table right before the real insert, so
 * the real insert hits the unique (userid, courseid, cmid) index exactly as it would when
 * two requests open a session for the same user and course at the same time.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class racing_session_store extends session_store {
    /** @var string Remote session id written by the simulated concurrent request. */
    public const COMPETITOR_SESSION_ID = 'remote-first';

    #[\Override]
    protected static function insert(\stdClass $record): void {
        global $DB;

        $competitor = clone $record;
        $competitor->remotesessionid = self::COMPETITOR_SESSION_ID;
        $DB->insert_record(self::TABLE, $competitor);

        parent::insert($record);
    }
}
