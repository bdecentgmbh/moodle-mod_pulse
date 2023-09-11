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

    public function upcoming_element(&$mform) {

        $mform->addElement('hidden', 'condition['.$this->component.'][upcomingtime]');
        $mform->setType('condition['.$this->component.'][upcomingtime]', PARAM_INT);
    }

    public function get_options() {
        return [
            self::DISABLED => get_string('disable'),
            self::ALL => get_string('all'),
            self::FUTURE => get_string('upcoming', 'pulse'),
        ];
    }

    public function trigger_instance(int $instanceid, int $userid, $expectedtime=null) {

        \mod_pulse\automation\instances::create($instanceid)->trigger_action($userid, $expectedtime);
    }

    public function is_user_completed($notification, int $userid) {
        return true;
    }

    public function process_instance_save($instanceid, $data) {
        global $DB;

        $filter = array_filter($data);
        if (!isset($data['status']) && empty($filter)) {
            return true;
        }

        $status = $data['status'] ?? '';
        // Future enrolment is disabled then make the upcoming time to null.
        if ($status != condition_base::FUTURE) {
            $data['upcomingtime'] = '';
        }
        // Future enrolments are enabled and its upcoming is empty set current time as upcoming.
        // Conditions will affect for coming enrolments after this time.
        if ($status == condition_base::FUTURE && $data['upcomingtime'] == 0) {
            $data['upcomingtime'] = time();
        }

        $record = [
            'instanceid' => $instanceid,
            'triggercondition' => $this->component,
            'status' => $data['status'] ?? null,
            'upcomingtime' => $data['upcomingtime'] ?? null,
            'isoverridden' => (isset($data['status'])) ? true : false
        ];
        unset($data['status']);
        $record['additional'] = json_encode($data);

        if ($condition = $DB->get_record('pulse_condition_overrides', ['instanceid' => $instanceid, 'triggercondition' => $this->component])) {
            $record['id'] = $condition->id;
            // Update the record.
            $DB->update_record('pulse_condition_overrides', $record);
        } else {
            // Insert the record.
            $DB->insert_record('pulse_condition_overrides', $record);
        }

        return true;
    }



    public function include_data_forinstance(&$instance, $data, $prefix=true) {

        // $instance->condition[$this->component]['status'] = $data->status;
        // $instance->override['condition_'.$this->component] = 1;


        $additional = $data->additional ? json_decode($data->additional, true) : [];
        foreach ($additional as $key => $value) {
            $instance->override["condition_".$this->component."_".$key] = 1;
        }

        if ($data->isoverridden) {
            $instance->condition[$this->component]['status'] = $data->status;
            $instance->override["condition_".$this->component."_status"] = 1;
        }

        if (isset($instance->condition[$this->component]) && $instance->condition[$this->component] != null) {

            $instance->condition[$this->component] = array_merge($instance->condition[$this->component], $additional);
            $instance->condition[$this->component]['upcomingtime'] = $data->upcomingtime ?: 0;
        }

        // $instance->override['condition_activity_modules'] = 1;

        /* $instance->condition = $instance->condition ?? [];
        $instance->condition = array_merge($instance->condition, $condition); */
    }
}
