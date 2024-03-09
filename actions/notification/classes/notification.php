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
 * Notification pulse action - Create and Manage notifications.
 *
 * This Controller create a schedule for users, verify their availability based on conditions.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaction_notification;

use book;
use DateTime;
use stdClass;
use moodle_url;
use html_writer;
use pulseaction_notification\task\notify_users;
use mod_pulse\automation\helper;
use mod_pulse\automation\instances;
use mod_pulse\helper as pulsehelper;
use mod_pulse\plugininfo\pulseaction;
use tool_dataprivacy\form\context_instance;

/**
 * Notification helper, Manage user schedules CRUD.
 */
class notification {

    /**
     * Represents a course teacher as type of notification sender.
     * @var int
     */
    const SENDERCOURSETEACHER = 1;

    /**
     * Represents a group teacher as type of notification sender.
     * @var int
     */
    const SENDERGROUPTEACHER = 2;

    /**
     * Represents a tenent role as type of notification sender.
     * @var int
     */
    const SENDERTENANTROLE = 3;

    /**
     * Represents a custom email as type of notification sender.
     * @var int
     */
    const SENDERCUSTOM = 4;

    /**
     * Represents a notification interval is once.
     * @var int
     */
    const INTERVALONCE = 1; // Once.

    /**
     * Represents a notification interval is Daily.
     * @var int
     */
    const INTERVALDAILY = 2; // Daily.

    /**
     * Represents a notification interval is weekly.
     * @var int
     */
    const INTERVALWEEKLY = 3; // Weekly.

    /**
     * Represents a notification interval is monthly.
     * @var int
     */
    const INTERVALMONTHLY = 4; // Montly.

    /**
     * Represents there is no delay to send the notification.
     * @var int
     */
    const DELAYNONE = 0;

    /**
     * Represents a delay before send the notifications.
     * @var int
     */
    const DELAYBEFORE = 1;

    /**
     * Represents a delay after some time to send the notifications.
     * @var int
     */
    const DELAYAFTER = 2;

    /**
     * Represents a length of the dynamic content is teaser.
     * @var int
     */
    const LENGTH_TEASER = 1;

    /**
     * Represents the dynamic content included the link to access the desired module.
     * @var int
     */
    const LENGTH_LINKED = 2;

    /**
     * Represents there is not links included with dynamic content.
     * @var int
     */
    const LENGTH_NOTLINKED = 3;

    /**
     * Represents the content of the dynamic module is only for placeholder.
     * @var int
     */
    const DYNAMIC_PLACEHOLDER = 0;

    /**
     * Represents the description of the dynamic module is included in the notification.
     * @var int
     */
    const DYNAMIC_DESCRIPTION = 1;

    /**
     * Represents the content of the dynamic module is included in the notification.
     * @var int
     */
    const DYNAMIC_CONTENT = 2;

    /**
     * Represents the notification schedule status is failed.
     * @var int
     */
    const STATUS_FAILED = 0;

    /**
     * Represents the notification schedule status is disabled.
     * @var int
     */
    const STATUS_DISABLED = 1;

    /**
     * Represents the notification schedule status is queued.
     * @var int
     */
    const STATUS_QUEUED = 2;

    /**
     * Represents the notification schedule status is sent.
     * @var int
     */
    const STATUS_SENT = 3;

    /**
     * Represents the user completed the suppress module.
     * @var int
     */
    const SUPPRESSREACHED = 1;

    /**
     * The record of the notification instance with templates and general conditions.
     *
     * @var stdclass
     */
    protected $instancedata;

    /**
     * The merged notification data based on instance overrides.
     *
     * @var stdclass
     */
    protected $notificationdata;

    /**
     * The ID of the action notification table.
     * @var int
     */
    protected $notificationid; // Notification table id.

