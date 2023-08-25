<?php

namespace mod_pulse\automation;

use moodle_exception;

abstract class action_base {

    const OPERATOR_ANY = '1';

    const OPERATOR_ALL = '2';

    protected $component;

    abstract public function config_shortname();

    abstract public function trigger_action($instancedata, $userid);

    abstract public function delete_template_action($templateid);

    abstract public function get_data_fortemplate($templateid);

    abstract public function get_data_forinstance($instanceid);

    abstract public function load_global_form(&$mform, $forminstance);

    public function set_component(string $component) {
        $this->component = $component;
    }

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

    public function get_tablename() {
        global $DB;
        return $DB->get_prefix().'pulseaction_'.$this->component;
    }


    public function prepare_editor_fileareas(&$data, \context $context) {
        return false;
    }

    // Load instance form.
    public function load_instance_form(&$mform, $forminstance) {

        $this->load_global_form($mform, $forminstance);
        // $elements = $mform->_elements;
    }

    public function delete_instance_action(int $instanceid) {
        global $DB;

        if (!$this->component) {
            throw new moodle_exception('componentnotset', 'pulse');
        }

        $instancetable = 'pulseaction_'.$this->component.'_ins';
        return $DB->delete_records($instancetable, ['instanceid' => $instanceid]);
    }


    protected function filter_action_data($record) {

        $shortname = $this->config_shortname();

        $final = helper::filter_record_byprefix($record, $shortname);

        return (object) $final;
    }


    public function update_encode_data(&$data) {
        return null;
    }

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
     * Include the actions data before set to form.
     *
     * @param [type] $instance
     * @return void
     */
    public function include_data_forinstance(&$instance) {

        $actiondata = $this->get_data_fortemplate($instance->templateid);

        if (empty($actiondata)) {
            return false;
        }

        $prefix = $this->config_shortname();

        $actioninstancedata = $this->get_data_forinstance($instance->id);

        // TODO: Get editors dynamically.
        $editors = ['headercontent', 'footercontent', 'staticcontent'];
        // Include the override data which is used in the form.
        foreach ($actioninstancedata as $configname => $configvalue) {
            if ($configvalue !== null) {
                $configname = in_array($configname, $editors) ? $configname.'_editor' : $configname;
                $instance->override[$prefix . "_" . $configname] = 1;
            }
        }

        $actiondata = \mod_pulse\automation\helper::merge_instance_overrides($actioninstancedata, $actiondata);

        $this->update_encode_data($actiondata);

        if (!empty($actiondata)) {

            $notificationkeys = array_keys($actiondata);

            array_walk($notificationkeys, function(&$value) use ($prefix) {
                $value = $prefix.'_'.$value;
            });

            $instance = (object) array_merge((array) $instance, array_combine($notificationkeys, array_values($actiondata)));
            // TODO: Include overrides.
            return $instance;
        }
        return $actiondata;
    }






}
