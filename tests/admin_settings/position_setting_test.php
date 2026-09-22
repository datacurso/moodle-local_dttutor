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

namespace local_dttutor\admin_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/dttutor/classes/admin_settings/admin_setting_position_preview.php');

/**
 * Saving the position of the floating button and the side the panel opens from.
 *
 * See MDL-UNIT-004 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\admin_settings\admin_setting_position_preview
 */
final class position_setting_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The setting under test.
     *
     * @return admin_setting_position_preview
     */
    private function setting(): admin_setting_position_preview {
        return new admin_setting_position_preview(
            'local_dttutor/avatar_position_data',
            'Avatar position',
            'Where the floating button sits',
            '{"preset":"right","x":"2rem","y":"6rem","drawerside":"right","xref":"right","yref":"bottom"}'
        );
    }

    /**
     * A complete position, ready to be tweaked by each test.
     *
     * @param array $overrides Keys to replace.
     * @return string The encoded position.
     */
    private function position(array $overrides = []): string {
        return json_encode($overrides + [
            'preset' => 'custom',
            'x' => '10px',
            'y' => '20px',
            'drawerside' => 'right',
            'xref' => 'left',
            'yref' => 'bottom',
        ]);
    }

    /**
     * MDL-UNIT-004: a complete and coherent position is accepted and stored.
     */
    public function test_a_complete_position_is_stored(): void {
        $this->assertSame('', $this->setting()->write_setting($this->position()));
        $this->assertSame($this->position(), get_config('local_dttutor', 'avatar_position_data'));
    }

    /**
     * CSS units the coordinates must accept.
     *
     * @return array<string, array{0: string}>
     */
    public static function valid_unit_provider(): array {
        return [
            'pixels' => ['15px'],
            'root em' => ['2rem'],
            'em' => ['1.5em'],
            'percentage' => ['50%'],
            'viewport height' => ['10vh'],
            'viewport width' => ['10vw'],
            'negative value' => ['-3rem'],
        ];
    }

    /**
     * MDL-UNIT-004: every admitted CSS unit is accepted for the coordinates.
     *
     * @param string $value
     * @dataProvider valid_unit_provider
     */
    public function test_admitted_css_units_are_accepted(string $value): void {
        $this->assertSame('', $this->setting()->write_setting($this->position(['x' => $value, 'y' => $value])));
    }

    /**
     * Coordinate values that must be turned down.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_coordinate_provider(): array {
        return [
            'no unit' => ['10'],
            'unknown unit' => ['10pt'],
            'a space before the unit' => ['10 px'],
            'plain text' => ['left'],
            'empty' => [''],
            'an expression' => ['calc(100% - 2rem)'],
        ];
    }

    /**
     * MDL-UNIT-004: a coordinate that is not a valid CSS value is turned down.
     *
     * @param string $value
     * @dataProvider invalid_coordinate_provider
     */
    public function test_a_coordinate_that_is_not_valid_css_is_turned_down(string $value): void {
        $error = $this->setting()->write_setting($this->position(['x' => $value]));

        $this->assertSame(get_string('error_invalid_coordinates', 'local_dttutor'), $error);
        $this->assertFalse(get_config('local_dttutor', 'avatar_position_data'));
    }

    /**
     * MDL-UNIT-004: the corner presets do not depend on the coordinates being written.
     */
    public function test_a_corner_preset_does_not_depend_on_the_coordinates(): void {
        $error = $this->setting()->write_setting($this->position(['preset' => 'right', 'x' => 'nonsense']));

        $this->assertSame('', $error);
    }

    /**
     * Positions that are incomplete or name options that do not exist.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_position_provider(): array {
        return [
            'not readable at all' => ['this is not a position'],
            'missing the preset' => ['{"x":"1px","y":"1px","drawerside":"right","xref":"left","yref":"bottom"}'],
            'missing the drawer side' => ['{"preset":"right","x":"1px","y":"1px","xref":"left","yref":"bottom"}'],
            'missing the reference edges' => ['{"preset":"right","x":"1px","y":"1px","drawerside":"right"}'],
            'unknown preset' => ['{"preset":"middle","x":"1px","y":"1px","drawerside":"right","xref":"left","yref":"bottom"}'],
            'unknown drawer side' => ['{"preset":"right","x":"1px","y":"1px","drawerside":"up","xref":"left","yref":"bottom"}'],
            'unknown horizontal edge' => [
                '{"preset":"right","x":"1px","y":"1px","drawerside":"right","xref":"middle","yref":"bottom"}',
            ],
            'unknown vertical edge' => [
                '{"preset":"right","x":"1px","y":"1px","drawerside":"right","xref":"left","yref":"middle"}',
            ],
        ];
    }

    /**
     * MDL-UNIT-004: an incomplete or incoherent position is turned down and nothing is stored.
     *
     * @param string $data
     * @dataProvider invalid_position_provider
     */
    public function test_an_incomplete_or_incoherent_position_is_turned_down(string $data): void {
        $error = $this->setting()->write_setting($data);

        $this->assertSame(get_string('error_invalid_position', 'local_dttutor'), $error);
        $this->assertFalse(get_config('local_dttutor', 'avatar_position_data'));
    }

    /**
     * MDL-UNIT-004: a rejected save leaves the position already in use untouched.
     */
    public function test_a_rejected_save_leaves_the_previous_position_untouched(): void {
        $inuse = $this->position(['x' => '4rem']);
        $this->setting()->write_setting($inuse);

        $this->setting()->write_setting($this->position(['x' => '4 rem']));

        $this->assertSame($inuse, get_config('local_dttutor', 'avatar_position_data'));
    }
}
