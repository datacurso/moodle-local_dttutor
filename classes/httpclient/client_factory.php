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

namespace local_dttutor\httpclient;

/**
 * Resolves the {@see ai_client} implementation in use.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class client_factory {
    /**
     * Return the bound client, or the production adapter when nothing is bound.
     *
     * An interface has no autowiring, so the container only knows the port when something
     * registered it explicitly (tests do so with \core\di::set(ai_client::class, ...)).
     *
     * @return ai_client
     * @throws \Throwable When the production adapter cannot be built.
     */
    public static function get(): ai_client {
        $container = \core\di::get_container();
        if ($container->has(ai_client::class)) {
            return $container->get(ai_client::class);
        }
        return new datacurso_ai_client();
    }
}
