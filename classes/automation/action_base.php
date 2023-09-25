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
 * Notification pulse action - Automation actions plugins controller base.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\automation;

use moodle_exception;

/**
 * Notification pulse action - Automation actions base.
 */
abstract class action_base {

    /**
     * Repersents the actions operator is any
     * @var int
     */
    const OPERATOR_ANY = 1;

    /**
     * Repersents the actions operator is all
     * @var int
     */
    const OPERATOR_ALL = 2;

    /**
     * The name of this action plugin.
     *
     * @var string
     */
    protected $component;

    /**
     * Returns the shortname of the configuration.
     *
     * @return string The shortname of the configuration.
     */
    abstract public function config_shortname();

    /**
     * Triggers the action for a specific instance and user.
     *
     * @param object $instancedata The data for the instance.
     * @param int $userid The ID of the user.
     *
     * @return void
     */
    abstract public function trigger_action($instancedata, $userid);

    /**
     * Deletes the action associated with a template.
     *
     * @param int $templateid The ID of the template.
     *
     * @return void
     */
    abstract public function delete_template_action($templateid);

    /**
     * Gets the data for a specific template.
     *
     * @param int $templateid The ID of the template.
     *
     * @return mixed The data for the template.
     */
    abstract public function get_data_fortemplate($templateid);

    /**
     * Gets the data for a specific instance.
     *
     * @param int $instanceid The ID of the instance.
     *
     * @return mixed The data for the instance.
     */
    abstract public function get_data_forinstance($instanceid);

    /**
     * Loads the global form.
     *
     * @param object $mform The Moodle form object.
     * @param mixed $forminstance The form instance.
     *
     * @return void
     */
    abstract public function load_global_form(&$mform, $forminstance);

    /**
     * Sets the action plugins name.
     *
     * @param string $component The component to set.
     *
     * @return void
     */
    public function set_component(string $component) {
        $this->component = $component;
    }

    /**
     * Gets the name of the action component.
     *
     * @return string The component.
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * Get the instance tablename for this action.
     *
     * @return string Tablename.
     */
    public function get_instance_tablename() {
        global $DB;
        return $DB->get_prefix().'pulseaction_'.$this->component.'_ins';
    }

    /**
     * Gets the table name for the action component.
     *
     * @return string The table name for the action component.
     */
    public function get_tablename() {
        global $DB;
        return $DB->get_prefix().'pulseaction_'.$this->component;
    }

    /**
     * Performs actions after data has been defined in the form.
     *
     * @param object $mform The Moodle form object.
     * @param \automation_templates_form $forminstance The form instance.
     *
     * @return void
     */
    public function definition_after_data(&$mform, $forminstance) {
    }

    /**
     * Prepares file areas for the editor.
     *
     * @param mixed $data The default data of the form.
     * @param \context $context The context object.
     *
     * @return bool False.
     */
    public function prepare_editor_fileareas(&$data, \context $context) {
        return false;
    }

    /**
     * Returns the default override elements.
     *
     * @return array An array of default override elements.
     */
    public function default_override_elements() {
        return [];
    }

    /**
     * Observe an event triggered from conditions.
     *
     * @param object $instancedata The data for the instance.
     * @param string $method The method to trigger.
     * @param mixed $eventdata The event data.
     *
     * @return bool True.
     */
    public function trigger_action_event($instancedata, $method, $eventdata) {
        return true;
    }

    /**
     * Handles actions when an instance is disabled.
     *
     * @param int $instanceid The ID of the instance.
     * @param int $status The status of the instance.
     *
     * @return bool True.
     */
    public function instance_disabled($instanceid, $status) {
        return true;
    }

    /**
     * Loads the instance form by calling the global form loading method.
     *
     * @param object $mform The automation instance form object.
     * @param mixed $forminstance
     *
     * @return void
     */
    public function load_instance_form(&$mform, $forminstance) {
        $this->load_global_form($mform, $forminstance);
    }

