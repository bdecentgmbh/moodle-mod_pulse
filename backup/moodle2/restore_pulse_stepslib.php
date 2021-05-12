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
 * Definition restore structure steps.
 *
 * @package   mod_pulse
 * @category  Backup
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access!');

/**
 * Define all the restore steps that will be used by the restore_pulse_activity_task
 */

/**
 * Structure step to restore pulse activity.
 */
class restore_pulse_activity_structure_step extends restore_activity_structure_step {

    /**
     * Restore steps structure definition.
     */
    protected function define_structure() {
        $paths = array();
        // Restore path.
        $paths[] = new restore_path_element('pulse', '/activity/pulse');
        $paths[] = new restore_path_element('pulse_users', '/activity/pulse/notifiedusers/pulse_users');
        $paths[] = new restore_path_element('pulse_completion', '/activity/pulse/usercompletion/pulse_completion');


        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process activity pulse restore.
     */
    protected function process_pulse($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        // Insert instance into Database.
        $newitemid = $DB->insert_record('pulse', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process pulse users records.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_pulse_users($data) {
        global $DB;

        $data = (object) $data;

        $data->pulse = $this->get_new_parentid('pulse');
        $data->course = $this->get_courseid();

        $DB->insert_record('pulse_users', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }


    /**
     * Process pulse users records.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_pulse_completion($data) {
        global $DB;

        $data = (object) $data;

        $data->pulseid = $this->get_new_parentid('pulse');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('pulse_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function after_execute() {
        // Add pulse related files.
        $this->add_related_files('mod_pulse', 'intro', null);
        $this->add_related_files('mod_pulse', 'pulse_content', null);
    }
}