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
 * Pulse notification action upgrade steps.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse notification action upgrade steps.
 *
 * @param  mixed $oldversion Previous version.
 * @return void
 */
function xmldb_pulseaction_notification_upgrade($oldversion) {
    global $CFG, $DB;

    // Inital plugin release - v1.0.

    $dbman = $DB->get_manager();

    // Auto templates instance.
    $instable = new xmldb_table('pulseaction_notification');
    $timemodified = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
    // Verify field exists.
    if ($dbman->field_exists($instable, $timemodified)) {
        // Change the field.
        $dbman->change_field_precision($instable, $timemodified);
    }

    // Update the templates table timemodified.
    $temptable = new xmldb_table('pulseaction_notification_ins');
    if ($dbman->field_exists($temptable, $timemodified)) {
        // Change the field.
        $dbman->change_field_precision($temptable, $timemodified);
    }

    return true;
}
