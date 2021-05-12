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
 * @category  Backup
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Define the complete pulse structure for backup, with file and id annotations
 */
class backup_pulse_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define backup steps structure.
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated - table fields.
        $pulse = new backup_nested_element('pulse', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'pulse_content',
            'pulse_contentformat', 'pulse', 'diff_pulse', 'resend_pulse', 'completionavailable', 'completionself', 'completionapproval',
            'completionapprovalroles', 'timemodified'));

        $notifiedusers = new backup_nested_element('notifiedusers');

        $pulseusers = new backup_nested_element('pulse_users', array('id'), array(
            'pulse', 'course', 'notified_users', 'timemodified'));

        $usercompletion = new backup_nested_element('pulsecompletion');

        $pulsecompletion = new backup_nested_element('pulse_completion', array('id'), array(
            'userid', 'pulseid', 'approvalstatus', 'approveduser', 'approvaltime', 'selfcompletion', 'selfcompletiontime', 'timemodified'));

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
            $pulseusers->set_source_table('pulse_users', array('pulse' => '../../id'));

            $pulsecompletion->set_source_table('pulse_completion', array('pulseid' => '../../id'));
            $pulsecompletion->annotate_ids('user', 'userid');
        }


        // Define file annotations.
        $pulse->annotate_files('mod_pulse', 'intro', null);
        $pulse->annotate_files('mod_pulse', 'pulse_content', null);

        // Return the root element (data), wrapped into standard activity structure.
        return $this->prepare_activity_structure($pulse);
    }
}