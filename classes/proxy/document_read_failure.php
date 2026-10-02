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

use local_dttutor\event\service_failed;

/**
 * Why the documents of a course could not be read, told to the administrator.
 *
 * A failed reading never stops the tutor: it answers with the rest of the course. Without a trace,
 * though, a licence that is refused, credits that ran out and a service that is down all look the
 * same from the platform. The reason is recorded with the words the chat already uses for its own
 * failures, so the log report reads the same whichever of the two failed.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_read_failure {
    /** @var string Reason of a failure that did not come from the AI provider. */
    public const UNEXPECTED = 'unexpected';

    /** @var string Error code of the provider when the service answered with an HTTP error. */
    private const HTTP_ERROR = 'httperror';

    /** @var string[] Reason recorded for each error code of the AI provider, by error code. */
    private const REASONS = [
        'license_not_allowed' => 'license_not_allowed',
        'notenoughtokens' => 'tokens_not_sufficient',
        'error_ratelimit_exceeded' => 'rate_limit_exceeded',
        'curlerror' => 'transport',
    ];

    /**
     * Record why the documents of a course could not be read.
     *
     * @param \Throwable $error What the reading failed with.
     * @param int $courseid Course whose documents were being read.
     * @return string The reason recorded.
     */
    public static function record(\Throwable $error, int $courseid): string {
        $reason = self::reason_of($error);
        service_failed::record($reason, service_failed::OPERATION_DOCUMENTS, $courseid);
        return $reason;
    }

    /**
     * The short machine-readable reason of a failure.
     *
     * An error code of the provider without a counterpart in the chat is kept as it is: it is
     * machine-readable already, and renaming it would only hide where it came from.
     *
     * @param \Throwable $error What the reading failed with.
     * @return string
     */
    public static function reason_of(\Throwable $error): string {
        if (!$error instanceof \moodle_exception) {
            return self::UNEXPECTED;
        }

        $errorcode = (string)$error->errorcode;
        if ($errorcode === self::HTTP_ERROR) {
            return 'http_' . (int)$error->a;
        }
        if (isset(self::REASONS[$errorcode])) {
            return self::REASONS[$errorcode];
        }
        if ($errorcode === '') {
            return self::UNEXPECTED;
        }
        return $errorcode;
    }
}
