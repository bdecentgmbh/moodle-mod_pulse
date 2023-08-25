<?php

namespace mod_pulse\automation;

use \mod_pulse\automation\action_base;

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


    public function get_instance_data() {
        global $DB;


        $actions = \mod_pulse\plugininfo\pulseaction::get_list();
        $select = ['ai.id as instanceid'];

        $i = 0;
        $join = [];
        foreach ($actions as $component => $action) {
            $i++;
            $tablename = $action->get_instance_tablename();
            if (!$tablename) {
                continue;
            }
            $sht = "$component"."id";
            $join[] = " JOIN $tablename AS $sht ON $sht.instanceid=ai.id ";
            $select[] = "$sht.id as $sht, $sht.*";
        }

        $sql = " SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates_ins} AS ati ON ati.instanceid=ai.id";

        $sql .= implode(" ", $join);
        $sql .= " WHERE ai.id=:instance ";

        $this->instance = $DB->get_record_sql($sql, ['instance' => $this->instanceid]);
        $templatedata = \mod_pulse\automation\templates::create($this->instance->templateid)->get_templates_rawdata();

        $this->instance = \mod_pulse\automation\helper::merge_instance_overrides($this->instance, $templatedata);

        return $this->instance;
    }

    protected function get_instance_record() {
        global $DB;

        return $DB->get_record('pulse_autoinstances', ['id' => $this->instanceid]);
    }

    /**
     * Get the instance formdata.
     *
     * @return void
     */
    public function get_instance_formdata() {

        // Fetch the instance record to find the template id.
        $instance = $this->get_instance_record();

        \mod_pulse\automation\templates::create($instance->templateid)->get_data_forinstance($instance);

        $this->include_actions_data($instance);

        $this->include_conditions_data($instance);

        return ((array) $instance);
    }

    public function include_conditions_data(&$instance) {
        global $DB;

        $overrides = $DB->get_records('pulse_condition_overrides', ['instanceid' => $instance->id]);
        $conditions = \mod_pulse\plugininfo\pulsecondition::get_list();

        foreach ($overrides as $condition) {
            if (isset($conditions[$condition->triggercondition])) {
                $conditions[$condition->triggercondition]->include_data_forinstance($instance, $condition);
            }
        }

    }

    /**
     * Include actions data.
     *
     * @param [type] $data
     * @return void
     */
    public function include_actions_data(&$instance) {

        // Fetch the list of enabled action plugins.
        $actionplugins = $this->actions;
        foreach ($actionplugins as $name => $plugin) {
            // Include all the actions data for this template.
            $plugin->include_data_forinstance($instance);
        }
    }

    public function get_courseid() {
        global $DB;
        return $DB->get_field('pulse_autoinstances', 'courseid', ['instance' => $this->instanceid]);
    }

    public function set_instance_actions() {
        // TODO: Fetch the actions based on the enable status for instances in future.
        $this->actions = \mod_pulse\plugininfo\pulseaction::get_list();
    }

    public static function create($instanceid) {
        $instance = new self($instanceid);
        return $instance;
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
    public function trigger_action($userid) {
        global $DB;

        // Check the trigger conditions are ok.
        $instancedata = (object) $this->get_instance_formdata();
        $conditions = $instancedata->condition ?? [];
        if (empty($conditions)) {
            return true;
        }

        // Verify the user is completed the instance conditions to access the actions.
        if ($this->find_user_completion_conditions($conditions, $instancedata, $userid)) {

            foreach ($this->actions as $name => $plugin) {
                // Send the trigger conditions are statified, then initate the instances based.
                $plugin->trigger_action($instancedata, $userid);
            }
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
    protected function find_user_completion_conditions($conditions, $instancedata, $userid) {
        global $CFG;

        require_once($CFG->dirroot.'/lib/completionlib.php');
        // Get course completion info instance.
        $completion = new \completion_info(get_course($instancedata->courseid));

        // Trigger condition operator method, require the user to complete all the conditions or any of one is fine.
        $count = ($instancedata->triggeroperator == action_base::OPERATOR_ALL);
        $enabled = $result = 0;

        foreach ($conditions as $component => $option) {
            // Get the condition plugin instance.
            $condition = \mod_pulse\plugininfo\pulsecondition::instance()->get_plugin($component);
            // Status of the condition, some conditions have additional values.
            $status = (is_array($option)) ? $option['status'] : $option;
            if ($status > 0) {
                continue;
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
                if (str_ends_with($key, '_editor')) {
                    $key = str_replace('_editor','', $key);
                    $override[$key] = $value;
                }
            });

            $overridenkeys = array_filter($override, function($value) {
                return $value ? true : false;
            });

            // print_object($overridden)
        }

        // Start the database transcation.
        $transaction = $DB->start_delegated_transaction();

        // Instance data to store in autoinstance table.
        $instancedata = (object) [
            'templateid' => $formdata->templateid,
            'courseid' => $formdata->courseid,
            'status' => true,
        ];

        // print_object($formdata);exit;
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
        $tagoptions = self::get_tag_instance_options();
        $context = \context_system::instance();
        \core_tag_tag::set_item_tags($tagoptions['component'], $tagoptions['itemtype'], $instanceid, $context, $record->tags);

        // Store the templates, conditions and actions data. Find the overridden elements.
        if (!empty($overridenkeys)) {

            $conditions =  helper::filter_record_byprefix($override, 'condition');
            // print_object($record);exit;

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
            foreach ($actionplugins as $component => $pluginbase) {
                $pluginbase->postupdate_editor_fileareas($overriddenelements, $context);
                $pluginbase->process_instance_save($instanceid, $overriddenelements);
            }
        }

        // Allow to update the DB changes to Database.
        $transaction->allow_commit();


        return $instanceid;
    }
}