    /**
     * Deletes an action instances.
     *
     * @param int $instanceid The ID of the instance.
     *
     * @return bool True.
     */
    public function delete_instance_action(int $instanceid) {
        global $DB;

        if (!$this->component) {
            throw new moodle_exception('componentnotset', 'pulse');
        }

        $instancetable = 'pulseaction_'.$this->component.'_ins';
        return $DB->delete_records($instancetable, ['instanceid' => $instanceid]);
    }

    /**
     * Filters action data by its configuration shortname.
     *
     * @param mixed $record The record to filter.
     *
     * @return object The filtered record.
     */
    public function filter_action_data($record) {

        $shortname = $this->config_shortname();

        $final = helper::filter_record_byprefix($record, $shortname);

        return (object) $final;
    }

    /**
     * Updates encoded data.
     *
     * @param mixed $data The data to update.
     *
     * @return mixed|null
     */
    public function update_encode_data(&$data) {
        return null;
    }

    /**
     * Includes data for a template.
     *
     * @param object $data The data object.
     *
     * @return bool True if successful, false otherwise.
     */
    public function include_data_fortemplate(&$data) {
        global $DB;

        // In moodle, the main table should be the name of the component.
        // Therefore, generate the table name based on the component name.
        $actiondata = $this->get_data_fortemplate($data->templateid);

        if (empty($actiondata)) {
            return false;
        }

        $this->update_encode_data($actiondata);

        if (!empty($actiondata)) {
            $prefix = $this->config_shortname();
            $notificationkeys = array_keys($actiondata);
            array_walk($notificationkeys, function(&$value) use ($prefix) {
                $value = $prefix.'_'.$value;
            });
            $data = (object) array_merge((array) $data, array_combine($notificationkeys, array_values($actiondata)));
        }
    }

    /**
     * Includes actions instance data for an instance.
     *
     * @param stdClass $instance The instance object.
     * @param bool $addprefix Flag to add prefix to keys.
     * @return stdClass|array|bool Modified instance object or data without prefix.
     */
    public function include_data_forinstance(&$instance, $addprefix=true) {

        // Retrieve action data for the template.
        $actiondata = $this->get_data_fortemplate($instance->templateid);

        if (empty($actiondata)) {
            return false;
        }
        // Set the prefix based on the component.
        $prefix = $this->config_shortname();
        // Get data specific to the instance.
        $actioninstancedata = $this->get_data_forinstance($instance->id);

        // TODO: Get editors dynamically.
        // Define editors for special handling.
        $editors = ['headercontent', 'footercontent', 'staticcontent'];
        // Include the override data which is used in the form.
        foreach ($actioninstancedata as $configname => $configvalue) {
            if ($configvalue !== null) {
                $configname = in_array($configname, $editors) ? $configname.'_editor' : $configname;
                $instance->override[$prefix . "_" . $configname] = 1;
            }
        }
        // Merge instance overrides with template data.
        $actiondata = \mod_pulse\automation\helper::merge_instance_overrides($actioninstancedata, $actiondata);
        // Update encoded data if necessary.
        $this->update_encode_data($actiondata);
        // Handle dynamic content if configured.
        $actiondata['mod'] = ($actiondata['dynamiccontent']) ? get_coursemodule_from_id('', $actiondata['dynamiccontent']) : [];

        // Apply prefix to keys if needed.
        if (!empty($actiondata) && $addprefix) {
            $notificationkeys = array_keys($actiondata);
            array_walk($notificationkeys, function(&$value) use ($prefix) {
                $value = $prefix.'_'.$value;
            });

            // Update the keys with prefix.
            $instance = (object) array_merge((array) $instance, array_combine($notificationkeys, array_values($actiondata)));
            // TODO: Include overrides.
            return $instance;
        }

        // Return modified instance or data without prefix.
        return $actiondata;
    }

    /**
     * Retrieves instances associated with a specific template.
     *
     * @param int $templateid The ID of the template.
     *
     * @return array An array of template instances or an empty array if none are found.
     */
    protected function get_template_instances($templateid) {
        global $DB;

        if ($instances = $DB->get_records('pulse_autoinstances', ['templateid' => $templateid]) ) {
            return $instances;
        }

        return [];
    }

}