    /**
     * Create the instance of the notification controller.
     *
     * @param int $notificationid Notification instance record id (pulseaction_notification_ins) NOT autoinstanceid.
     * @return notification
     */
    public static function instance($notificationid) {
        static $instance;

        if (!$instance || ($instance && $instance->notificationid != $notificationid)) {
            $instance = new self($notificationid);
        }

        return $instance;
    }

    /**
     * Contructor for this notification controller.
     *
     * @param int $notificationid  Notification table id.
     */
    protected function __construct(int $notificationid) {
        $this->notificationid = $notificationid;
    }

    /**
     * Create the notification instance and set the data to this class.
     *
     * @return void
     */
    protected function create_instance_data() {
        global $DB;

        $notification = $DB->get_record('pulseaction_notification_ins', ['id' => $this->notificationid]);

        if (empty($notification)) {
            throw new \moodle_exception('notificationinstancenotfound', 'pulse');
        }
        $instance = instances::create($notification->instanceid);
        $autoinstance = $instance->get_instance_data();

        $notificationdata = $autoinstance->actions['notification'];

        unset($autoinstance->actions['notification']); // Remove actions.

        $this->set_notification_data($notificationdata, $autoinstance);
    }

    /**
     * Set the notification data to global. Decode and do other structure updates for the data before setup.
     *
     * @param stdclass $notificationdata Contains notification data.
     * @param stdclass $instancedata Contains other than actions.
     * @return void
     */
    public function set_notification_data($notificationdata, $instancedata) {
        // Set the notification data.
        $notificationdata = (object) $notificationdata;
        $this->notificationdata = $this->update_data_structure($notificationdata);

        // Set the instance data.
        $instancedata = (object) $instancedata;
        // Instance not contains course then include course.
        if (!isset($instancedata->course)) {
            $instancedata->course = get_course($instancedata->courseid);
        }
        $this->instancedata = $instancedata;
    }

    /**
     * Decode the encoded json values to array, to further uses.
     *
     * @param stdclass $actiondata
     * @return stdclass $actiondata Updated action data.
     */
    public function update_data_structure($actiondata) {

        $actiondata->recipients = is_array($actiondata->recipients)
            ? $actiondata->recipients : json_decode($actiondata->recipients);

        $actiondata->bcc = is_array($actiondata->bcc) ? $actiondata->bcc : json_decode($actiondata->bcc);
        $actiondata->cc = is_array($actiondata->cc) ? $actiondata->cc : json_decode($actiondata->cc);

        $actiondata->notifyinterval = is_array($actiondata->notifyinterval)
            ? $actiondata->notifyinterval : json_decode($actiondata->notifyinterval, true);

        return $actiondata;
    }

