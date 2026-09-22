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

namespace local_dttutor;

/**
 * Availability of the interface in every declared language.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class lang_strings_test extends \advanced_testcase {
    /** @var string[] Languages the plugin ships its interface in. */
    private const DECLARED_LANGUAGES = ['en', 'es', 'de', 'fr', 'pt', 'id', 'ru'];

    /**
     * Strings declared by one language pack of the plugin.
     *
     * @param string $lang Language directory name.
     * @return array<string, string>
     */
    private function load_language_pack(string $lang): array {
        global $CFG;
        $string = [];
        include($CFG->dirroot . '/local/dttutor/lang/' . $lang . '/local_dttutor.php');
        return $string;
    }

    /**
     * One language pack per test.
     *
     * @return array<string, array{0: string}>
     */
    public static function language_provider(): array {
        $cases = [];
        foreach (self::DECLARED_LANGUAGES as $lang) {
            $cases[$lang] = [$lang];
        }
        return $cases;
    }

    /**
     * MDL-INT-031: every declared language ships a language pack.
     *
     * @param string $lang
     * @dataProvider language_provider
     */
    public function test_every_declared_language_ships_a_pack(string $lang): void {
        global $CFG;
        $this->assertFileExists($CFG->dirroot . '/local/dttutor/lang/' . $lang . '/local_dttutor.php');
    }

    /**
     * MDL-INT-031: no translation is missing a string used by the interface.
     *
     * @param string $lang
     * @dataProvider language_provider
     */
    public function test_no_translation_is_missing_strings(string $lang): void {
        $reference = $this->load_language_pack('en');
        $translation = $this->load_language_pack($lang);

        $this->assertEmpty(
            array_diff(array_keys($reference), array_keys($translation)),
            'Strings missing from the ' . $lang . ' language pack.'
        );
    }

    /**
     * MDL-INT-031: no translation declares strings the reference language does not know about.
     *
     * @param string $lang
     * @dataProvider language_provider
     */
    public function test_no_translation_declares_unknown_strings(string $lang): void {
        $reference = $this->load_language_pack('en');
        $translation = $this->load_language_pack($lang);

        $this->assertEmpty(
            array_diff(array_keys($translation), array_keys($reference)),
            'Strings declared only in the ' . $lang . ' language pack.'
        );
    }

    /**
     * MDL-INT-031: no translated string is left empty.
     *
     * @param string $lang
     * @dataProvider language_provider
     */
    public function test_no_translated_string_is_empty(string $lang): void {
        foreach ($this->load_language_pack($lang) as $key => $value) {
            $this->assertNotSame('', trim((string)$value), 'Empty string ' . $key . ' in ' . $lang . '.');
        }
    }

    /**
     * MDL-INT-031: each language gets its own strings, whatever the tutor answers in.
     *
     * The language is asked for explicitly instead of through the session: what matters here is
     * that the pack of a language is the one served for that language.
     */
    public function test_every_language_is_served_its_own_strings(): void {
        $this->resetAfterTest();
        $reference = $this->load_language_pack('en');
        $spanish = $this->load_language_pack('es');

        $key = null;
        foreach ($reference as $candidate => $value) {
            if (isset($spanish[$candidate]) && $spanish[$candidate] !== $value) {
                $key = $candidate;
                break;
            }
        }
        $this->assertNotNull($key, 'The Spanish pack is expected to differ from the reference one.');

        $manager = get_string_manager();
        $this->assertEquals($spanish[$key], $manager->get_string($key, 'local_dttutor', null, 'es'));
        $this->assertEquals($reference[$key], $manager->get_string($key, 'local_dttutor', null, 'en'));
    }
}
