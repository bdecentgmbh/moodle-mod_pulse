<?php


namespace pulsecondition_activity;

use mod_pulse\automation\condition_base;

class conditionform extends \mod_pulse\automation\condition_base {

    public function include_action(&$option) {
        $option['activitycompletion'] = get_string('activitycompletion', 'pulsecondition_activity');
    }

    public function load_instance_form(&$mform, $forminstance) {

        $mform->addElement('html', '<h3>'.get_string('activitycompletion', 'pulsecondition_activity').'</h3>');

        $completionstr = get_string('activitycompletion', 'pulsecondition_activity');

        $mform->addElement('select', 'condition[activity][status]', $completionstr, $this->get_options());

        $courseid = $forminstance->get_customdata('courseid') ?? '';
        $modinfo = \course_modinfo::instance($courseid);

        // Include the suppress activity settings for the instance.
        $completion = new \completion_info(get_course($courseid));
        $activities = $completion->get_activities();
        array_walk($activities, function(&$value) {
            $value = $value->name;
        });

        $modules = $mform->addElement('autocomplete', 'condition[activity][modules]', get_string('suppressmodule', 'pulseaction_notification'), $activities);
        $modules->setMultiple(true);
        $mform->disabledIf('condition[activity][modules]', 'condition[activity][status]', 'eq', self::DISABLED);
    }

    public function is_user_completed($instancedata, $userid, \completion_info $completion=null) {
        // Get the notification suppres module ids.
        $additional = $instancedata->conditions['activity'] ?? [];
        $modules = $additional['modules'] ?? [];
        if (!empty($modules)) {
            $result = [];

            if ($completion === null) {
                $completion = new \completion_info(get_course($instancedata->courseid));
            }
            // Find the completion status for all this suppress modules.
            foreach ($modules as $cmid) {
                $modulecompletion = $completion->get_completion_data($cmid, $userid, []);
                if ($modulecompletion['completionstate'] == COMPLETION_COMPLETE) {
                    $result[] = true;
                }
            }
            // If suppress operator set as all, check all the configures modules are completed.
            // Remove the schedule only if all the activites are completed.
            if (count($result) == count($modules)) {
                return true;
            }

            return false;
        }
        return true;
    }

    /**
     * Module completed.
     *
     * @param [type] $eventdata
     * @return void
     */
    public static function module_completed($eventdata) {
        // Event data.
        $data = $eventdata->get_data();

        $cmid = $data['contextinstanceid'];
        $userid = $data['userid'];
        // Get the info for the context.
        list($context, $course, $cm) = get_context_info_array($data['contextid']);
        // Self condition instance.
        $condition = new self();
        // Course completion info.
        $completion = new \completion_info($course);

        // Get all the notification instance configures the suppress with this activity.
        $notifications = self::get_acitivty_notifications($cmid);

        foreach ($notifications as $notification) {
            // Get the notification suppres module ids.
            $additional = $notification->additional ? json_decode($notification->additional) : '';
            $modules = $additional['modules'] ?? [];
            if (!empty($modules)) {
                // Remove the schedule only if all the activites are completed.
                // if ($condition->is_user_completed($notification, $userid, $completion)) {
                $condition->trigger_instance($notification->instanceid, $userid);
                // }
            }

            return true;
        }
    }


    /**
     * Fetch the list of menus which is used the triggered ID in the access rules for the given method.
     *
     * Find the menus which contains the given ID in the access rule (Role or cohorts).
     *
     * @param int $id ID of the triggered method, Role or cohort id.
     * @param string $method Field to find, Role or Cohort.
     * @return array
     */
    public static function get_acitivty_notifications($id) {
        global $DB;

        $like = $DB->sql_like('additional', ':value');
        $activitylike = $DB->sql_like('triggercondition', ':activity');
        $sql = "SELECT * FROM {pulse_condition_overrides} WHERE status >= 1 AND $activitylike AND $like";
        $params = ['activity' => 'activity', 'value' => '%"'.$id.'"%'];

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }


    public function process_instance_save($instanceid, $data) {
        global $DB;

        $record = [
            'instanceid' => $instanceid,
            'triggercondition' => $this->component,
            'status' => $data['status'] ?? null,
            'additional' => json_encode(['modules' => $data['modules']]),
            'isoverridden' => (isset($data['status']))

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

    public function include_data_forinstance(&$instance, $data) {
        $instance->condition[$this->component] = $data->additional ? json_decode($data->additional, true) : [];
        $instance->condition[$this->component]['status'] = $data->status;
        $instance->override['condition_activity_status'] = 1;
        $instance->override['condition_activity_modules'] = 1;
    }

}
