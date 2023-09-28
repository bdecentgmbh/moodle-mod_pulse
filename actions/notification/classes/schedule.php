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
 * Notification pulse action - Send the scheduled notifications.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaction_notification;

use mod_pulse\automation\helper;
use mod_pulse\automation\instances;
use mod_pulse\helper as pulsehelper;
use stdClass;

/**
 * Notification pulse action - Send the scheduled notifications.
 */
class schedule {

    /**
     * The user object of support user.
     * @var stdclass
     */
    protected $supportuser;

    /**
     * The automation instance data.
     * @var stdclass
     */
    protected $instancedata;

    /**
     * The record of notification schedule.
     * @var stdclass
     */
    protected $schedulerecord;

    /**
     * The record of entire notifcation schedule row.
     * @var stdclass
     */
    protected $schedule;

    /**
     * The course object.
     * @var stdclass
     */
    protected $course;

    /**
     * The user data object.
     * @var stdclass
     */
    protected $user;

    /**
     * Notification record.
     *
     * @var notification
     */
    protected $notification;

    /**
     * List of overrides in the notification action.
     *
     * @var array
     */
    protected $notificationoverrides;

    /**
     * Notification data processed with overrides.
     *
     * @var stdclass
     */
    protected $notificationdata;

    /**
     * Course context.
     *
     * @var \context_course
     */
    protected $coursecontext; // Context_module.

    /**
     * List of conditions enabled in this instance.
     *
     * @var array
     */
    protected $conditions;

    /**
     * Schedule constructor.
     */
    public function __construct() {
        // Support user.
        $this->supportuser = \core_user::get_support_user();

    }

    /**
     * Create a instance of this schedule controller.
     *
     * @return self
     */
    public static function instance() {

        $self = new self();
        return $self;
    }

    /**
     * Sends scheduled notifications to users.
     *
     * @param int|null $userid (Optional) The ID of the user. If provided, notifications will be sent only to this user.
     *
     * @return void
     */
    public function send_scheduled_notification($userid=null) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/pulse/automation/automationlib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/lib/moodlelib.php');

        // Genreate the SQL to fetch the list of schedules to send notification.
        // It fetch the list based on the limit.
        $schedules = $this->get_scheduled_records($userid);
        // Get the modules list for the dynamiccontent will be used in this schedule.
        // Reduce the query to fetch same modules again.
        $modules = $this->get_modules_dynamic_content($schedules);