    /**
     * Generate the data set for the user to create schedule for this instance.
     *
     * @param int $userid ID of the user to create schedule.
     * @return array $record Record to insert into schdeule.
     */
    protected function generate_schedule_record(int $userid) {

        $record = [
            'instanceid' => $this->notificationdata->instanceid,
            'userid' => $userid,
            'type' => $this->notificationdata->notifyinterval['interval'],
            'status' => self::STATUS_QUEUED,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        return $record;
    }

    /**
     * Insert the schedule to database, verify if the schedule is already in queue then override the schedule with given record.
     *
     * @param stdclass $data
     * @param bool $newschedule
     * @return int Inserted schedule ID.
     */
    protected function insert_schedule($data, $newschedule=false) {
        global $DB;

        $sql = 'SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)';

        if ($record = $DB->get_record_sql($sql, [
                'instanceid' => $data['instanceid'], 'userid' => $data['userid'], 'disabledstatus' => self::STATUS_DISABLED,
                'queued' => self::STATUS_QUEUED,
            ])) {

            $data['id'] = $record->id;
            // Update the status to enable for notify.
            $DB->update_record('pulseaction_notification_sch', $data);

            return $record->id;
        }

        // Dont create new schedule for already notified users until is not new schedule.
        // It prevents creating new record for user during the update of instance interval.
        if (!$newschedule && $DB->record_exists('pulseaction_notification_sch', [
            'instanceid' => $data['instanceid'], 'userid' => $data['userid'], 'status' => self::STATUS_SENT,
        ])) {
            return false;
        }

        return $DB->insert_record('pulseaction_notification_sch', $data);
    }

    /**
     * Disable the queued schdule of the given user.
     *
     * @param int $userid
     * @return void
     */
    protected function disable_user_schedule($userid) {
        global $DB;

        $sql = "SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)";

        $params = [
            'instanceid' => $this->notificationdata->instanceid, 'userid' => $userid, 'disabledstatus' => self::STATUS_DISABLED,
            'queued' => self::STATUS_QUEUED,
        ];

        if ($record = $DB->get_record_sql($sql, $params)) {
            $DB->set_field('pulseaction_notification_sch', 'status', self::STATUS_DISABLED, ['id' => $record->id]);
        }
    }

    /**
     * Remove the queued and disabled schedules of this user.
     *
     * @param int $userid
     * @return void
     */
    public function remove_user_schedules($userid) {
        global $DB;

        $sql = "SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)";

        $params = [
            'instanceid' => $this->notificationdata->instanceid, 'userid' => $userid, 'disabledstatus' => self::STATUS_DISABLED,
            'queued' => self::STATUS_QUEUED,
        ];

        if ($record = $DB->get_record_sql($sql, $params)) {
            $DB->delete_records('pulseaction_notification_sch', ['id' => $record->id]);
        }
    }

    /**
     * Get the current schedule created for the user related to specific instance.
     *
     * @param stdclass $data Data with instance id and user id.
     * @return stdclass|null Record of the current schedule.
     */
    protected function get_schedule($data) {
        global $DB;

        if ($record = $DB->get_record('pulseaction_notification_sch', [
            'instanceid' => $data->instanceid, 'userid' => $data->userid,
        ])) {
            return $record;
        }

        return false;
    }

    /**
     * Find the sent time of the last schedule to the user for the specific instance.
     *
     * @param int $userid
     * @return int|null Time of the last schedule notified to the user for the specific instance
     */
    protected function find_last_notifiedtime($userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;

        // Get last notified schedule for this instance to the user.
        $condition = ['instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT];
        $records = $DB->get_records('pulseaction_notification_sch', $condition, 'id DESC', '*', 0, 1);

        return !empty($records) ? current($records)->notifiedtime : '';
    }

    /**
     * Find a count of the schedules sent to the user for the current notification instance.
     *
     * @param int $userid ID of the user to fetch the counts
     * @return int|null Count of the schedules sent to the user
     */
    protected function find_notify_count($userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;

        // Get last notified schedule for this instance to the user.
        $condition = ['instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT];
        $records = $DB->get_records('pulseaction_notification_sch', $condition, 'id DESC', '*', 0, 1);

        return !empty($records) ? current($records)->notifycount : '';
    }

    /**
     * Verify the user is already notified for this instance. It verify the lastrun is empty for the user record.
     *
     * Note: Use this method to verify the instance with interval once.
     *
     * @param int $userid
     * @return bool
     */
    protected function is_user_notified(int $userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;
        $condition = ['instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT];
        if ($record = $DB->get_record('pulseaction_notification_sch', $condition)) {
            return $record->notifiedtime != null ? true : false;
        }
        return false;
    }

    /**
     * Remove the schdeduled notifications for this instance.
     *
     * @param int $status
     * @return void
     */
    protected function remove_schedules($status=self::STATUS_SENT) {
        global $DB;

        $DB->delete_records('pulseaction_notification_sch', ['instanceid' => $this->instancedata->id, 'status' => $status]);
    }

    /**
     * Disable the queued schdule for all users.
     *
     * @return void
     */
    protected function disable_schedules() {
        global $DB;

        $params = [
            'instanceid' => $this->notificationdata->instanceid,
            'status' => self::STATUS_QUEUED,
        ];

        // Disable the queued schedules for this instance.
        $DB->set_field('pulseaction_notification_sch', 'status', self::STATUS_DISABLED, $params);

    }

    /**
     * Removes the current queued schedules and recreate the schedule for all the qualified users.
     *
     * @return void
     */
    public function recreate_schedule_forinstance() {
        // Remove the current queued schedules.
        $this->create_instance_data();

        $this->remove_schedules(self::STATUS_QUEUED);
        // Create the schedules for all users.
        $this->create_schedule_forinstance();
    }

    /**
     * Create schdule for the instance.
     *
     * @param bool $newenrolment Is the schedule for new enrolments.
     * @return void
     */
    public function create_schedule_forinstance($newenrolment=false) {
        // Generate the notification instance data.
        if (empty($this->instancedata)) {
            $this->create_instance_data();
        }

        // Confirm the instance is not disabled.
        if (!$this->instancedata->status) {
            $this->disable_schedules();
            return false;
        }

        // Course context.
        $context = \context_course::instance($this->instancedata->courseid);
        // Roles to receive the notifications.
        $roles = $this->notificationdata->recipients;
        if (empty($roles)) {
            // No roles are defined to recieve notifications. Remove the schedules for this instance.
            $this->remove_schedules();
            return true; // No roles are defined to recieve notifications. Break the schedule creation.
        }
        // Get the users for this receipents roles.
        $users = $this->get_users_withroles($roles, $context);
        foreach ($users as $userid => $user) {
            $suppressreached = notify_users::is_suppress_reached(
                $this->notificationdata, $user->id, $this->instancedata->course, null);
            if ($suppressreached) {
                continue;
            }
            $this->create_schedule_foruser($user->id, null, 0, null, $newenrolment);
        }

        return true;
    }

    /**
     * Verfiy the current instance configured any conditions.
     *
     * @return bool if configured any conditions return true otherwise returns flase.
     */
    protected function verfiy_instance_contains_condition() {

        if (!isset($this->instancedata->condition)) {
            return false;
        }

        // Verify the instance contains any enabled conditions.
        foreach ($this->instancedata->condition as $condition => $values) {
            if ($values['status']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create schedule for the user.
     *
     * @param int $userid
     * @param string $lastrun
     * @param integer $notifycount
     * @param int $expectedruntime Timestamp of the time to run.
     * @param bool $isnewuser
     * @param bool $newschedule
     * @return int ID of the created schedule.
     */
    public function create_schedule_foruser($userid, $lastrun='', $notifycount=0, $expectedruntime=null,
        $isnewuser=false, $newschedule=false) {

        if (empty($this->instancedata)) {
            $this->create_instance_data();
        }

        // Instance should be configured with any of conditions. Otherwise stop creating instance (PLS-637).
        // Verify the user passed the instance condition.
        if (!$this->verfiy_instance_contains_condition() || !instances::create($this->notificationdata->instanceid)
            ->find_user_completion_conditions($this->instancedata->condition, $this->instancedata, $userid, $isnewuser)) {
            // Remove the user condition.
            $this->disable_user_schedule($userid);
            return true;
        }

        // TODO: Verify it realy need to verify the suppress reached status.

        $notifycount = $notifycount ?: $this->find_notify_count($userid);
        // Verify the Limit is reached, if 0 then its unlimited.
        if ($this->notificationdata->notifylimit > 0 && ($notifycount >= $this->notificationdata->notifylimit)) {
            return false;
        }

        // Notification interval is once per user, it already notified to the user. break the trigger here.
        if ($this->notificationdata->notifyinterval['interval'] == self::INTERVALONCE && $this->is_user_notified($userid)) {
            return true;
        }

        $lastrun = $lastrun ?: $this->find_last_notifiedtime($userid);

        // Generate the schedule record.
        $data = $this->generate_schedule_record($userid);

        $data['notifycount'] = $notifycount;
        // ...# Find the next run.
        $nextrun = $this->generate_the_scheduletime($userid, $lastrun, $expectedruntime);
        // Include the next run to schedule.
        $data['scheduletime'] = $nextrun;
        // Insert the new schedule or update schedule.
        $scheduleid = $this->insert_schedule($data, $newschedule);

        return $scheduleid;
    }

    /**
     * Generate the schedule time for this notification.
     *
     * @param int $userid
     * @param int $lastrun
     * @param int $expectedruntime
     * @return int
     */
    protected function generate_the_scheduletime($userid, $lastrun=null, $expectedruntime=null) {
        global $DB;

        $data = $this->notificationdata;
        $data->userid = $userid;

        $now = new DateTime('now', \core_date::get_server_timezone_object());

        if ($expectedruntime) {
            $expectedruntime = $now->setTimestamp($expectedruntime);
        }

        $nextrun = $expectedruntime ?: $now;
        if (!empty($lastrun)) {
            $lastrun = ($lastrun instanceof DateTime)
                ?: (new DateTime('now', \core_date::get_server_timezone_object()))->setTimestamp($lastrun);
            $nextrun = $lastrun;
        }

        $interval = $data->notifyinterval['interval'];

        switch ($interval) {

            case self::INTERVALDAILY:
                $time = $data->notifyinterval['time'];
                $nextrun->modify('+1 day'); // TODO: Change this to Dateinterval().
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;

            case self::INTERVALWEEKLY:
                $day = $data->notifyinterval['weekday'];
                $time = $data->notifyinterval['time'];
                $nextrun->modify("Next ".$day);
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;

            case self::INTERVALMONTHLY:
                $monthdate = $data->notifyinterval['monthdate'];
                if ($monthdate != 31) { // If the date is set as 31 then use the month end.
                    $nextrun->modify('first day of next month');
                    $date = $data->notifyinterval['monthdate']
                        ? $data->notifyinterval['monthdate'] - 1 : $data->notifyinterval['monthdate'];
                    $nextrun->modify("+$date day");
                } else {
                    $nextrun->modify('last day of next month');
                }

                $time = $data->notifyinterval['time'] ?? '0:00';
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;

            case self::INTERVALONCE:
                $nextrun = $expectedruntime ?: $now;
                break;
        }

        // Add limit of available.
        if ($data->notifydelay == self::DELAYAFTER) {

            // QuickFIX - PLS-726.
            // When the instances are created/updated, creates the schedules directly. 
			// With is method expected run time are not included.
            // Other conditions are not contains any specific usecases.
            // Verify and include the expected runtime from sessions only.
            if (!$expectedruntime && method_exists('\pulsecondition_session\conditionform', 'get_session_time')) {
                // Confirm any f2f module added in condition.
                $sessionstarttime = \pulsecondition_session\conditionform::get_session_time($data, $this->instancedata);
                if (!empty($sessionstarttime)) {
                    $nextrun->setTimestamp($sessionstarttime);
                }
            }

            $delay = $data->delayduration;
            $nextrun->modify("+ $delay seconds");

        } else if ($data->notifydelay == self::DELAYBEFORE) {
            $delay = $data->delayduration;

            if ($expectedruntime) {
                // Session condition only send the expected runtime.
                // Reduce the delay directly from the expected runtime.
                $nextrun->modify("- $delay seconds");

            } else if (method_exists('\pulsecondition_session\conditionform', 'get_session_time')) {
                // Confirm any f2f module added in condition.
                $sessionstarttime = \pulsecondition_session\conditionform::get_session_time($data, $this->instancedata);

                if (!empty($sessionstarttime)) {
                    $nextrun->setTimestamp($sessionstarttime);
                    $nextrun->modify("- $delay seconds");
                }
            }
        }

        return $nextrun->getTimestamp();
    }

    /**
     * Get the users assigned in the roles.
     *
     * @param array $roles Role ids to fetch
     * @param \context $context
     * @param int $childuserid
     * @return array List of the users.
     */
    protected function get_users_withroles(array $roles, $context, $childuserid=null) {
        global $DB;

        // TODO: Cache the role users.
        if (empty($roles)) {
            return [];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'rle');

        // TODO: Define user fields, never get entire fields.
        $rolesql = "SELECT ra.id as assignid, u.*, ra.roleid FROM {role_assignments} ra
        JOIN {user} u ON u.id = ra.userid
        JOIN {role} r ON ra.roleid = r.id
        LEFT JOIN {role_names} rn ON (rn.contextid = :ctxid AND rn.roleid = r.id) ";

        // Fetch the parent users related to the child user.
        $childcontext = '';
        if ($childuserid) {
            $rolesql .= " JOIN {context} uctx ON uctx.instanceid=:childuserid AND contextlevel=" . CONTEXT_USER . " ";
            $childcontext = " OR ra.contextid = uctx.id ";
            $inparams['childuserid'] = $childuserid;
        }

        $rolesql .= " WHERE (ra.contextid = :ctxid2 $childcontext) AND ra.roleid $insql ORDER BY u.id";

        $params = ['ctxid' => $context->id, 'ctxid2' => $context->id] + $inparams;

        $users = $DB->get_records_sql($rolesql, $params);

        return $users;
    }

    /**
     * Build the notification content.
     *
     * @param stdClass|null $cm Course module
     * @param \context $context
     * @param array $overrides
     *
     * @return string Notification content.
     */
    public function build_notification_content(?stdClass $cm=null, $context=null, $overrides=[]) {
        global $CFG, $DB;

        $syscontext = \context_system::instance();

        $headercontent = $this->notificationdata->headercontent;
        $staticcontent = $this->notificationdata->staticcontent;
        $footercontent = $this->notificationdata->footercontent;

        // Rewrite the plugin url for files in editors.
        foreach (['headercontent', 'staticcontent', 'footercontent'] as $editor) {

            $content = $$editor;

            $field = 'pulsenotification_'.$editor;
            $prefix = (isset($overrides[$editor]) || isset($overrides[$field]) || isset($overrides[$field.'_editor']))
                ? '_instance' : '';
            $id = (isset($overrides[$editor]) || isset($overrides[$field]) || isset($overrides[$field.'_editor']))
                ? $this->notificationdata->instanceid : $this->instancedata->templateid;

            $$editor = file_rewrite_pluginfile_urls(
                $content, 'pluginfile.php', $syscontext->id,
                'mod_pulse', $field.$prefix, $id
            );
        }

        // Include the dynamic contents.
        $dynamiccontent = $this->notificationdata->dynamiccontent;
        if ($dynamiccontent) {
            if ($cm == null) {
                $module = get_coursemodule_from_id('', $dynamiccontent);
                $cm = (object) [
                    'instance' => $module->instance,
                    'modname' => $module->modname,
                    'id' => $module->id,
                ];
            }

            $modcontext = \context_module::instance($dynamiccontent);

            $staticcontent .= self::generate_dynamic_content(
                $this->notificationdata->contenttype,
                $this->notificationdata->contentlength,
                $this->notificationdata->chapterid ?? 0,
                $modcontext,
                $cm
            ); // Concat the dynamic content after static content.
        }

        $finalcontent = $headercontent . $staticcontent . $footercontent;

        return format_text($finalcontent, FORMAT_HTML, ['noclean' => true, 'overflowdiv' => true]);
    }

    /**
     * Gernerate the content based on dynamic module to attach with the notification content.
     *
     * @param string $contenttype
     * @param int $contentlength
     * @param int $chapterid
     * @param \context $context
     * @param stdclass $cm
     *
     * @return string
     */
    public static function generate_dynamic_content($contenttype, $contentlength, $chapterid, $context, $cm) {
        global $CFG, $DB;

        // Include module libarary files.
        require_once($CFG->dirroot.'/lib/modinfolib.php');
        require_once($CFG->dirroot.'/mod/book/lib.php');
        require_once($CFG->libdir.'/filelib.php');

        // Content type is placholder, no need to include the content.
        if ($contenttype == self::DYNAMIC_PLACEHOLDER) {
            return '';
        }

        if ($contenttype == self::DYNAMIC_CONTENT && in_array($cm->modname, ['book', 'page'])) {

            if ($cm->modname == 'book') {
                $chapter = $DB->get_record('book_chapters', ['id' => $chapterid, 'bookid' => $cm->instance]);
                $chaptertext = \file_rewrite_pluginfile_urls(
                        $chapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $chapter->id);

                $content = format_text($chaptertext, $chapter->contentformat, ['noclean' => true, 'overflowdiv' => true]);
                $link = new moodle_url('/mod/book/view.php', ['id' => $cm->id, 'chapterid' => $chapterid]);
            } else if ($cm->modname == 'page') {
                $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);

                $content = file_rewrite_pluginfile_urls(
                        $page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
                $link = new moodle_url('/mod/page/view.php', ['id' => $cm->id]);
            }

        } else {
            // TODO: Need to cache module intro content.
            $activity = $DB->get_record("$cm->modname", ['id' => $cm->instance]);
            $content = format_module_intro($cm->modname, $activity, $cm->id, true);
            $link = new moodle_url("/mod/$cm->modname/view.php", ['id' => $cm->id]);
        }

        // Verify the contnet length.
        switch ($contentlength) {

            case self::LENGTH_TEASER:
                preg_match('/<p>(.*?)<\/p>/s', $content, $match);
                $content = $match[0] ?? $content;

                $content .= html_writer::link($link, get_string('readmore', 'pulseaction_notification'));
                break;

            case self::LENGTH_LINKED:
                $content .= html_writer::link($link, get_string('readmore', 'pulseaction_notification'));
                break;
        }

        return $content;
    }

    /**
     * Generate the details of the notification to send.
     *
     * @param stdclass $moddata
     * @param stdclass $user
     * @param \context $context
     * @param array $notificationoverrides
     * @return stdclass Basic details to send notification.
     */
    public function generate_notification_details($moddata, $user, $context, $notificationoverrides=[]) {
        global $USER;

        // Find the cc and bcc users for this schedule.
        $roles = array_merge($this->notificationdata->cc, $this->notificationdata->bcc);

        // Get the users for this bcc and cc roles.
        $roleusers = $this->get_users_withroles($roles, $context, $user->id);

        // Filter the cc users for this instance.
        $cc = $this->notificationdata->cc;
        $ccusers = array_filter($roleusers, function($value) use ($cc) {
            return in_array($value->roleid, $cc);
        });

        // Filter the bcc users for this instance.
        $bcc = $this->notificationdata->bcc;
        $bccusers = array_filter($roleusers, function($value) use ($bcc) {
            return in_array($value->roleid, $bcc);
        });

        // Set the recepient as session user for format content.
        $olduser = $USER;
        \core\session\manager::set_user($user);

        // Use the current user language to filter content.
        if ($user->lang != current_language()) {
            $oldforcelang = force_current_language($user->lang);
        }

        $result = [
            'recepient' => (object) $user,
            'cc'        => array_map(fn($user) => [$user->email, fullname($user)], $ccusers),
            'bcc'       => array_map(fn($user) => [$user->email, fullname($user)], $bccusers),
            'subject'   => format_string($this->notificationdata->subject),
            'content'   => $this->build_notification_content($moddata, $context, $notificationoverrides),
        ];

        // After format the message and subject return back to previous lang.
        if (isset($oldforcelang)) {
            force_current_language($oldforcelang);
            unset($oldforcelang);
        }
        // Return to normal current session user.
        \core\session\manager::set_user($olduser);

        return (object) $result;
    }

    /**
     * Get the tenant role sender user.
     *
     * @param stdclass $scheduledata
     * @return stdclass
     */
    public function get_tenantrole_sender($scheduledata) {
        // TODO: Tenant based sender fetch goes here.
        return (object) [];
    }

    /**
     * Find the sender users for the course context, fetched the teachers based on group assignment.
     *
     * @param \context $coursecontext
     * @param int $groupid
     * @return stdclass
     */
    protected static function get_sender_users($coursecontext, $groupid) {

        $withcapability = 'pulseaction/notification:sender';
        $sender = get_enrolled_users(
            $coursecontext,
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
     * Load book chapters.
     *
     * @param int $cmid Course module id.
     * @return array
     */
    public static function load_book_chapters(int $cmid) {
        global $DB;
        $mod = get_coursemodule_from_id('', $cmid);

        $list = '';
        if ($mod->modname == 'book') {
            $chapters = $DB->get_records('book_chapters', ['bookid' => $mod->instance]);
            return $chapters;
        }
        return $list;
    }

    /**
     * Get the status of the schedule.
     *
     * @param int $value
     * @param stdclass $row
     * @return string
     */
    public static function get_schedule_status($value, $row) {

        if ($value == self::STATUS_DISABLED) {
            return get_string('onhold', 'pulseaction_notification');
        } else if ($value == self::STATUS_QUEUED) {
            if (!$row->instancestatus) {
                return get_string('onhold', 'pulseaction_notification');
            }
            return get_string('queued', 'pulseaction_notification');
        } else if ($value == self::STATUS_SENT) {
            return get_string('sent', 'pulseaction_notification');
        } else {
            return get_string('failed', 'pulseaction_notification');
        }
    }

    /**
     * Get the schedule subject to display in the reports.
     *
     * @param string $value Subject
     * @param stdclass $row
     * @return string
     */
    public static function get_schedule_subject($value, $row) {
        global $DB;

        $value = $row->instancesubject ?: $row->templatesubject; // Use templates subject if instance subject doesn't overrides.
        $sender = \core_user::get_support_user();
        $courseid = $DB->get_field('pulse_autoinstances', 'courseid', ['id' => $row->instanceid]);
        $user = (object) \core_user::get_user($row->userid);
        $course = get_course($courseid ?? SITEID);

        list($subject, $messagehtml) = \mod_pulse\helper::update_emailvars('', $value, $course, $user, null, $sender);

        return $subject . html_writer::link('javascript:void(0);', '<i class="fa fa-info"></i>', [
            'class' => 'pulse-automation-info-block',
            'data-target' => 'view-content',
            'data-instanceid' => $row->instanceid,
            'data-userid' => $row->userid,
        ]);
    }

    /**
     * Get the list of modules data for the placholders. includes the metadata fields.
     *
     * @param array $modules List of modules.
     * @return array
     */
    public static function get_modules_data($modules) {
        global $DB, $CFG;

        if (file_exists($CFG->dirroot.'/local/metadata/lib.php')) {
            require_once($CFG->dirroot.'/local/metadata/lib.php');
        }

        $list = [];
        foreach ($modules as $modname => $instances) {

            $tablename = $DB->get_prefix().$modname;
            list($insql, $inparams) = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED, 'md');

            $sql = "SELECT md.*, cm.id as cmid FROM $tablename md
            JOIN {modules} m ON m.name = '$modname'
            JOIN {course_modules} cm ON cm.instance = md.id AND cm.module = m.id
            WHERE md.id $insql";

            $records = $DB->get_records_sql($sql, $inparams);

            foreach ($records as $modid => $mod) {
                $mod->type = $modname;

                if (isset($list[$modname][$modid])) {
                    continue;
                }

                if (function_exists('local_metadata_load_data')) {
                    $newmod = (object) ['id' => $mod->cmid];
                    local_metadata_load_data($newmod, CONTEXT_MODULE);
                    unset($newmod->cmid);
                    foreach ($newmod as $name => $value) {
                        $key = str_replace('local_metadata_field_', 'metadata', $name);
                        $mod->$key = $value;
                    }
                }

                $list[$modname][$modid] = $mod;
            }
        }

        return $list;
    }
}
