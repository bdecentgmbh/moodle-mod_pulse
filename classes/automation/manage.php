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
 * Notification pulse action - Automation helper.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\automation;

use core_reportbuilder\external\reports\retrieve;
use html_writer;
use moodle_exception;
use stdClass;

class manage {
    
    /**
     * Template ID
     * 
     * @param int
     */
    public $templateid;

    /**
     * Course ID
     * 
     * @param int
     */
    public $courseid;

    /**
     * Get the template id and course id.
     * 
     * @param int $templateid Template ID
     * @param int $courseid Course ID
     */
    public function __construct($templateid, $courseid) {
        global $DB;
        $this->templateid = $templateid;
        $this->courseid = $courseid;
    }

    /**
     * Create an instance of the template.
     *
     * @param int $templateid The ID of the template.
     * @return self The instance of the template.
     */
    public static function create($templateid, $courseid) {
        // TODO: template exist checks goes here.
        return new self($templateid, $courseid);
    }

    /**
     * Delete all the auto template instance in the database table.
     * 
     * @return bool
     */
    public function delete_auto_instances() {
        global $DB;

        $instances = $this->get_course_instances_record();
        if (!empty($instances)) {     
            foreach ($instances as $instanceid => $instance) {
                instances::create($instanceid)->delete_instance();
            }
            return true;
        }
        return false;
    }

    /**
     * Delete the all instance in this course use the templateid.
     * 
     * @return bool True if the deletion is successful, false otherwise
     */
    public function delete_course_instance() {
        
        if ($this->delete_auto_instances()) {
            return true;
        }
        return false;
    }

    /**
     * Get the template record data.
     */
    public function get_template_formdata() {
        global $DB;
        if ($template = $DB->get_record('pulse_autotemplates', ['id' => $this->templateid], '*', MUST_EXIST)) {
            return $template;
        }
    }

    /**
     * Retrieves instances associated with this template.
     *
     * @return array An array of template instances or an empty array if none are found.
     */
    public function get_course_instances_record() {
        global $DB;

        if ($instances = $DB->get_records('pulse_autoinstances', ['templateid' => $this->templateid, 'courseid' => $this->courseid]) ) {
            return $instances;
        }

        return [];
    }

    /** 
     * Added the automation instance for this course.
     */
    public function add_course_instance() {
        global $DB;
        
        $transaction = $DB->start_delegated_transaction();

        // Fetch the related template data.
        $templatedata = $this->get_template_formdata(); // Fetch the template data from DB.

        // Instance data to store in autoinstance table.
        $instancedata = (object) [
            'templateid' => $templatedata->id,
            'courseid' => $this->courseid,
            'status' => $templatedata->status,
            'timemodified' => time(),
        ];

        if ($instanceid = $DB->insert_record('pulse_autoinstances', $instancedata)) {

            $course = get_course($this->courseid);

            // Default override elements and values for the instance management.
            $overriddenelements = [
                'insreference' => shorten_text(strip_tags($course->shortname), 30),
                'pulsenotification_suppress' => [],
                'pulsenotification_suppressoperator' => 2,
                'pulsenotification_dynamiccontent' => 0,
                'pulsenotification_chapterid' => '',
            ];
            $templatefields = $DB->get_columns('pulse_autotemplates_ins');
            $fields = array_keys($templatefields);
            $preventfields = ['id', 'triggerconditions', 'timemodified'];
            
            // Clear unused fields from list.
            $fields = array_diff_key(array_flip($fields), array_flip($preventfields));

            $templatedata = array_intersect_key((array) $overriddenelements, $fields);
            
            // Convert the elements array into json.
            array_walk($templatedata, function(&$value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
            });

            $tablename = 'pulse_autotemplates_ins'; // Template instance tablename to update.
            // Update the instance overridden data related to template.
            $templatedata['timemodified'] = time();
            \mod_pulse\automation\templates::update_instance_data($instanceid, $templatedata);

            // ...Send the data to action plugins for perform the data store.
            $context = \context_course::instance($this->courseid);

            // Find list of actions.
            $actionplugins = \mod_pulse\plugininfo\pulseaction::get_list();

            // Added the item id for file editors.
            $overriddenelements['instanceid'] = $instanceid;
            $overriddenelements['courseid'] = $this->courseid;
            $overriddenelements['templateid'] = $this->templateid;

            foreach ($actionplugins as $component => $pluginbase) {
                $pluginbase->postupdate_editor_fileareas($overriddenelements, $context);
                $pluginbase->process_instance_save($instanceid, $overriddenelements);
            }

        }

        // Allow to update the DB changes to Database.
        $transaction->allow_commit();

        return true;
        
    }

    /**
     * Updates the "visible" field of the current menu and deletes it from the cache.
     *
     * @param bool $status The new value for the "status" field.
     * @param bool $instance
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_instance_status(bool $status, bool $instance=false) {
        global $DB;

       $instances = $this->get_course_instances_record();
        foreach ($instances as $instanceid => $instance) {
            instances::create($instanceid)->update_status($status);
        }
        return true;
    }

    
}