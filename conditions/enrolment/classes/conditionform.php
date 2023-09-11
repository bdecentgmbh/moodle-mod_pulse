<?php


namespace pulsecondition_enrolment;

use mod_pulse\automation\condition_base;

class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Verify the user is completed the course which is configured in the conditions for the notification.
     *
     * @param stdclass $notification
     * @param int $userid
     * @return bool
     */
    public function is_user_completed($instancedata, int $userid, \completion_info $completion=null) {
        $courseid = $instancedata->courseid;
        $context = \context_course::instance($courseid);

        return is_enrolled($context, $userid);
    }

    /**
     * Include data to action.
     *
     * @param array $option
     * @return void
     */
    public function include_action(&$option) {
        $option['enrolment'] = get_string('enrolment', 'pulsecondition_enrolment');
    }

    /**
     * Load instance form.
     *
     * @param \mform $mform
     * @param stdclass $forminstance
     * @return void
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('enrolment', 'pulsecondition_enrolment');

        $mform->addElement('select', 'condition[enrolment][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[enrolment][status]', 'enrolment', 'pulsecondition_enrolment');
    }

    /**
     * User enrolled.
     *
     * @param stdclass $eventdata
     * @return void
     */
    public static function user_enrolled($eventdata) {
        global $DB;

        $data = $eventdata->get_data();
        $courseid = $data['courseid'];
        $relateduserid = $data['userid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $like = $DB->sql_like('pat.triggerconditions', ':value');
        $sql = "SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} AS pat ON pat.id = ai.templateid
        JOIN {pulse_condition_overrides} AS co ON co.instanceid = ai.id
        WHERE ai.courseid=:courseid AND ($like OR co.triggercondition = 'enrol')";

        $params = ['courseid' => $courseid, 'value' => '%"enrol"%'];

        $instances = $DB->get_records_sql($sql, $params);

        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition = (new self())->trigger_instance($instance->instanceid, $relateduserid);
        }

        return true;
    }
}
