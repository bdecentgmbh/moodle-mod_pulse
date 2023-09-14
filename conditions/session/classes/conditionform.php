<?php


namespace pulsecondition_session;
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

use mod_pulse\automation\condition_base;

class conditionform extends \mod_pulse\automation\condition_base {

    const MODNAME = 'facetoface';

    public function include_action(&$option) {
        $option['session'] = get_string('sessioncompletion', 'pulsecondition_session');
    }

    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('sessioncompletion', 'pulsecondition_session');

        $mform->addElement('select', 'condition[session][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[session][status]', 'sessioncompletion', 'pulsecondition_session');

        $courseid = $forminstance->get_customdata('courseid') ?? '';

        // Include the suppress session settings for the instance.
        // $modinfo = \course_modinfo::instance($courseid);
        $list = [];
        $activities = get_all_instances_in_courses(STATIC::MODNAME, [$courseid => $courseid]);
        // foreach ($activities)
        // facetoface_get_sessions
        array_map(function($value) use (&$list) {
            $list[$value->id] = $value->name;
        }, $activities);

        $mform->addElement('autocomplete', 'condition[session][modules]', get_string('sessionmodule', 'pulsecondition_session'), $list);
        $mform->disabledIf('condition[session][modules]', 'condition[session][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[session][modules]', 'sessionmodule', 'pulsecondition_session');

        // TODO: add this revention of override checkbox to dynamic instead of specific in each plugin.
        $mform->addElement('hidden', 'override[condition_session_modules]', 1);
        $mform->setType('override[condition_session_modules]', PARAM_RAW);
    }

    /**
     * Find the users is booked the session.
     *
     * @param [type] $instancedata
     * @param [type] $userid
     * @param \completion_info|null $completion
     * @return boolean
     */
    public function is_user_completed($instancedata, $userid, \completion_info $completion=null) {
        global $DB;

        // Get the notification suppres module ids.
        $additional = $instancedata->conditions['session'] ?? [];
        $modules = $additional['modules'] ?? '';
        if (!empty($modules)) {
            $result = [];

            $sql = "SELECT * FROM {facetoface_signups} f2f_su
            JOIN {facetoface_sessions} f2f_ss ON f2f_ss.id = f2f_su.sessionid
            WHERE f2f_ss.facetoface = :f2fid AND f2f_su.userid = :userid";

            $existingsignup = $DB->count_records_sql($sql, array('f2fid' => $modules, 'userid' => $userid));

            return ($existingsignup) ? true : false;
        }
        // Not configured any session modules.
        return true;
    }

    /**
     * Module completed.
     *
     * @param [type] $eventdata
     * @return void
     */
    public static function signup_success($eventdata) {
        global $DB;

        // Event data.
        $data = $eventdata->get_data();
/*
        print_object($data);
        exit; */

        $cmid = $data['contextinstanceid'];
        $userid = $data['userid'];
        $sessionid = $data['objectid'];

        // Get the info for the context.
        list($context, $course, $cm) = get_context_info_array($data['contextid']);
        // Self condition instance.
        $condition = new self();
        // Course completion info.
        $completion = new \completion_info($course);

        // Get all the notification instance configures the suppress with this session.
        $notifications = self::get_acitivty_notifications($cm->instance);
        // print_object($cm->instance);exit;
        foreach ($notifications as $notification) {
            // Get the notification suppres module ids.
            $additional = $notification->additional ? json_decode($notification->additional, true) : '';
            $modules = $additional['modules'] ?? '';

            if (!empty($modules)) {
                $session = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid));
                // Trigger all the instance for notifications.
                $condition->trigger_instance($notification->instanceid, $userid, $session->timestart);

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
        $sessionlike = $DB->sql_like('triggercondition', ':session');
        $sql = "SELECT * FROM {pulse_condition_overrides} WHERE status >= 1 AND $sessionlike AND $like";
        $params = ['session' => 'session', 'value' => '%"'.$id.'"%'];

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

    public static function get_session_time($notification, $instancedata) {
        global $DB;

        if (isset($instancedata->condition['session']) && $instancedata->condition['session']['status']) {
            $module = $instancedata->condition['session']['modules'];

            $existingsignup = self::get_session_data($module, $notification->userid);

            return !empty($existingsignup) ? current($existingsignup)->timestart : '';
        }
        return false;
    }

    public static function get_session_data($face2faceid, $userid) {
        global $DB;

        $sql = "SELECT * FROM {facetoface_signups} f2f_su
            JOIN {facetoface_sessions_dates} f2f_sd ON f2f_sd.sessionid = f2f_su.sessionid
            JOIN {facetoface_sessions} f2f_ss ON f2f_ss.id = f2f_su.sessionid
            JOIN {facetoface_signups_status} f2f_sts ON f2f_su.id = f2f_sts.signupid
            WHERE f2f_ss.facetoface = :f2fid AND f2f_su.userid = :userid AND f2f_sd.timestart > :timestart
            AND f2f_sts.statuscode >= :code AND f2f_sts.statuscode < :statuscode";

        $existingsignup = $DB->get_records_sql($sql, array(
            'f2fid' => $face2faceid, 'userid' => $userid, 'timestart' => time(), 'code' => MDL_F2F_STATUS_REQUESTED,
            'statuscode' => MDL_F2F_STATUS_NO_SHOW
        ), 0, 1);

        return $existingsignup;
    }

}

