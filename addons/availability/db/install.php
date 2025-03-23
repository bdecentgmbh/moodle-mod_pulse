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
 * Install script for Pulse Reactions
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    pulseaddon_availability
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Installation script for the pulseaddon_availability plugin.
 *
 * @return bool Returns true on successful installation.
 */
function xmldb_pulseaddon_availability_install() {
    global $DB;

    // Enable the plugin by default for new installations.
    set_config('enabled', 1, 'pulseaddon_availability');

    // Check if we need to migrate from local_pulsepro.
    if ($DB->get_manager()->table_exists('local_pulsepro_availability')) {
        require_once(__DIR__ . '/upgrade.php');
        xmldb_pulseaddon_availability_upgrade(0);
    }

    return true;
}
