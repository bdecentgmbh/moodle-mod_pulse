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

namespace pulseaddon_preset;

/**
 * Class instance
 *
 * @package    pulseaddon_preset
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance extends \mod_pulse\addon\base {
    /**
     * Get the name of the addon.
     *
     * @return string Name of the addon
     */
    public function get_name() {
        return 'Pulse Preset';
    }

    /**
     * Triggered after the pulse created using presets save method.
     * It helps to updated the user custom data to pulse pro table.
     *
     * @param array $configparams User provided custom module config data
     * @return void
     */
    public function instance_pulse_preset_update($configparams) {

        if (class_exists('\pulseaddon_preset\presets\preset_form')) {
            \pulseaddon_preset\presets\preset_form::update_preset_config_params($this->pulseid, $configparams);
        }
    }

    /**
     * File manager options for preset activity backup file.
     *
     * @return array
     */
    public static function preset_fileoptions(): array {
        return [
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => ['.mbz'],
            'return_types'   => FILE_INTERNAL | FILE_EXTERNAL,
        ];
    }

    /**
     * Returns list of fileareas used in the pulseaddon reminder contents.
     *
     * @return array list of filearea to support pluginfile.
     */
    public static function pluginfile_fileareas(): array {
        return ['preset_template', 'description', 'instruction'];
    }

    /**
     * Extend the pulse module apply preset method to format the data.
     *
     * @param string $method Name of method to trigger..
     * @param array $backupdata Preset restore data.
     * @return array $backupdata Updated preset template restore data.
     */
    public static function preset_formatdata(string $method, $backupdata) {
        if ($method == 'cleandata' && class_exists('pulseaddon_preset\presets\preset_form')) {
            return \pulseaddon_preset\presets\preset_form::formatdata($backupdata);
        }
        return $backupdata;
    }

    /**
     * Create a demo presets during the plugin installation.
     *
     * @return void
     */
    public static function create_presets() {
        global $CFG;

        $presets = [];
        if (file_exists($CFG->dirroot . '/mod/pulse/addons/preset/assets/presets.xml')) {
            $presetsxml = simplexml_load_file($CFG->dirroot . '/mod/pulse/addons/preset/assets/presets.xml');
            $result = json_decode(json_encode($presetsxml), true);
            $presets = (!empty($result)) ? $result : [];
        }

        if (!empty($presets)) {
            return \mod_pulse\preset::pulse_create_presets($presets, true);
        }

        return [];
    }
}
