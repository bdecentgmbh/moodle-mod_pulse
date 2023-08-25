<?php

namespace mod_pulse\automation;

abstract class condition_base {

    const OPERATOR_ANY = '1';

    const OPERATOR_ALL = '2';

    const DISABLED = '0'; // Option
    const ALL = '1';
    const FUTURE = '2';

    protected $component;

    abstract public function include_action(&$option);

    abstract public function load_instance_form(&$mform, $forminstance);

    public function set_component($componentname) {
        $this->component = $componentname;
    }

    public function trigger_instance(int $instanceid, int $userid) {
        \mod_pulse\automation\instances::create($instanceid)->trigger_action($userid);
    }

    public function is_user_completed($notification, int $userid) {
        return true;
    }

    public function process_instance_save($instanceid, $status) {
        global $DB;

        $record = [
            'instanceid' => $instanceid,
            'triggercondition' => $this->component,
            'status' => $status
        ];

        if ($condition = $DB->get_record('pulse_condition_overrides', ['instanceid' => $instanceid, 'triggercondition' => $this->component]) ) {
            $record['id'] = $condition->id;
            // Update the record.
            $DB->update_record('pulse_condition_overrides', $record);
        } else {
            // Insert the record.
            $DB->insert_record('pulse_condition_overrides', $record);
        }

        return true;
    }

    public function get_options() {
        return [
            self::DISABLED => get_string('disable'),
            self::ALL => get_string('all'),
            self::FUTURE => get_string('upcoming', 'pulse'),
        ];
    }

    public function include_data_forinstance(&$instance, $data) {
        $instance->condition[$this->component] = $data->status;
        $instance->override['condition_'.$this->component] = 1;
    }
}
