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
 * Notification pulse action - Automation conditions base.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\automation;

/**
 * Automation conditions base.
 */
abstract class condition_base {

    /**
     * Repersents the conditions operator is any.
     * @var int
     */
    const OPERATOR_ANY = 1;

    /**
     * Repersents the conditions operator is all.
     * @var int
     */
    const OPERATOR_ALL = 2;

    /**
     * Repersents the condition status is disabled.
     * @var int
     */
    const DISABLED = 0; // Option.

    /**
     * Repersents the condition status is all.
     * @var int
     */
    const ALL = 1;

    /**
     * Repersents the condition status is future.
     * @var int
     */
    const FUTURE = 2;

    /**
     * The name of this action plugin.
     *
     * @var string
     */
    protected $component;

    /**
     * Includes an condition based to the template condition options.
     *
     * @param mixed $option The action option.
     */
    abstract public function include_condition(&$option);

    /**
     * Loads the condition form for an instance.
     *
     * @param moodleform $mform The form to be loaded.
     * @param stdClass $forminstance The instance object.
     */
    abstract public function load_instance_form(&$mform, $forminstance);

    /**
     * Sets the name of the conditions component.
     *
     * @param string $componentname The component name.
     */
    public function set_component($componentname) {
        $this->component = $componentname;
    }

    /**
     * Adds an upcoming element to the form.
     *
     * @param moodleform $mform The form object.
     */
    public function upcoming_element(&$mform) {

        $mform->addElement('hidden', 'condition['.$this->component.'][upcomingtime]');
        $mform->setType('condition['.$this->component.'][upcomingtime]', PARAM_INT);
    }

    /**
     * Gets available options.
     *
     * @return array List of options.
     */
    public function get_options() {
        return [
            self::DISABLED => get_string('disable'),
            self::ALL => get_string('all'),
            self::FUTURE => get_string('upcoming', 'pulse'),
        ];
    }

    /**
     * Triggers an actions associated to the instance.
     *
     * @param int $instanceid The instance ID.
     * @param int $userid The user ID.
     * @param mixed $expectedtime The expected time for triggering.
     * @param bool $newuser Is the trigger instance for new user.
     */
    public function trigger_instance(int $instanceid, int $userid, $expectedtime=null, $newuser=false) {

        \mod_pulse\automation\instances::create($instanceid)->trigger_action($userid, $expectedtime, $newuser);
    }

    /**
     * Checks if a user has completed the conditions.
     *
     * @param mixed $notification The notification object.
     * @param int $userid The user ID.
     *
     * @return bool True if user has completed, otherwise false.
     */
    public function is_user_completed($notification, int $userid) {
        return true;
    }

    /**
     * Status of the condition addon works based on the user enrolment.
     *
     * @return bool
     */
    public function is_user_enrolment_based() {
        return true;
    }

    /**
     * Processes the saving of an instance.
     *
     * @param int $instanceid The instance ID.
     * @param array $data The data to be saved.
     *
     * @return bool True if the instance was saved successfully, otherwise false.
     */
    public function process_instance_save($instanceid, $data) {
        global $DB;

        // Remove empty values from data array.
        $filter = array_filter($data);
        // If 'status' is not set and there are no other non-empty values, stopped here.
        if (!isset($data['status']) && empty($filter)) {
            return true;
        }

        // Get the 'status' or set it to an empty string if not present.
        $status = $data['status'] ?? '';

        // Future enrolment is disabled then make the upcoming time to null.
        if ($status != self::FUTURE) {
            $data['upcomingtime'] = '';
        }
        // Future enrolments are enabled and its upcoming is empty set current time as upcoming.
        // Conditions will affect for coming enrolments after this time.
        if ($status == self::FUTURE && $data['upcomingtime'] == 0) {
            $data['upcomingtime'] = time();
        }
        // Prepare the record to be inserted or updated.
        $record = [
            'instanceid' => $instanceid,
            'triggercondition' => $this->component,
            'status' => $data['status'] ?? null,
            'upcomingtime' => $data['upcomingtime'] ?? null,
            'isoverridden' => (isset($data['status'])) ? true : false,
        ];
        // Remove 'status' from data array.
        unset($data['status']);
        // Encode additional data as JSON.
        $record['additional'] = json_encode($data);

        if ($this->component == 'events' && array_key_exists('event', $data)) {
            $eventrecord = [
                'instanceid' => $instanceid,
                'eventname' => stripslashes($data['event']),
                'notifyuser' => $data['notifyuser'],
            ];
            if ($event = $DB->get_record('pulsecondition_events', ['instanceid' => $instanceid])) {
                $eventrecord['id'] = $event->id;
                // Update the record.
                $DB->update_record('pulsecondition_events', $eventrecord);
            } else {
                // Insert the record.
                $DB->insert_record('pulsecondition_events', $eventrecord);
            }
        }

        // Check if a record already exists for this instance and trigger condition.
        if ($condition = $DB->get_record('pulse_condition_overrides',
            ['instanceid' => $instanceid, 'triggercondition' => $this->component])) {
            $record['id'] = $condition->id;
            // Update the record.
            $DB->update_record('pulse_condition_overrides', $record);
        } else {
            // Insert the record.
            $DB->insert_record('pulse_condition_overrides', $record);
        }

        return true;
    }

    /**
     * Includes data for an instance.
     *
     * @param object $instance The instance object.
     * @param object $data The data object.
     * @param bool $prefix Whether to add a prefix to keys or not.
     *
     * @return void
     */
    public function include_data_forinstance(&$instance, $data, $prefix=true) {
        // Decode additional data.
        $additional = $data->additional ? json_decode($data->additional, true) : [];

        // Add overrides to instance.
        foreach ($additional as $key => $value) {
            // NEEDTOOVERVIEW: For the wrong count of overrides in the list, removed this additional.
            $instance->override["condition_".$this->component."_".$key] = 1;
        }

        // If data is overridden, set status override.
        if ($data->isoverridden) {
            $instance->condition[$this->component]['status'] = $data->status;
            $instance->override["condition_".$this->component."_status"] = 1;
            $count = isset($instance->overridecount) ? $instance->overridecount + 1 : 1;
            $instance->overridecount = $count;
        }
        // If component condition is set, merge additional data and set 'upcomingtime'.
        if (isset($instance->condition[$this->component])
            && $instance->condition[$this->component] != null && is_array($additional)) {
            $instance->condition[$this->component] = array_merge($instance->condition[$this->component], $additional);
            $instance->condition[$this->component]['upcomingtime'] = $data->upcomingtime ?: 0;
        }
    }

    /**
     * List of placeholders rendered form the events.
     *
     * @return array
     */
    public function get_email_placeholders() {
        return [];
    }

    /**
     * Include the conditions var values for the placeholders.
     *
     * @param int $userid
     * @param stdClass $instancedata
     * @param stdClass $schedulerecord
     * @return array
     */
    public function update_email_customvars($userid, $instancedata, $schedulerecord) {
        return [];
    }

    /**
     * Include the conditions join query with the sql used to fetch the schedule record.
     *
     * @return string
     */
    public function schedule_override_join() {
        return '';
    }

    /**
     * Define the stutures of conditions for the backup.
     *
     * @param object $instances
     * @return void
     */
    public function backup_define_structure(&$instances) {
    }

    /**
     * Delete the records of condition for the custom instance.
     *
     * @param int $instanceid
     * @return void
     */
    public function delete_condition_instance(int $instanceid) {
        return false;
    }
}
