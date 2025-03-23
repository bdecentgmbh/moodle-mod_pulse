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
 * Upgrade steps for Pulse Addon Availability
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    pulseaddon_availability
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
function xmldb_pulseaddon_availability_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024122604) {
        // Copy data from local_pulsepro_availability to pulseaddon_availability.
        if ($dbman->table_exists('local_pulsepro_availability')) {
            $sql = "INSERT INTO {pulseaddon_availability}
                    (pulseid, userid, status, availabletime)
                    SELECT lpa.pulseid, lpa.userid, lpa.status, lpa.availabletime
                    FROM {local_pulsepro_availability} lpa
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM {pulseaddon_availability} pa
                        WHERE pa.pulseid = lpa.pulseid
                        AND pa.userid = lpa.userid
                    )";

            $DB->execute($sql);
        }

        if ($oldversion > 0) {
            upgrade_plugin_savepoint(true, 2024122604, 'pulseaddon', 'availability');
        }
    }

    if ($oldversion < 2024122606) {
        // Define field availabletime to be changed.
        $table = new xmldb_table('pulseaddon_availability');
        $field = new xmldb_field('availabletime', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'status');

        // Conditionally launch change field type and length.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        if ($oldversion > 0) {
            upgrade_plugin_savepoint(true, 2024122606, 'pulseaddon', 'availability');
        }
    }

    return true;
}
