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
 * Pulse module install steps.
 *
 * @package   pulseaddon_preset
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse pro general plugin install steps.
 *
 * @return bool
 */
function xmldb_pulseaddon_preset_install() {
    global $DB;

    // Enable the plugin by default for new installations.
    set_config('enabled', 1, 'pulseaddon_preset');

    if (method_exists('core_plugin_manager', 'reset_caches')) {
        core_plugin_manager::reset_caches();
    }
    // Inital plugin release - v1.0.

    // Plugin release - v1.1.
    pulseaddon_preset\instance::create_presets();

    require_once(__DIR__ . '/upgrade.php');
    xmldb_pulseaddon_preset_upgrade(0);

    return true;
}
