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

use local_dttutor\httpclient\client_factory;

/**
 * What can be said about the state of the tutor and its use in a course.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_status {
    /** @var int Days of use the course report covers. */
    public const USAGE_WINDOW_DAYS = 30;

    /**
     * State of the service behind the tutor.
     *
     * The balance of credits belongs to the licence of the site, so it is asked of the provider and
     * kept for a short while: a teacher opening the page must not wait for a call every time.
     *
     * @return array{available: bool, credits: int|null}
     */
    public static function get(): array {
        if (!client_factory::is_provider_enabled()) {
            return ['available' => false, 'credits' => null];
        }

        return ['available' => true, 'credits' => self::get_credits()];
    }

    /**
     * Credits left on the licence of the site, or null when the provider cannot say.
     *
     * @return int|null
     */
    private static function get_credits(): ?int {
        $cache = \cache::make('local_dttutor', 'service_status');
        $cached = $cache->get('credits');
        if ($cached !== false) {
            return $cached === -1 ? null : (int)$cached;
        }

        $credits = null;
        try {
            if (class_exists('\\aiprovider_datacurso\\local\\service\\credit_token_service')) {
                $balance = \aiprovider_datacurso\local\service\credit_token_service::get_credits_balance();
                if (($balance['status'] ?? '') === 'success') {
                    $credits = (int)($balance['balance'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            \local_dttutor_log('CREDITS_UNAVAILABLE', ['exception' => get_class($e)], true);
        }

        $cache->set('credits', $credits ?? -1);
        return $credits;
    }

    /**
     * Failures of the service the site has recorded in the last day.
     *
     * @return int|null Failures, or null when the site keeps no readable log.
     */
    public static function get_recent_failures(): ?int {
        try {
            $readers = get_log_manager()->get_readers('\\core\\log\\sql_reader');
            $reader = reset($readers);
            if (!$reader) {
                return null;
            }

            return (int)$reader->get_events_select_count(
                'eventname = :eventname AND timecreated >= :since',
                [
                    'eventname' => '\\local_dttutor\\event\\service_failed',
                    'since' => time() - DAYSECS,
                ]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * How much the tutor has been used in a course over the last weeks.
     *
     * Counted from the logs of the platform, which is the only place that knows about courses:
     * the reports of the provider are broken down by user and service, never by course.
     *
     * @param int $courseid
     * @return int|null Questions asked, or null when the site keeps no readable log.
     */
    public static function get_course_usage(int $courseid): ?int {
        try {
            $readers = get_log_manager()->get_readers('\\core\\log\\sql_reader');
            $reader = reset($readers);
            if (!$reader) {
                return null;
            }

            return (int)$reader->get_events_select_count(
                'eventname = :eventname AND courseid = :courseid AND timecreated >= :since',
                [
                    'eventname' => '\\local_dttutor\\event\\tutor_used',
                    'courseid' => $courseid,
                    'since' => time() - (self::USAGE_WINDOW_DAYS * DAYSECS),
                ]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
