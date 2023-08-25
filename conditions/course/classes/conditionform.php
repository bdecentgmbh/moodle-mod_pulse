<?php


namespace pulsecondition_course;

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
        global $DB;

        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        $completion = ($completion !== null) ? $completion : new \completion_info($course);
        return $completion->is_course_complete($course);
    }

    public function include_action(&$option) {
        $option['coursecompletion'] = get_string('coursecompletion', 'pulsecondition_course');
    }

    public function load_instance_form(&$mform, $forminstance) {

        $mform->addElement('html', '<h3>'.get_string('coursecompletion', 'pulsecondition_course').'</h3>');

        $completionstr = get_string('coursecompletion', 'pulsecondition_course');


        $mform->addElement('select', 'condition[course]', $completionstr, $this->get_options());
    }

    public static function course_completed($eventdata) {
        global $DB;

        $data = $eventdata->get_data();
        $courseid = $data['courseid'];
        $relateduserid = $data['userid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $like = $DB->sql_like('pat.triggerconditions', ':value');
        $sql = "SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} AS pat ON pat.id = ai.templateid
        JOIN {pulse_condition_overrides} AS co ON co.instanceid = ai.id AND co.triggercondition = 'course'
        WHERE ai.courseid=:courseid AND (co.status = null OR co.status > 0) AND $like";

        $params = ['courseid' => $courseid, 'value' => '%"course"%'];

        $instances = $DB->get_records_sql($sql, $params);

        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition = (new self())->trigger_instance($instance->instanceid, $relateduserid);
        }

        return true;
    }
}
