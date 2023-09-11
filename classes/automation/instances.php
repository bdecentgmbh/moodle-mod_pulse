<?php

namespace mod_pulse\automation;

use availability_completion\condition;
use \mod_pulse\automation\action_base;
use moodle_url;
use core_reportbuilder\local\helpers\report as reporthelper;
use course_enrolment_manager;
use moodle_exception;

class instances extends templates {

    protected $instanceid;

    protected $instance;

    protected $actions;

    public function __construct($instanceid) {
        // TODO Check istance exists. throw exception if not available.
        $this->instanceid = $instanceid;
        // $this->instance = $this->get_instance_data();
        $this->set_instance_actions();
    }

    public static function create($instanceid) {
        $instance = new self($instanceid);
        return $instance;
    }

    public function get_instance_data() {
        global $DB;

        // Fetch the instance record to find the template id.
        $instance = $this->get_instance_record();

        if (empty($instance)) {
            throw new moodle_exception('instancedatanotgenerated', 'pulse');
        }

        $instance->template = \mod_pulse\automation\templates::create($instance->templateid)->get_data_forinstance($instance);

        $instance->actions = $this->include_actions_data($instance, false);
        $this->include_conditions_data($instance);

        $this->instance = $instance;

        $this->instance->course = get_course($instance->courseid);

        return $this->instance;
    }


    protected function get_instance_record() {
        global $DB;

        return $DB->get_record('pulse_autoinstances', ['id' => $this->instanceid]);
    }

    /**
     * Get the instance formdata.
     *
     * @return array
     */
    public function get_instance_formdata() {

        // Fetch the instance record to find the template id.
        $instance = $this->get_instance_record();

        if (empty($instance)) {
            throw new moodle_exception('instancedatanotgenerated', 'pulse');
        }

        // Fetch record of template instance.
        \mod_pulse\automation\templates::create($instance->templateid)->get_data_forinstance($instance);

        $this->include_actions_data($instance);

        $this->include_conditions_data($instance);

        /* print_object($instance);
        exit; */
        return ((array) $instance);
    }

    public function include_conditions_data(&$instance) {
        global $DB;

        $overrides = $DB->get_records('pulse_condition_overrides', ['instanceid' => $instance->id]);
        $conditions = \mod_pulse\plugininfo\pulsecondition::get_list();

        // Define empty list of conditions.
        if (!isset($instance->condition)) {
            $instance->condition = [];
        }

        // Include template conditions.

        $triggerconditions = $instance->template->triggerconditions ?? $instance->triggerconditions;
        array_map(function($value) use ($instance, $conditions) {
            $instance->condition[$value] = ['status' => 1];
        }, $triggerconditions);

        // Override the instance conditions to template conditions.
        foreach ($overrides as $condition) {
            if (isset($conditions[$condition->triggercondition])) {
                $conditiondata[$condition->triggercondition] = $conditions[$condition->triggercondition]->include_data_forinstance($instance, $condition);
            }
        }

        return $conditiondata ?? [];
    }

    /**
     * Include actions data.
     *
     * @param [type] $data
     * @return void
     */
    public function include_actions_data(&$instance, $prefix=true) {

        // Fetch the list of enabled action plugins.
        $actionplugins = $this->actions;
        foreach ($actionplugins as $name => $plugin) {
            // Include all the actions data for this template.
            $actiondata[$name] = $plugin->include_data_forinstance($instance, $prefix);
        }

        return $actiondata;
    }

    public function get_courseid() {
        global $DB;
        return $DB->get_field('pulse_autoinstances', 'courseid', ['instance' => $this->instanceid]);
    }

    public function set_instance_actions() {
        // TODO: Fetch the actions based on the enable status for instances in future.
        $this->actions = \mod_pulse\plugininfo\pulseaction::get_list();
    }



     /**
     * Updates the "visible" field of the current menu and deletes it from the cache.
     *
     * @param bool $status The new value for the "status" field.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_status(bool $status, bool $instance = false) {

        return $this->update_field('status', $status, ['id' => $this->instanceid]);
    }

    /**
     * Updates a field of the current menu with the given key and value.
     *
     * @param string $key The key of the field to update.
     * @param mixed $value The new value of the field.
     * @return bool|int Returns true on success, or false on failure. it also deletes the current menu from cache.
     */
    public function update_field($key, $value) {
        global $DB;

        $result = $DB->set_field('pulse_autoinstances', $key, $value, ['id' => $this->instanceid]);

        return $result;
    }

