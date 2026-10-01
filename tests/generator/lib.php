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
 * Data generator for local_dttutor.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_dttutor\course_config;
use local_dttutor\httpclient\client_factory;
use local_dttutor\httpclient\provider_config;
use local_dttutor\session_store;

/**
 * Creates the records the tutor keeps in Moodle, for tests that need a course already set up.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_dttutor_generator extends component_generator_base {
    /**
     * Switch the tutor on or off for a course.
     *
     * @param array $data Keys: courseid, and optionally enabled (defaults to on).
     */
    public function create_course_settings(array $data): void {
        if (empty($data['courseid'])) {
            throw new coding_exception('The course is required to configure the tutor.');
        }
        $enabled = array_key_exists('enabled', $data) ? (int)(bool)$data['enabled'] : 1;
        course_config::update((int)$data['courseid'], ['indexing_enabled' => $enabled]);
    }

    /**
     * Store the reference to a conversation opened in the AI service.
     *
     * @param array $data Keys: userid, courseid, remotesessionid, and optionally cmid.
     */
    public function create_conversation(array $data): void {
        if (empty($data['userid']) || empty($data['courseid'])) {
            throw new coding_exception('The user and the course are required to store a conversation.');
        }
        session_store::upsert(
            (int)$data['userid'],
            (int)$data['courseid'],
            isset($data['cmid']) ? (int)$data['cmid'] : null,
            (string)($data['remotesessionid'] ?? 'behat-session')
        );
    }

    /**
     * Configure the AI provider the tutor depends on, the way the running Moodle release keeps it.
     *
     * Moodle 4.5 switches the provider on as a plugin; Moodle 5.0+ needs an instance of it. The
     * instance is written as the AI subsystem stores it rather than through its API, which refuses
     * a provider that is not installed: the tests of this plugin never reach the provider itself,
     * and the sites that run them do not always have it.
     *
     * @param array $data Keys, all optional: enabled (defaults to on) and licensekey.
     */
    public function create_ai_provider(array $data = []): void {
        global $DB;

        $enabled = !array_key_exists('enabled', $data) || (bool)$data['enabled'];
        $licensekey = (string)($data['licensekey'] ?? 'test-licence-key');

        if (!provider_config::has_instances()) {
            \core\plugininfo\aiprovider::enable_plugin(client_factory::PROVIDER, (int)$enabled);
            set_config('licensekey', $licensekey, provider_config::COMPONENT);
            return;
        }

        $DB->insert_record('ai_providers', [
            'name' => 'Datacurso',
            'provider' => provider_config::PROVIDER_CLASS,
            'enabled' => (int)$enabled,
            'config' => json_encode(['licensekey' => $licensekey]),
            'actionconfig' => json_encode([]),
        ]);
    }

    /**
     * Switch the AI provider off, as an administrator does in the AI administration of Moodle.
     */
    public function disable_ai_provider(): void {
        global $DB;

        if (!provider_config::has_instances()) {
            \core\plugininfo\aiprovider::enable_plugin(client_factory::PROVIDER, 0);
            return;
        }

        $DB->set_field('ai_providers', 'enabled', 0, ['provider' => provider_config::PROVIDER_CLASS]);
    }
}
