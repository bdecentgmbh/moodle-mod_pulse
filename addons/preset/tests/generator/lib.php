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
 * Pulse instance test instance generate defined.
 *
 * @package   pulseaddon_preset
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse module instance generator.
 */
class pulseaddon_preset_generator extends testing_module_generator {
    /**
     * Module intro content.
     *
     * @var string
     */
    public $intro = 'Pulse test notification';

    /**
     * Insert defined demo presets for test.
     *
     * @return void
     */
    public function create_presets(): void {
        \pulseaddon_preset\instance::create_presets();
    }
}