    /**
     * Delete the current menu and all its associated items from the database.
     *
     * @return bool True if the deletion is successful, false otherwise.
     */
    public function delete_instance() {
        global $DB;

        if ($DB->delete_records('pulse_autoinstances', ['id' => $this->instanceid])) {
            // Delete all of its instance data from templates.
            $DB->delete_records('pulse_autotemplates_ins', ['instanceid' => $this->instanceid]);
            // Delete all its actions data for this instance.
            $this->delete_actions_instances($this->instanceid);

            return true;
        }
        return false;
    }

    /**
     * Create a duplicate of this instance. Fetch the formatdata for this instance and remove the ids.
     * Then send to the manage-instance method it will create new instance.
     *
     * @return void
     */
    public function duplicate() {

        $instance = (object) $this->get_instance_formdata();
        $instance->id = 0;
        $instance->instanceid = 0;

        foreach (helper::get_actions() as $action) {
            $config = $action->config_shortname();
            if (isset($instance->{$config.'_id'})) {
                $instance->{$config.'_id'} = 0;
            }
        }

        $context = \context_system::instance();
        helper::prepare_editor_draftfiles($instance, $context);

        // Create the item.
        self::manage_instance($instance);
    }

    public function get_report_url() {
        global $DB;

        $data = [
            'source' => 'pulseaction_notification\reportbuilder\datasource\notification',
            'component' => 'pulseaction_notification'
        ];
        if ($report = $DB->get_record('reportbuilder_report', $data)) {
            $url = new moodle_url('/reportbuilder/view.php', ['id' => $report->id, 'instanceid' => $this->instanceid]);
        } else {
            $data['name'] = get_string('automationreportname', 'pulse');
            $instance = reporthelper::create_report((object) $data, (bool) 1);
            $id = $instance->get('id');
            $url = new moodle_url('/reportbuilder/view.php', ['id' => $id, 'instanceid' => $this->instanceid]);
        }

        return $url;
    }

    /**
     * Delete all the available actions linked with this template.
     *
     * Find the lis of actions and get linked template instance based template id and delete those actions.
     *
     * @param int $templateid
     * @return void
     */
    public function delete_actions_instances($instanceid) {
        global $DB;
        // Fetch the list of enabled action plugins.
        $actionplugins = \mod_pulse\plugininfo\pulseaction::get_list();
        foreach ($actionplugins as $name => $plugin) {
            // Delete the instance data related to the action.
            $plugin->delete_instance_action($instanceid);
        }
    }

    /**
     * Trigger the instace actions.
     *
     * @param [type] $userid
     * @return void
     */
    public function trigger_action($userid, $runtime=null, $newuser=false) {
        global $DB;

        // Check the trigger conditions are ok.
        $instancedata = (object) $this->get_instance_formdata();
       /*  $conditions = $instancedata->condition ?? [];
        if (empty($conditions)) {
            return true;
        }

        // Verify the user is completed the instance conditions to access the actions.
        if ($this->find_user_completion_conditions($conditions, $instancedata, $userid)) { */

            foreach ($this->actions as $name => $plugin) {
                // Send the trigger conditions are statified, then initate the instances based.
                $plugin->trigger_action($instancedata, $userid, $runtime, $newuser);
            }
        // }
    }


    public function trigger_action_event($method, $eventdata) {
        global $DB;

        // Check the trigger conditions are ok.
        $instancedata = (object) $this->get_instance_data();

        foreach ($this->actions as $name => $plugin) {
            // Send the trigger conditions are statified, then initate the instances based.
            $plugin->trigger_action_event($instancedata, $method, $eventdata);
        }

    }

