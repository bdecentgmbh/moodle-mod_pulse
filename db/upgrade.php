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
 * Pulse module upgrade steps.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pulse module upgrade steps.
 *
 * @param  mixed $oldversion Previous version.
 * @return void
 */
function xmldb_pulse_upgrade($oldversion) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/pulse/lib.php');
    if (method_exists('core_plugin_manager', 'reset_caches')) {
        core_plugin_manager::reset_caches();
    }
    // Inital plugin release - v1.0.

    $dbman = $DB->get_manager();
    // Plugin release - v1.1.
    if ($oldversion < 2021091700) {
        // Define fields to be added to pulse_presets.
        $table = new xmldb_table('pulse_presets');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'title');
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'description');
        $table->add_field('instruction', XMLDB_TYPE_TEXT, null, null, null, null, null, 'descriptionformat');
        $table->add_field('instructionformat', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'instruction');
        $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'instructionformat');
        $table->add_field('preset_template', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'icon');
        $table->add_field('configparams', XMLDB_TYPE_TEXT, null, null, null, null, null, 'preset_template');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '1', 'configparams');
        $table->add_field('order_no', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'status');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'order_no');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table.
        if (!$dbman->table_exists('pulse_presets')) {
            $dbman->create_table($table);
        }
        \mod_pulse\preset::pulse_create_presets();
        // Pulse savepoint reached.
        upgrade_mod_savepoint(true, 2021091700, 'pulse');
    }

    if ($oldversion < 2021091701) {
        if ($records = $DB->get_records('pulse_presets', [])) {
            foreach ($records as $key => $record) {
                if ($record->configparams != '') {
                    $record->configparams = json_encode(array_keys(json_decode($record->configparams, true)));
                    $DB->update_record('pulse_presets', $record);
                }
            }
        }
        upgrade_mod_savepoint(true, 2021091701, 'pulse');
    }

    if ($oldversion < 2021100800) {

        $pulsetable = new xmldb_table('pulse');
        $displaymode = new xmldb_field('displaymode', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'diff_pulse');
        $boxtype = new xmldb_field('boxtype', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'displaymode');
        $boxicon = new xmldb_field('boxicon', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'boxtype');
        $cssclass = new xmldb_field('cssclass', XMLDB_TYPE_CHAR, '200', null, null, null, null, 'boxicon');

        // Conditionally launch add field privatereplyto.
        if (!$dbman->field_exists($pulsetable, $displaymode)) {
            $dbman->add_field($pulsetable, $displaymode);
        }
        if (!$dbman->field_exists($pulsetable, $boxtype)) {
            $dbman->add_field($pulsetable, $boxtype);
        }
        if (!$dbman->field_exists($pulsetable, $boxicon)) {
            $dbman->add_field($pulsetable, $boxicon);
        }
        if (!$dbman->field_exists($pulsetable, $cssclass)) {
            $dbman->add_field($pulsetable, $cssclass);
        }

        upgrade_mod_savepoint(true, 2021100800, 'pulse');
    }

    if ($oldversion < 2021110200) {
        $pulsetable = new xmldb_table('pulse');
        $boxicon = new xmldb_field('boxicon', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'boxtype');
        if ($dbman->field_exists($pulsetable, $boxicon)) {
            $dbman->change_field_type($pulsetable, $boxicon);
        }

        upgrade_mod_savepoint(true, 2021110200, 'pulse');
    }

    if ($oldversion < 2023051802) {

        // Define table pulse_autoinstances to be created.
        $table = new xmldb_table('pulse_autoinstances');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null, 'templateid');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '9', null, null, null, '1', 'courseid');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'status');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for pulse_autoinstaces.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table pulse_autotemplates to be created.
        $table = new xmldb_table('pulse_autotemplates');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('reference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'title');
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, '1', 'reference');
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'visible');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, '1', 'notes');
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');
        $table->add_field('tenants', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tags');
        $table->add_field('categories', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tenants');
        $table->add_field('triggerconditions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'categories');
        $table->add_field('triggeroperator', XMLDB_TYPE_INTEGER, '9', null, null, null, '1', 'triggerconditions');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'triggeroperator');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for pulse_autotemplates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table pulse_autotemplates_ins to be created.
        $table = new xmldb_table('pulse_autotemplates_ins');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'instanceid');
        $table->add_field('insreference', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'title');
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'insreference');
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null, 'notes');
        $table->add_field('tenants', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tags');
        $table->add_field('categories', XMLDB_TYPE_TEXT, null, null, null, null, null, 'tenants');
        $table->add_field('triggerconditions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'categories');
        $table->add_field('triggeroperator', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'triggerconditions');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'triggeroperator');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique', XMLDB_KEY_UNIQUE, array('instanceid'));

        // Conditionally launch create table for pulse_autotemplates_ins.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table pulse_condition_overrides to be created.
        $table = new xmldb_table('pulse_condition_overrides');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('triggercondition', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null, 'instanceid');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'triggercondition');
        $table->add_field('upcomingtime', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'status');
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');
        $table->add_field('isoverridden', XMLDB_TYPE_INTEGER, '8', null, null, null, '1', 'additional');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique', XMLDB_KEY_UNIQUE, array('instanceid', 'triggercondition'));

        // Conditionally launch create table for pulse_condition_overrides.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pulse savepoint reached.
        upgrade_mod_savepoint(true, 2023051802, 'pulse');

    }

    if ($oldversion < 2023051825) {
        // Auto templates instance.
        $instable = new xmldb_table('pulse_autotemplates_ins');
        $reference = new xmldb_field('reference', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        // Verify field exists.
        if ($dbman->field_exists($instable, $reference)) {
            // Change the field.
            $dbman->rename_field($instable, $reference, 'insreference');
        }

        upgrade_mod_savepoint(true, 2023051825, 'pulse');
    }

    if ($oldversion < 2023051830) {
        // Auto templates instance.
        $instable = new xmldb_table('pulse_autotemplates_ins');
        $timemodified = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        // Verify field exists.
        if ($dbman->field_exists($instable, $timemodified)) {
            // Change the field.
            $dbman->change_field_precision($instable, $timemodified);
        }

        // Update the templates table timemodified.
        $temptable = new xmldb_table('pulse_autotemplates');
        if ($dbman->field_exists($temptable, $timemodified)) {
            // Change the field.
            $dbman->change_field_precision($temptable, $timemodified);
        }

        // Update the pulse_autoinstances table timemodified.
        $autoinstable = new xmldb_table('pulse_autoinstances');
        if ($dbman->field_exists($autoinstable, $timemodified)) {
            // Change the field.
            $dbman->change_field_precision($autoinstable, $timemodified);
        }

        upgrade_mod_savepoint(true, 2023051830, 'pulse');
    }

    if ($oldversion < 2023100701) {
        // Auto templates instance.
        $instable = new xmldb_table('pulse_condition_overrides');
        $reference = new xmldb_field('isoverriden', XMLDB_TYPE_INTEGER, '8', null, null, null, '1');
        // Verify field exists.
        if ($dbman->field_exists($instable, $reference)) {
            // Change the field.
            $dbman->rename_field($instable, $reference, 'isoverridden');
        }

        // Upcoming time.
        $upcomingtime = new xmldb_field('upcomingtime', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'status');
        if (!$dbman->field_exists($instable, $upcomingtime)) {
            $dbman->add_field($instable, $upcomingtime);
        }

        upgrade_mod_savepoint(true, 2023100701, 'pulse');
    }

    return true;
}
