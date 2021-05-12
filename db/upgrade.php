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
 * List of scheduled tasks to send pulses in background.
 *
 * @package   mod_pulse
 * @category  DB
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access !');

function xmldb_pulse_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2021041301.15) {

        $table = new xmldb_table('pulse');
        $resendpulse = new xmldb_field('resend_pulse', XMLDB_TYPE_INTEGER, '2',
        null, null, null, '0', 'diff_pulse');
        if (!$dbman->field_exists($table, $resendpulse)) {
            $dbman->add_field($table, $resendpulse);
        }
        // upgrade_plugin_savepoint(true, 2021041301, 'mod', 'pulse');
    // }

    // if ($oldversion < 2021041301.09) {

        $table = new xmldb_table('pulse');
        $completionavailable = new xmldb_field('completionavailable', XMLDB_TYPE_INTEGER, '2',
        null, null, null, '0', 'diff_pulse');
        if (!$dbman->field_exists($table, $completionavailable)) {
            $dbman->add_field($table, $completionavailable);
        }
        // upgrade_plugin_savepoint(true, 2021041301.09, 'mod', 'pulse');
    // }

    // if ($oldversion < 2021041301.10) {

        $table = new xmldb_table('pulse');
        $completionapproval = new xmldb_field('completionapproval', XMLDB_TYPE_INTEGER, '2',
        null, null, null, '0', 'completionavailable');
        if (!$dbman->field_exists($table, $completionapproval)) {
            $dbman->add_field($table, $completionapproval);
        }
        // Completion aprroval roles.
        $completionapprovalroles = new xmldb_field('completionapprovalroles', XMLDB_TYPE_TEXT, '',
        null, null, null, null, 'completionapproval');
        if (!$dbman->field_exists($table, $completionapprovalroles)) {
            $dbman->add_field($table, $completionapprovalroles);
        }
        // upgrade_plugin_savepoint(true, 2021041301.10, 'mod', 'pulse');
    // }

    
        // Define table pulse_completion to be created.
        $table = new xmldb_table('pulse_completion');

        // Adding fields to table pulse_completion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pulseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('approvalstatus', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('approveduser', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('selfcompletion', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table pulse_completion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for pulse_completion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // upgrade_plugin_savepoint(true, 2021041301.16, 'mod', 'pulse');
    // }

    // if ($oldversion < 2021041301.17) {

        $table = new xmldb_table('pulse');
        $completionapprovalroles = new xmldb_field('completionself', XMLDB_TYPE_TEXT, '',
        null, null, null, null, 'completionavailable');
        if (!$dbman->field_exists($table, $completionapprovalroles)) {
            $dbman->add_field($table, $completionapprovalroles);
        }
    // }

    // if ($oldversion < 2021041301.18) {

        // Define field id to be added to pulse_completion.
        $table = new xmldb_table('pulse_completion');
        $fieldtime = new xmldb_field('approvaltime', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'approveduser');
        $selftime = new xmldb_field('selfcompletiontime', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'selfcompletion');
        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $fieldtime)) {
            $dbman->add_field($table, $fieldtime);
        }
        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $selftime)) {
            $dbman->add_field($table, $selftime);
        }
    }

    return true;
}