    /**
     * Find the user completion conditions.
     *
     * @param stdclass $conditions
     * @param stdclass $instancedata
     * @param int $userid
     *
     * @return void
     */
    public function find_user_completion_conditions($conditions, $instancedata, $userid, $isnewuser=false) {
        global $CFG;

        require_once($CFG->dirroot.'/lib/completionlib.php');
        // Get course completion info instance.

        $course = $instancedata->course ?? get_course($instancedata->courseid);

        // print_object($conditions);exit;
        $completion = new \completion_info($course);

        // Trigger condition operator method, require the user to complete all the conditions or any of one is fine.
        $count = ($instancedata->triggeroperator == action_base::OPERATOR_ALL);
        $enabled = $result = 0;

        foreach ($conditions as $component => $option) {
            // Get the condition plugin instance.
            $condition = \mod_pulse\plugininfo\pulsecondition::instance()->get_plugin($component);
            // Status of the condition, some conditions have additional values.
            $status = (is_array($option)) ? $option['status'] : $option;
            // No need to check the condition if condition is set as future enrolment and the user is old user.
            if ($status <= 0 ) {
                continue;
            }
            // Condition is only configured to verify the future enrolment.
            if ($status == condition_base::FUTURE && !$isnewuser) {
                $userenroltime = $this->get_user_enrolment_createtime($userid, $instancedata->course);
                // User enrolled before the condition is set as upcoming. then not need to verify the condition.
                // User is passed this condition by default.
                if ($userenroltime < $option['upcomingtime']) {
                    continue;
                }
            }

            $enabled++; // Increase enabled condition count.

            if ($condition->is_user_completed($instancedata, $userid, $completion)) {

                $result++; // Increase completed condition count for this user.
                // Instance only configured to complete any one of the conditions.
                if (!$count) {
                    return true; // Break the loop, found one completed condition.
                }
            }
        }

        return ($enabled == $result) ? true : false;
    }

    public function get_user_enrolment_createtime($userid, $course) {
        global $PAGE, $CFG;

        require_once($CFG->dirroot.'/enrol/locallib.php');

        static $context;
        static $courseid;


        if ($context == null || $course != $course->id) {
            $context = \context_course::instance($course->id);
            $courseid = $course->id;
        }
        $enrolments = (new course_enrolment_manager($PAGE, $course))->get_user_enrolments($userid);

        if (!empty($enrolments)) {
            $enrolmenttime = current($enrolments)->timecreated;
            return $enrolmenttime;
        }
        return false;
    }

