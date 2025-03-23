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
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

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

    if ($oldversion < 2024033012) {
        // Mark as complete options.
        $pulsetable = new xmldb_table('pulse');
        $completionbtnconfirmation = new xmldb_field(
            'completionbtnconfirmation', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'completionapprovalroles');

        $completionbtntext = new xmldb_field(
            'completionbtntext', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'completionbtnconfirmation');

        $completionbtncontent = new xmldb_field(
            'completionbtn_content', XMLDB_TYPE_TEXT, null, null, null, null, null, 'completionbtntext');

        $completionbtncontentformat = new xmldb_field(
            'completionbtn_contentformat', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'completionbtn_content');

        // Completion button confiramtion option.
        if (!$dbman->field_exists($pulsetable, $completionbtnconfirmation)) {
            $dbman->add_field($pulsetable, $completionbtnconfirmation);
        }
        // Completion button text option.
        if (!$dbman->field_exists($pulsetable, $completionbtntext)) {
            $dbman->add_field($pulsetable, $completionbtntext);
        }
        // Completion button content option.
        if (!$dbman->field_exists($pulsetable, $completionbtncontent)) {
            $dbman->add_field($pulsetable, $completionbtncontent);
        }
        // Completion button content format option.
        if (!$dbman->field_exists($pulsetable, $completionbtncontentformat)) {
            $dbman->add_field($pulsetable, $completionbtncontentformat);
        }
        upgrade_mod_savepoint(true, 2024033012, 'pulse');
    }

    if ($oldversion < 2024122701) {

        // Define table pulse_options to be created.
        $table = new xmldb_table('pulse_options');

        // Adding fields to table pulse_options.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pulseid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table pulse_options.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('pulseid', XMLDB_KEY_UNIQUE, ['pulseid', 'name']);

        // Conditionally launch create table for pulse_options.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pulse savepoint reached.
        upgrade_mod_savepoint(true, 2024122701, 'pulse');
    }

    return true;
}