        foreach ($schedules as $sid => $schedule) {

            // Separete the values from the record and create each as class level variable object.
            $this->build_schedule_values($schedule);

            // Verify the notification instance limit of notification is reached for this user.
            if (isset($schedule->notifiycount) && $schedule->notifiycount >= $this->notificationdata->notifylimit) {
                continue; // Limit reached break this continue to next user.
            }

            $cmdata = (object) [
                'modname' => $schedule->md_name, // Module name Book or page.
                'instance' => $schedule->cm_instance,
                'id' => $schedule->cm_id
            ];
            // Generate the details to send the notification, it contains user, cc, bcc and schedule data.
            $detail = $this->notification->generate_notification_details(
                $cmdata, $this->user, $this->coursecontext, $this->notificationoverrides);

            $sender = $this->find_sender_user();

            // Add bcc and CC to sender user custom headers.
            $sender->customheaders = [
                "Bcc: $detail->bcc\r\n",
                "Cc: $detail->cc\r\n",
            ];

            // Prepare the module data. based on dynamic content and includ the session data.
            $mod = $this->prepare_moduledata_placeholders($modules, $cmdata);

            // Check the session condition are set for this notification. if its added then load the session data for placeholders.
            $sessionin = in_array('session', (array) $this->instancedata->template->triggerconditions);
            $sessionin = ($this->schedulerecord->con_isoverridden == 1) ? $this->schedulerecord->con_status : $sessionin;
            if ($sessionin) {
                $sessionconditiondata = (object) ['modules' => json_decode($this->schedulerecord->con_additional)->modules];
                $this->include_session_data($mod, $sessionconditiondata, $this->user->id);
            }

            // Update the email placeholders.
            list($subject, $messagehtml) = pulsehelper::update_emailvars(
                $detail->content, $detail->subject, $this->course, $this->user, $mod, $sender);

            // Plain message.
            $messageplain = html_to_text($messagehtml);

            // Courseid is needed in the message api.
            $pulse = (object) ['course' => $this->course->id];
            // TODO: NOTE using notification API takes 16 queries. Direct email_to_user method will take totally 9 queries.
            // Send the notification to user.
            $messagesend = email_to_user($detail->recepient, $sender, $subject,
                $messageplain, $messagehtml, '', '', true, $sender->from ?? '');

            if ($messagesend) {
                // Update the current time as lastrun.
                // Update the lastrun and increase the limit.
                $notifiedtime = time();

                $notifycount = $this->schedule->notifycount ? $this->schedule->notifycount + 1 : 1;
                $update = [
                    'id' => $this->schedule->id,
                    'notifycount' => $notifycount,
                    'status' => notification::STATUS_SENT,
                    'notifiedtime' => $notifiedtime,
                ];

                // Generate a next runtime. Only if user has limit to receive notifications. otherwise made the nextrun null.
                // Update the schedule.
                $DB->update_record('pulseaction_notification_sch', $update);

                if ($notifycount < $this->notificationdata->notifylimit
                    && $this->notificationdata->notifyinterval['interval'] != notification::INTERVALONCE) {
                    $newschedule = true;
                    $this->notification->create_schedule_foruser(
                        $this->schedule->userid, $notifiedtime, $notifycount, null, null, $newschedule);
                }
            }
        }
    }

    /**
     * Fetch the queued schedules for the user.
     *
     * @param int|null $userid (Optional) The ID of the user. If provided, schedules are fetched and inti the notification sent.
     *
     * @return void
     */
    protected function get_scheduled_records($userid=null) {
        global $DB;

        $select[] = 'ns.id AS id'; // Set the schdule id as unique column.

        // Get columns not increase table queries.
        // TODO: Fetch only used columns. Fetching all the fields in a query will make double the time of query result.
        $tables = [
            'ns' => $DB->get_columns('pulseaction_notification_sch'),
            'ai' => $DB->get_columns('pulse_autoinstances'),
            'pat' => $DB->get_columns('pulse_autotemplates'),
            'pati' => $DB->get_columns('pulse_autotemplates_ins'),
            'ni' => $DB->get_columns('pulseaction_notification_ins'),
            'an' => $DB->get_columns('pulseaction_notification'),
            'con' => array_fill_keys(["status", "additional", "isoverridden"], ""),
            'ue' => $DB->get_columns('user'),
            'c' => $DB->get_columns('course'),
            'ctx' => $DB->get_columns('context'),
            'cm' => array_fill_keys(["id", "course", "module", "instance"], ""), // Make the values as keys.
            'md' => array_fill_keys(['name'], "")
        ];

        foreach ($tables as $prefix => $table) {
            $columns = array_keys($table);
            // Columns.
            array_walk($columns, function(&$value, $key, $prefix) {
                $value = "$prefix.$value AS ".$prefix."_$value";
            }, $prefix);

            $select = array_merge($select, $columns);
        }
        // Final list of select columns, convert to sql mode.
        $select = implode(', ', $select);

        // Number of notification to send in this que.
        $limit = get_config('pulse', 'schedulecount') ?: 100;

        // Trigger the schedules for sepecied users.
        $userwhere = $userid ? ' AND ns.userid =:userid ' : '';
        $userparam = $userid ? ['userid' => $userid] : [];

        // Fetch the schedule which is status as 1 and nextrun not empty and not greater than now.
        $sql = "SELECT $select FROM {pulseaction_notification_sch} ns
            JOIN {pulse_autoinstances} ai ON ai.id = ns.instanceid
            JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
            JOIN {pulse_autotemplates_ins} pati ON pati.instanceid = ai.id
            JOIN {pulseaction_notification_ins} ni ON ni.instanceid = ns.instanceid
            JOIN {pulseaction_notification} an ON an.templateid = ai.templateid
            JOIN {user} ue ON ue.id = ns.userid
            JOIN {course} c ON c.id = ai.courseid
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            LEFT JOIN {pulse_condition_overrides} con ON con.instanceid = pati.instanceid AND con.triggercondition = 'session'
            LEFT JOIN {course_modules} cm ON cm.id = ni.dynamiccontent
            LEFT JOIN {modules} md ON md.id = cm.module
            JOIN (
                SELECT DISTINCT eu1_u.id, ej1_e.courseid, COUNT(ej1_ue.enrolid) AS activeenrolment
                    FROM {user} eu1_u
                    JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                    JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid)
                WHERE 1 = 1 AND ej1_ue.status = 0
                AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= :timestart)
                AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > :timeend)
                GROUP BY eu1_u.id, ej1_e.courseid
            ) active_enrols ON active_enrols.id = ue.id AND active_enrols.courseid = c.id
            WHERE ns.status = :status AND ai.status <> 0
            AND active_enrols.activeenrolment <> 0
            AND c.visible = 1
            AND c.startdate <= :startdate AND  (c.enddate = 0 OR c.enddate >= :enddate)
            AND ue.deleted = 0 AND ue.suspended = 0
            AND ns.suppressreached = 0 AND ns.scheduletime <= :current_timestamp $userwhere ORDER BY ns.timecreated ASC";

        $params = [
            'status' => notification::STATUS_QUEUED,
            'current_timestamp' => time(),
            'timestart' => time(), 'timeend' => time(),
            'startdate' => time(), 'enddate' => time()
        ] + $userparam;

        $schedules = $DB->get_records_sql($sql, $params, 0, $limit);

        return $schedules;
    }

    /**
     * Retrieves dynamic content modules for a list of schedules.
     *
     * @param array $schedules An array of schedules containing information about modules.
     *                         Each schedule should have 'md_name' (module name) and 'cm_instance' (module instance) properties.
     *
     * @return array An array of dynamic modules data.
     */
    protected function get_modules_dynamic_content($schedules) {
        // Get the dynamic modules list of all schedules.
        $dynamicmodules = [];
        foreach ($schedules as $key => $schedule) {
            if (!isset($schedule->md_name) || empty($schedule->md_name)) {
                continue;
            }
            $dynamicmodules[$schedule->md_name][] = $schedule->cm_instance;
        }

        return notification::get_modules_data($dynamicmodules);
    }

    /**
     * Builds the values for a given schedule.
     *
     * @param object $schedule The schedule object containing information about the automation instance.
     *
     * @return void
     */
    protected function build_schedule_values($schedule) {

        $this->schedulerecord = $schedule;

        // Prepare templates instance data.
        $templatedata = helper::filter_record_byprefix($schedule, 'pat');
        $templateinsdata = helper::filter_record_byprefix($schedule, 'pati');
        $templateinsdata = (object) helper::merge_instance_overrides($templateinsdata, $templatedata);
        $templateinsdata->triggerconditions = json_decode($templateinsdata->triggerconditions, true);

        // Prepare the instance data.
        $instancedata = (object) helper::filter_record_byprefix($schedule, 'ai');
        // Merge the template data to instance.
        $instancedata->template = $templateinsdata;
        unset($templateinsdata->id);
        $instancedata = (object) array_merge((array) $instancedata, (array) $templateinsdata);
        $this->instancedata = $instancedata; // Auomtaion instance data.

        if (isset($this->conditions[$instancedata->id])) {
            // Include the condition for this instance if already created for this cron use it.
            $this->instancedata->condition = $this->conditions[$instancedata->id];
        } else {
            $condition = (new instances($instancedata->id))->include_conditions_data($this->instancedata);
            $this->conditions[$instancedata->id] = $condition;
            $this->instancedata->condition = $condition;
        }

        // Schedule data.
        $this->schedule = (object) helper::filter_record_byprefix($schedule, 'ns');
        // Course data.
        $this->course = (object) helper::filter_record_byprefix($schedule, 'c');
        // User data.
        $this->user = (object) helper::filter_record_byprefix($schedule, 'ue');
        // Course context data.
        $context = (object) helper::filter_record_byprefix($schedule, 'ctx');
        // Conver the context data to moodle context.
        $this->coursecontext = \mod_pulse_context_course::create_instance_fromrecord($context);
        // Filter the notification data by its prefix.
        $notificationrecord = helper::filter_record_byprefix($schedule, 'an');
        // Filter the notification instance data by its prefix.
        $notificationinstancerecord = helper::filter_record_byprefix($schedule, 'ni');

        // Merge the notification overrides data and its notification data.
        $this->notificationdata = (object) helper::merge_instance_overrides($notificationinstancerecord, $notificationrecord);

        // Filter the notification instance overrided values list.
        $this->notificationoverrides = array_filter((array) $notificationinstancerecord, function($value) {
            return $value !== null;
        });

        // Create notification instance.
        // Set the notification instance data merge with notification and instance data.
        $this->notification = notification::instance($notificationrecord['id']);
        $this->notification->set_notification_data($this->notificationdata, $this->instancedata);

    }

    /**
     * Finds the sender user for this schedule.
     *
     * @return mixed Returns either a string with the custom sender email, or an object representing the sender user.
     */
    protected function find_sender_user() {
        // Find the sender for this schedule.
        if ($this->notificationdata->sender == notification::SENDERCUSTOM) {
            // Find the user from moodle for this custom email.
            $emailuser = \core_user::get_user_by_email($this->notificationdata->senderemail);
            // Use the custom sender email as the support user email.
            $sender = $this->notificationdata->senderemail;

            // SEnder is not found and is a custom email. then create dummy user data with custom email.
            if (is_string($sender)) {
                $replyto = $sender;
                $expsender = explode('@', $sender);
                $sender = (object) [
                    'from' => $replyto,
                    'firstname' => $expsender[0] ?? $sender, // Use the first part of the email as firstname of the user.
                    'lastname' => '',
                    'firstnamephonetic' => '',
                    'lastnamephonetic' => '',
                    'middlename' => '',
                    'alternatename' => '',
                ];
            }

        } else if ($this->notificationdata->sender == notification::SENDERTENANTROLE) {
            $sender = $this->notification->get_tenantrole_sender($this->schedulerecord);
        } else {
            // Get user groups is sender is configured as group teacher.
            $groups = ($this->notificationdata->sender == notification::SENDERGROUPTEACHER)
                ? groups_get_user_groups($this->course->id, $this->schedule->userid) : 0;

            $groupids = 0;
            if (!empty($groups)) {
                $firstgroup = current($groups);
                $groupids = current($firstgroup);
            }
            // Get the course teacher if group teacher not available it will fallback to course teacher automatically.
            $sender = (object) $this->get_sender_users($groupids);

            if (empty((array) $sender)) {
                // Sender not found then use the support user.
                $sender = $this->supportuser;
            }
        }

        return $sender;
    }

    /**
     * Retrieves the sender user(s) for a given group ID.
     *
     * @param int|array $groupid The ID of the group or an array of group IDs.
     *
     * @return stdClass|array Returns an object representing the sender user if found, or an empty array if not.
     */
    protected function get_sender_users($groupid) {

        $groupid = is_array($groupid) ? current($groupid) : $groupid;

        $withcapability = 'pulseaction/notification:sender';
        $sender = get_enrolled_users(
            $this->coursecontext,
            $withcapability,
            $groupid,
            'u.*',
            null,
            0,
            1,
            true
        );

        return !empty($sender) ? current($sender) : [];
    }

    /**
     * Prepares module data to use as placeholders in notifications.
     *
     * @param array $modules An array of dynamic content modules organized by module name and instance.
     * @param object $cmdata An object containing information about the module (modname, instance, id).
     *
     * @return stdClass Returns an object representing the module data to be used as placeholders.
     */
    public function prepare_moduledata_placeholders($modules, $cmdata) {
        global $CFG;

        // Prepare the module data to use as placeholders.
        $mod = new \stdclass;

        // Find the module data if dynamic content is configured.
        if ($modules && $this->notificationdata->dynamiccontent) {
            $mod = (object) $modules[$cmdata->modname][$cmdata->instance] ?? [];
        }

        return $mod;
    }

    /**
     * Includes session data in the module object.
     *
     * @param stdClass $mod The module object to which session data will be added.
     * @param stdClass $sessiondata An object containing session data.
     * @param int $userid The ID of the user.
     *
     * @return stdClass The modified module object with session data included.
     */
    public function include_session_data(&$mod, $sessiondata, $userid) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        $modules = $sessiondata->modules;
        $sessions = \pulsecondition_session\conditionform::get_session_data($modules, $userid);
        if (empty($sessions)) {
            $mod->session = new stdClass();
        } else {
            $finalsessiondata = new \stdclass();
            $session = current($sessions);
            $finalsessiondata->discountcode = $session->discountcode;
            $finalsessiondata->details = format_text($session->details);
            $finalsessiondata->capacity = $session->capacity;
            $finalsessiondata->normalcost = format_cost($session->normalcost);
            $finalsessiondata->discountcost = format_cost($session->discountcost);
            $finalsessiondata->link = $CFG->wwwroot . "/mod/facetoface/signup.php?s=$session->id";

            $formatedtime = facetoface_format_session_times($session->timestart, $session->timefinish, null);
            $finalsessiondata = (object) array_merge((array) $finalsessiondata, (array) $formatedtime);
            // Fetch the sessions custom fields.
            $customfields = facetoface_get_session_customfields();
            $finalsessiondata->customfield = new \stdclass();
            // Include the session custom fields.
            foreach ($customfields as $field) {
                $finalsessiondata->customfield->{$field->shortname} = facetoface_get_customfield_value(
                    $field, $session->sessionid, 'session');
            }

            $mod->session = $finalsessiondata;
        }

        return $mod;
    }
}