    /**
     * Insert or update the menu instance to DB. Convert the multiple options select elements to json.
     * setup menu order after insert.
     *
     * Delete the current menu cache after updated the menu.
     *
     * @param stdclass $formdata
     * @return bool
     */
    public static function manage_instance($formdata) {
        global $DB;

        $record = $formdata;

        // Filter the overridden enabled form elements names as a list.
        $override = $record->override;

        if ($override = $record->override) {

            array_walk($override, function($value, $key) use (&$override) {
                $length = strlen('_editor');
                if (substr_compare($key, '_editor', -$length) === 0) { // Find elements Ends with _editor.
                    $key = str_replace('_editor','', $key);
                    $override[$key] = $value;
                }

                // Update the interval key to notify.
                // TODO: Update the method to notification action.
                // TODO: create hook to update the elements or add override element for groups.
                /* if ($key == 'interval') {
                    $key = 'pulsenotification_notify';
                    $override[$key] = $value;
                } */
            });

             /* print_object($override);
            exit; */

            $overridenkeys = array_filter($override, function($value) {
                return $value ? true : false;
            });

            // Fetch the list of keys need to remove override values - not overrides.
            $removeoverridenkeys = array_filter($override, function($value) {
                return $value ? false : true;
            });

            if (isset($formdata->instanceid)) {
                self::remove_override_values($removeoverridenkeys, $formdata->instanceid, $formdata->templateid);
            }
        }

        // Start the database transcation.
        $transaction = $DB->start_delegated_transaction();

        // Instance data to store in autoinstance table.
        $instancedata = (object) [
            'templateid' => $formdata->templateid,
            'courseid' => $formdata->courseid,
            'status' => true,
        ];

        // Check the isntance is already created. if created update the record otherwise create new instance.
        if (isset($formdata->instanceid) && $DB->record_exists('pulse_autoinstances', ['id' => $formdata->instanceid])) {

            $instancedata->id = $formdata->instanceid;
            // Update the template.
            $DB->update_record('pulse_autoinstances', $instancedata);
            // Show the edited success notification.
            \core\notification::success(get_string('templateupdatesuccess', 'pulse'));

            $instanceid = $instancedata->id;

        } else {
            $instanceid = $DB->insert_record('pulse_autoinstances', $instancedata);
            // Show the inserted success notification.
            \core\notification::success(get_string('templateinsertsuccess', 'pulse'));
        }


        // Store the tags.
        if (isset($overridenkeys['tags']) && !empty($overridenkeys['tags'])) {
            $tagoptions = self::get_tag_instance_options();
            $context = \context_system::instance();
            if (!empty($record->tags)) {
                \core_tag_tag::set_item_tags($tagoptions['component'], $tagoptions['itemtype'], $instanceid, $context, $record->tags);
            }
        }
        // Store the templates, conditions and actions data. Find the overridden elements.
        // if (!empty($overridenkeys)) {

            $conditions =  helper::filter_record_byprefix($override, 'condition');

            foreach ($conditions as $key => $status) {
                $component = explode('_', $key)[0];
                if (!isset($record->condition[$component])) {
                    continue;
                }
                $conditionname = "pulsecondition_".$component."\conditionform";
                $condition = new $conditionname();
                $condition->set_component($component);

                $condition->process_instance_save($instanceid, $record->condition[$component]);
            }

            // Fetch the value of the overridden settings.
            $overriddenelements = array_intersect_key((array) $record, $overridenkeys);

            // ...Store the templates overrides.
            // Fetch the auto templates fields.
            $templatefields = $DB->get_columns('pulse_autotemplates');
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
            \mod_pulse\automation\templates::update_instance_data($instanceid, $templatedata);

            // ...Send the data to action plugins for perform the data store.
            $context = \context_course::instance($record->courseid);
            // Find list of actions.
            $actionplugins = \mod_pulse\plugininfo\pulseaction::get_list();

            // Added the item id for file editors.
            $overriddenelements['instanceid'] = $instanceid;
            $overriddenelements['courseid'] = $record->courseid;
            $overriddenelements['templateid'] = $formdata->templateid;

            /* print_object($record);
            print_object($overridenkeys);
            exit; */
            foreach ($actionplugins as $component => $pluginbase) {
                $pluginbase->postupdate_editor_fileareas($overriddenelements, $context);
                $pluginbase->process_instance_save($instanceid, $overriddenelements);
            }
        // }

        // Allow to update the DB changes to Database.
        $transaction->allow_commit();


        return $instanceid;
    }

    /**
     * Remove the values of previously overrides values, those values are removed now.
     *
     * @param array $fields
     * @return void
     */
    protected static function remove_override_values($fields, $instanceid) {
        global $DB;

        if (!empty($fields)) {
            // Remove the conditions overrides.
            $conditions =  helper::filter_record_byprefix($fields, 'condition');
            foreach ($conditions as $key => $status) {
                $component = explode('_', $key)[0];
                $DB->set_field('pulse_condition_overrides', 'isoverridden', null, ['instanceid' => $instanceid, 'triggercondition' => $component]);
            }

            // Remove template fields data.
            $templatefields = $DB->get_columns('pulse_autotemplates');
            $tempfields = array_keys($templatefields);
            $preventfields = ['id', 'triggerconditions', 'timemodified'];
            // Clear unused fields from list.
            $tempfields = array_diff_key(array_flip($tempfields), array_flip($preventfields));
            $templatedata = array_intersect_key((array) $fields, $tempfields);
            $templatedata = array_fill_keys(array_keys($templatedata), null);
            $templatedata['id'] = $DB->get_field('pulse_autotemplates_ins', 'id', ['instanceid' => $instanceid]);
            $DB->update_record('pulse_autotemplates_ins', $templatedata);

            // Remove the actions overrides.
            $actions = helper::get_actions();
            foreach ($actions as $component => $pluginbase) {
                 // Filter the current action data from the templates data by its shortname.
                $actiondata = $pluginbase->filter_action_data((array) $fields);
                $actiondata = (object) array_fill_keys(array_keys((array) $actiondata), null);
                if (empty((array) $actiondata)) {
                    continue;
                }
                $actiondata->id = $DB->get_field('pulseaction_'.$component.'_ins', 'id', ['instanceid' => $instanceid]);
                $DB->update_record('pulseaction_'.$component.'_ins', $actiondata);
            }
        }
    }


}
