<?php


namespace pulsecondition_cohort;

use mod_pulse\automation\condition_base;


class conditionform extends \mod_pulse\automation\condition_base {

    public function include_action(&$option) {
        $option['cohort'] = get_string('condition', 'pulsecondition_cohort');
    }

    public function load_instance_form(&$mform, $forminstance) {
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');

        $completionstr = get_string('condition', 'pulsecondition_cohort');

        $mform->addElement('select', 'condition[cohort][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[cohort][status]', 'condition', 'pulsecondition_cohort');

        $cohorts = cohort_get_all_cohorts();
        $cohorts = $cohorts['cohorts'];

        array_walk($cohorts, function(&$value) {
            $value = $value->name;
        });

        // TODO: double check all the config names and help icons.
        $cohorts = $mform->addElement('autocomplete', 'condition[cohort][cohorts]', get_string('cohorts', 'pulsecondition_cohort'), $cohorts);
        $cohorts->setMultiple(true);
        $mform->hideIf('condition[cohort][cohorts]', 'condition[cohort][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[cohort][cohorts]', 'cohorts', 'pulsecondition_cohort');

        $mform->addElement('hidden', 'override[condition_cohort_cohorts]', 1);
        $mform->setType('override[condition_cohort_cohorts]', PARAM_RAW);
    }

    public function is_user_completed($instancedata, $userid, \completion_info $completion=null) {
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php'); // Cohort library file inclusion.

        // Find the cohort conditions is enabled if not then make this condition true.
        if (!isset($instancedata->condition['cohort']['status']) || $instancedata->condition['cohort']['status'] == 0 ) {
            return true;
        }

        // Get the cohort ids.
        $cohorts = $instancedata->condition['cohort']['cohorts'] ?? [];

        foreach ($cohorts as $cohort) {

            if (cohort_is_member($cohort, $userid)) {
                return true;
            }
        }

        // Cohorts are configured but not completed.
        return !empty($cohorts) ? false : true;
    }

    /**
     * Module completed.
     *
     * @param [type] $eventdata
     * @return void
     */
    public static function member_added($eventdata) {
        global $DB;

        $data = $eventdata->get_data();

        $cohortid = $data['objectid'];
        $relateduserid = $data['relateduserid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $patlike = $DB->sql_like('pat.triggerconditions', ':cohort');
        $overlike = $DB->sql_like('additional', ':value');
        $cohortlike = $DB->sql_like('co.triggercondition', ':cohort2');

        $sql = "SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} AS pat ON pat.id = ai.templateid
        JOIN {pulse_condition_overrides} AS co ON co.instanceid = ai.id
        WHERE ($patlike OR ($cohortlike AND co.status > 0) ) AND $overlike";

        $params = ['cohort' => 'cohort', 'cohort2' => 'cohort', 'value' => '%"'.$cohortid.'"%'];

        $instances = $DB->get_records_sql($sql, $params);

        $condition = new self();
        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition->trigger_instance($instance->instanceid, $relateduserid);
        }

        return true;
    }


    /*public function process_instance_save($instanceid, $data) {
        global $DB;

        $record = [
            'instanceid' => $instanceid,
            'triggercondition' => $this->component,
            'status' => $data['status'],
            'additional' => json_encode(['cohorts' => $data['cohorts']])
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
    } */

 /*    public function include_data_forinstance1(&$instance, $data) {
        $instance->condition[$this->component] = $data->additional ? json_decode($data->additional, true) : [];
        $instance->condition[$this->component]['status'] = $data->status;
        $instance->override['condition_cohort_status'] = 1;
        $instance->override['condition_cohort_cohorts'] = 1;
    } */

}
