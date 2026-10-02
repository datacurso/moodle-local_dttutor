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
 * Test double for the exceptions the AI provider throws.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\fixtures;

/**
 * An exception shaped like the ones aiprovider_datacurso throws, without needing it installed.
 *
 * A real \moodle_exception looks its message up in the language strings of its component, and
 * the provider may be absent from the site the tests run on. Only the error code and its
 * argument matter to whoever reads it.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_exception extends \moodle_exception {
    /**
     * Build the exception without looking its message up.
     *
     * @param string $errorcode Error code of the provider.
     * @param mixed $a Argument of the error code, such as the HTTP status of an HTTP error.
     */
    public function __construct(string $errorcode, $a = null) {
        $this->errorcode = $errorcode;
        $this->module = 'aiprovider_datacurso';
        $this->a = $a;
        $this->link = '';
        $this->debuginfo = null;
        \Exception::__construct($errorcode);
    }
}
