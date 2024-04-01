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
 * Definition backup-steps
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pulse\automation\action_base;
use mod_pulse\plugininfo\pulseaction;
use mod_pulse\plugininfo\pulsecondition;

/**
 * Define the complete pulse structure for backup, with file and id annotations.
 */
class backup_pulse_activity_structure_step extends backup_activity_structure_step {


    protected function define_course_plugin_structure(&$pulse) {

        $automationinstance = new backup_nested_element('automationinstance');
        $instances = new backup_nested_element('pulse_autoinstances', array('id'), array(
            'templateid', 'courseid', 'status', 'timemodified'
        ));

        // Automation templates.
        $automation = new backup_nested_element('automationtemplates');
        $templates = new backup_nested_element('pulse_autotemplates', array('id'), array(
            'title', 'reference', 'visible', 'notes', 'status', 'tags', 'tenants',
            'categories', 'triggerconditions', 'triggeroperator', 'timemodified',
        ));

        $automationtempinstance = new backup_nested_element('automationtemplateinstance');
        $tempinstances = new backup_nested_element('pulse_autotemplates_ins', array('id'), array(
            'instanceid', 'title', 'insreference', 'notes', 'tags', 'tenants',
            'categories', 'triggerconditions', 'triggeroperator', 'timemodified'
        ));

        $pulseconditionoverrides = new backup_nested_element('pulseconditionoverrides');
        $overrides = new backup_nested_element('pulse_condition_overrides', array('id'), array(
            'instanceid', 'triggercondition', 'status', 'upcomingtime', 'additional', 'isoverridden'
        ));

        // Automation template.
        $pulse->add_child($automation);
        $automation->add_child($templates);

        // Automation instance.
        $pulse->add_child($automationinstance);
        $automationinstance->add_child($instances);

        // Automation template instance.
        $pulse->add_child($automationtempinstance);
        $automationtempinstance->add_child($tempinstances);

        // Condition overrides.
        $pulse->add_child($pulseconditionoverrides);
        $pulseconditionoverrides->add_child($overrides);


        $pulse->set_source_table('pulse_autoinstances', ['courseid' => backup::VAR_COURSEID]);

        // return $instances;
    }

    /**
     * Define backup steps structure.
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated - table fields.
        $pulse = new backup_nested_element('pulse', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'pulse_subject', 'pulse_content',
            'pulse_contentformat', 'pulse', 'diff_pulse', 'displaymode',
            'boxtype', 'boxicon', 'cssclass', 'resend_pulse',
            'completionavailable', 'completionself', 'completionapproval',
            'completionapprovalroles', 'timemodified'));

        $notifiedusers = new backup_nested_element('notifiedusers');

        $pulseusers = new backup_nested_element('pulse_users', array('id'), array(
            'pulseid', 'userid', 'timecreated'));

        $usercompletion = new backup_nested_element('pulsecompletion');

        $pulsecompletion = new backup_nested_element('pulse_completion', array('id'), array(
            'userid', 'pulseid', 'approvalstatus', 'approveduser', 'approvaltime',
            'selfcompletion', 'selfcompletiontime', 'timemodified'));

        // Build the tree.
        $pulse->add_child($notifiedusers);
        $notifiedusers->add_child($pulseusers);

        // User completion.
        $pulse->add_child($usercompletion);
        $usercompletion->add_child($pulsecompletion);

        // Define sources.
        // Define source to backup.
        $pulse->set_source_table('pulse', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $pulseusers->set_source_table('pulse_users', array('pulseid' => backup::VAR_PARENTID));

            $pulsecompletion->set_source_table('pulse_completion', array('pulseid' => backup::VAR_PARENTID));
            $pulsecompletion->annotate_ids('user', 'userid');
        }

        // Define file annotations.
        $pulse->annotate_files('mod_pulse', 'intro', null);
        $pulse->annotate_files('mod_pulse', 'pulse_content', null);

        // $this->define_course_plugin_structure($pluse);

        $pulse = \mod_pulse\extendpro::pulse_extend_backup_steps($pulse, $userinfo);

        // Return the root element (data), wrapped into standard activity structure.
        return $this->prepare_activity_structure($pulse);
    }
}



/**
 * Define the complete pulse structure for backup, with file and id annotations.
 */
class backup_pulse_course_structure_step extends backup_activity_structure_step {


    protected function define_structure() {

        $automationinstance = new backup_nested_element('automationinstance');
        $instances = new backup_nested_element('pulse_autoinstances', array('id'), array(
            'templateid', 'courseid', 'status', 'timemodified'
        ));

        // Automation templates.
        $automation = new backup_nested_element('automationtemplates');
        $templates = new backup_nested_element('pulse_autotemplates', array('id'), array(
            'title', 'reference', 'visible', 'notes', 'status', 'tags', 'tenants',
            'categories', 'triggerconditions', 'triggeroperator', 'timemodified',
        ));

        $automationtempinstance = new backup_nested_element('automationtemplateinstance');
        $tempinstances = new backup_nested_element('pulse_autotemplates_ins', array('id'), array(
            'instanceid', 'title', 'insreference', 'notes', 'tags', 'tenants',
            'categories', 'triggerconditions', 'triggeroperator', 'timemodified'
        ));

        $pulseconditionoverrides = new backup_nested_element('pulseconditionoverrides');
        $overrides = new backup_nested_element('pulse_condition_overrides', array('id'), array(
            'instanceid', 'triggercondition', 'status', 'upcomingtime', 'additional', 'isoverridden'
        ));



        // Automation template.
        $instances->add_child($automation);
        $automation->add_child($templates);

        /*  // Automation instance.
        $pulse->add_child($automationinstance);
        $automationinstance->add_child($instances);
        */

        // Automation template instance.
        $instances->add_child($automationtempinstance);
        $automationtempinstance->add_child($tempinstances);

        // Condition overrides.
        $instances->add_child($pulseconditionoverrides);
        $pulseconditionoverrides->add_child($overrides);

        // Actions and conditions
        // $instances->add_child($actions);
        // $instances->add_child($conditions);

        // Pulse instance.
        $instances->set_source_table('pulse_autoinstances', ['courseid' => backup::VAR_COURSEID]);
        $tempinstances->set_source_table('pulse_autotemplates_ins', ['instanceid' => backup::VAR_PARENTID]);
        $overrides->set_source_table('pulse_condition_overrides', ['instanceid' => backup::VAR_PARENTID]);

        // Pulse autotemplates.
        $templates->set_source_sql('
            SELECT *
            FROM {pulse_autotemplates} at
            WHERE at.id IN (
                SELECT templateid
                FROM {pulse_autoinstances}
                WHERE courseid = :courseid
            )
        ', ['courseid' => backup::VAR_COURSEID]);


        // Include the backup steps for actions and conditions.
        $this->add_subplugin_structure('pulseaction', $instances, true);
        $this->add_subplugin_structure('pulsecondition', $instances, true);

        // Return the root element (data), wrapped into standard activity structure.
        return $this->prepare_activity_structure($instances);
    }
}
