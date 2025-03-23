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
     * @param  array $defaultoptions Default options.
     * @return void
     */
    public function create_instance($record = null, array $defaultoptions = []) {
        global $CFG;

        $record = (object) $record;
        $record->showdescription = 1;
        $record->pulse = 1;

        if (!isset($record->diff_pulse)) {
            $record->diff_pulse = 0;
        }
        if (!isset($record->completionbtn_content_editor)) {
            $record->completionbtn_content_editor = ['text' => '', 'format' => FORMAT_HTML];
        }

        $plugins = mod_pulse\plugininfo\pulseaddon::get_enabled_addons();
        foreach ($plugins as $plugin => $version) {
            if (!file_exists($CFG->dirroot . '/mod/pulse/addons/' . $plugin . '/tests/generator/lib.php')) {
                continue;
            }
            require_once($CFG->dirroot . '/mod/pulse/addons/' . $plugin . '/tests/generator/lib.php');
            $classname = 'pulseaddon_' . $plugin . '_generator';
            if (class_exists($classname) && method_exists($classname, 'default_value')) {
                $options = $classname::default_value();
                $record = (object) array_merge((array) $record, $options);
            }
        }

        $record = (object) array_merge((array) $record, $defaultoptions);
        return parent::create_instance($record, $defaultoptions);
    }
}
