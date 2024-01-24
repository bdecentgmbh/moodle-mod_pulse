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
 * Conditions - Pulse condition class for the "Session Completion".
 *
 * @package   pulsecondition_session
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulsecondition_session;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/facetoface/lib.php');

use mod_pulse\automation\condition_base;

/**
 * Pulse automation session condition form and basic details.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Name of the session module.
     * @var string
     */
    const MODNAME = 'facetoface';

    /**
     * Include data to action.
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['session'] = get_string('sessionbooking', 'pulsecondition_session');
    }

    /**
     * Loads the form elements for session condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('sessionbooking', 'pulsecondition_session');

        $mform->addElement('select', 'condition[session][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[session][status]', 'sessionbooking', 'pulsecondition_session');

        $courseid = $forminstance->get_customdata('courseid') ?? '';

        // Include the suppress session settings for the instance.
        $list = [];
        $activities = get_all_instances_in_courses(static::MODNAME, [$courseid => $courseid]);
        array_map(function($value) use (&$list) {
            $list[$value->id] = format_string($value->name);
        }, $activities);

        $mform->addElement('autocomplete', 'condition[session][modules]',
                get_string('sessionmodule', 'pulsecondition_session'), $list);
        $mform->hideIf('condition[session][modules]', 'condition[session][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[session][modules]', 'sessionmodule', 'pulsecondition_session');

        // TODO: add this revention of override checkbox to dynamic instead of specific in each plugin.
        $mform->addElement('hidden', 'override[condition_session_modules]', 1);
        $mform->setType('override[condition_session_modules]', PARAM_RAW);
    }

    /**
     * Find the users is booked the session.
     *
     * @param stdclass $instancedata
     * @param int $userid
     * @param \completion_info $completion
     * @return bool
     */
    public function is_user_completed($instancedata, $userid, \completion_info $completion=null) {
        global $DB;

        // Get the notification suppres module ids.
        $additional = $instancedata->condition['session'] ?? [];
        $modules = $additional['modules'] ?? '';
        if (!empty($modules)) {
            $result = [];

            $sql = "SELECT count(*) FROM {facetoface_signups} f2f_su
            JOIN {facetoface_sessions} f2f_ss ON f2f_ss.id = f2f_su.sessionid
            WHERE f2f_ss.facetoface = :f2fid AND f2f_su.userid = :userid";

            $existingsignup = $DB->count_records_sql($sql, array('f2fid' => $modules, 'userid' => $userid));
            return ($existingsignup) ? true : false;
        }
        // Not configured any session modules.
        return true;
    }

    /**
     * Event observer to trigger actions when the user is signup to the session.
     *
     * @param stdclass $eventdata
     * @return bool
     */
    public static function signup_success($eventdata) {
        global $DB;

        // Event data.
        $data = $eventdata->get_data();
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
        $notifications = self::get_session_notifications($cm->instance);

        foreach ($notifications as $notification) {
            // Get the notification suppres module ids.
            $additional = $notification->additional ? json_decode($notification->additional, true) : '';
            $modules = $additional['modules'] ?? '';

            if (!empty($modules)) {
                $session = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid));
                // Trigger all the instance for notifications.
                $condition->trigger_instance($notification->instanceid, $userid, $session->timestart);
            }
        }

        return true;
    }

    /**
     * Fetch the list of menus which is used the triggered ID in the access rules for the given method.
     *
     * Find the menus which contains the given ID in the access rule (Role or cohorts).
     *
     * @param int $id ID of the triggered method, Role or cohort id.
     * @return array
     */
    public static function get_session_notifications($id) {
        global $DB;

        $like = $DB->sql_like('co.additional', ':value'); // Like query to fetch the instances assigned this module.
        $sessionlike = $DB->sql_like('pat.triggerconditions', ':session');

        $sql = "SELECT *, ai.id as id, ai.id as instanceid FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
        LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'session'
        WHERE $like AND (co.status > 0 OR $sessionlike)";
        // Params.
        $params = ['session' => '%"session"%', 'value' => '%"'.$id.'"%'];

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

    /**
     * Gets the session time for the notification.
     *
     * @param object $notification The notification data.
     * @param object $instancedata The instance data.
     * @return mixed The session start time or false if not found.
     */
    public static function get_session_time($notification, $instancedata) {
        global $DB;

        if (isset($instancedata->condition['session']) && $instancedata->condition['session']['status']
            && isset($instancedata->condition['session']['modules'])) {
            $module = $instancedata->condition['session']['modules'];
            $existingsignup = self::get_session_data($module, $notification->userid);
            return !empty($existingsignup) ? current($existingsignup)->timestart : '';
        }
        return false;
    }

    /**
     * Get session data for a specific face-to-face ID and user ID.
     *
     * @param int $face2faceid The face-to-face ID.
     * @param int $userid The user ID.
     * @return array|null An array of session data or null if not found.
     */
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
