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
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse module instance generator.
 */
class mod_pulse_generator extends testing_module_generator {

    /**
     * Create pulse module instance.
     *
     * @param  mixed $record Module instance data.
     * @param  array $options Additional options.
     * @return void
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object) $record;
        $record->showdescription = 1;
        $record->pulse = 1;
        if (!isset($record->diff_pulse)) {
            $record->diff_pulse = 0;
        }
        return parent::create_instance($record, $options);
    }
}
