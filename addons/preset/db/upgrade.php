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
 * Upgrade steps for Pulse presets
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    pulseaddon_preset
 * @category   upgrade
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_pulseaddon_preset_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024122604) {

        // Migrate credits fields from configurable params to options[credits].
        $table = new xmldb_table('pulse_presets');
        if ($dbman->table_exists($table)) {
            $records = $DB->get_records('pulse_presets');
            foreach ($records as $record) {
                $params = json_decode($record->configparams, true);
                foreach ($params as $key => $param) {
                    if ($param == 'reactiondisplay') {
                        $params[$key] = 'options[reactiondisplay]';
                    }
                    if ($param == 'reactiontype') {
                        $params[$key] = 'options[reactiontype]';
                    }
                    if ($param == 'credits') {
                        $params[$key] = 'options[credits]';
                    }
                    if ($param == 'credits_status') {
                        $params[$key] = 'options[credits_status]';
                    }
                }
                $record->configparams = json_encode($params);
                $DB->update_record('pulse_presets', $record);
            }
        }
        if ($oldversion > 0) {
            upgrade_plugin_savepoint(true, 2024122604, 'pulseaddon', 'preset');
        }
    }

    return true;
}
