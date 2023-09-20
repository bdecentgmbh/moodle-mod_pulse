<?php


namespace pulseaction_notification;

use book;
use DateTime;
use mod_pulse\automation\helper;
use mod_pulse\automation\instances;
use mod_pulse\helper as pulsehelper;
use mod_pulse\plugininfo\pulseaction;
use moodle_url;
use html_writer;
use stdClass;
use tool_dataprivacy\form\context_instance;

class notification {

    const SENDERCOURSETEACHER = '1';
    const SENDERGROUPTEACHER = '2';
    const SENDERTENANTROLE = '3';
    const SENDERCUSTOM = '4';

    // Values for interval.
    const INTERVALONCE = '1'; // Once.
    const INTERVALDAILY = '2'; // Daily.
    const INTERVALWEEKLY = '3'; // Weekly
    const INTERVALMONTHLY = '4'; // Montly.

    const DELAYNONE = '0'; // Don't send a notifcation.
    const DELAYBEFORE = '1';
    const DELAYAFTER = '2';

    const LENGTH_TEASER = '1';
    const LENGTH_LINKED = '2';
    const LENGTH_NOTLINKED = '3';

    const DYNAMIC_DESCRIPTION = '1';
    const DYNAMIC_CONTENT = '2';

    const STATUS_FAILED = 0;
    const STATUS_DISABLED = 1;
    const STATUS_QUEUED = 2;
    const STATUS_SENT = 3;

    const SUPPRESSREACHED = 1;

    protected $instancedata;

    protected $notificationdata;

    protected $notificationid; //

    /**
     * Undocumented function
     *
     * @param [type] $notificationid Notification instance record id NOT autoinstanceid.
     * @return self
     */
    public static function instance($notificationid) {
        static $instance;

        if (!$instance || ($instance && $instance->notificationid != $notificationid)) {
            $instance = new self($notificationid);
        }

        return $instance;
    }

    protected function __construct(int $notificationid) {
        $this->notificationid = $notificationid;
    }

    protected function create_instance_data() {
        global $DB;

        $notification = $DB->get_record('pulseaction_notification_ins', ['id' => $this->notificationid]);

        $instance = instances::create($notification->instanceid);
        $autoinstance = $instance->get_instance_data();

        $notificationdata = $autoinstance->actions['notification'];

        unset($autoinstance->actions['notification']); // Remove actions.

        $this->set_notification_data($notificationdata, $autoinstance);
    }

    /**
     * Undocumented function
     *
     * @param [type] $notificationdata Contains notification data.
     * @param [type] $instancedata Contains other than actions.
     * @return void
     */
    public function set_notification_data($notificationdata, $instancedata) {
        $data = (object) $notificationdata;
        $this->notificationdata = $this->update_data_structure($data);

        $data = (object) $instancedata;
        $this->instancedata = $instancedata;
    }


    public function update_data_structure($actiondata) {

        $actiondata->recipients = is_array($actiondata->recipients)
            ? $actiondata->recipients : json_decode($actiondata->recipients);

        $actiondata->bcc = is_array($actiondata->bcc) ? $actiondata->bcc : json_decode($actiondata->bcc);
        $actiondata->cc = is_array($actiondata->cc) ? $actiondata->cc : json_decode($actiondata->cc);

        $actiondata->notifyinterval = is_array($actiondata->notifyinterval)
            ? $actiondata->notifyinterval : json_decode($actiondata->notifyinterval, true);

        return $actiondata;
    }


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

    protected function insert_schedule($data, $newschedule=false) {
        global $DB;

        $sql = 'SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)';

        if ($record = $DB->get_record_sql($sql, [
                'instanceid' => $data['instanceid'], 'userid' => $data['userid'], 'disabledstatus' => self::STATUS_DISABLED,
                'queued' => self::STATUS_QUEUED
            ])) {

            $data['id'] = $record->id;
            // Update the status to enable for notify.
            $DB->update_record('pulseaction_notification_sch', $data);

            return $record->id;
        }

        // Dont create new schedule for already notified users until is not new schedule.
        // It prevents creating new record for user during the update of instance interval.
        if (!$newschedule && $DB->record_exists('pulseaction_notification_sch', [
            'instanceid' => $data['instanceid'], 'userid' => $data['userid'], 'status' => self::STATUS_SENT
        ])) {
            return false;
        }

        return $DB->insert_record('pulseaction_notification_sch', $data);
    }

    protected function disable_user_schedule($userid) {
        global $DB;

        $sql = "SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)";

        $params = [
            'instanceid' => $this->notificationdata->instanceid, 'userid' => $userid, 'disabledstatus' => self::STATUS_DISABLED,
            'queued' => self::STATUS_QUEUED
        ];

        if ($record = $DB->get_record_sql($sql, $params)) {
            // print_object($record);exit;
            $DB->set_field('pulseaction_notification_sch', 'status', self::STATUS_DISABLED, ['id' => $record->id]);
        }
    }

    public function remove_user_schedules($userid) {
        global $DB;

        $sql = "SELECT * FROM {pulseaction_notification_sch}
                WHERE instanceid = :instanceid AND userid = :userid AND (status = :disabledstatus  OR status = :queued)";

        $params = [
            'instanceid' => $this->notificationdata->instanceid, 'userid' => $userid, 'disabledstatus' => self::STATUS_DISABLED,
            'queued' => self::STATUS_QUEUED
        ];

        if ($record = $DB->get_record_sql($sql, $params)) {
            $DB->delete_records('pulseaction_notification_sch', ['id' => $record->id]);
        }
    }

    protected function get_schedule($data) {
        global $DB;

        if ($record = $DB->get_record('pulseaction_notification_sch', [
            'instanceid' => $data->instanceid, 'userid' => $data->userid
        ])) {
            return $record;
        }

        return false;
    }

    protected function find_last_notifiedtime($userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;

        // Get last notified schedule for this instance to the user.
        $condition = array('instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT);
        $records = $DB->get_records('pulseaction_notification_sch', $condition, 'id DESC', '*', 0, 1);

        return !empty($records) ? current($records)->notifiedtime : '';
    }


    protected function find_notify_count($userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;

        // Get last notified schedule for this instance to the user.
        $condition = array('instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT);
        $records = $DB->get_records('pulseaction_notification_sch', $condition, 'id DESC', '*', 0, 1);

        return !empty($records) ? current($records)->notifycount : '';
    }

    /**
     * Verify the user is already notified for this instance. It verify the lastrun is empty for the user record.
     *
     * Note: Use this method to verify the instance with interval once.
     *
     * @param integer $userid
     * @return boolean
     */
    protected function is_user_notified(int $userid) {
        global $DB;

        $id = $this->notificationdata->instanceid;
        if ($record = $DB->get_record('pulseaction_notification_sch', ['instanceid' => $id, 'userid' => $userid, 'status' => self::STATUS_SENT])) {
            return $record->notifiedtime != null ? true : false;
        }
        return false;
    }

    /**
     * Remove the schdeduled notifications for this instance.
     *
     * @return void
     */
    protected function remove_schedules() {
        global $DB;

        $DB->delete_records('pulseaction_notification_sch', ['instanceid' => $this->instancedata->id, 'status' => self::STATUS_SENT]);
    }




    public function create_schedule_forinstance($newenrolment=false) {
        // Generate the notification instance data.
        if (empty($this->instancedata)) {
            $this->create_instance_data();
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
            $this->create_schedule_foruser($user->id, null, 0, null, $newenrolment);
        }

        return true;
    }

    /**
     * Create schedule for the user.
     *
     * @param int $userid
     * @return void
     */
    public function create_schedule_foruser($userid, $lastrun='', $notifycount=0, $expectedruntime=null, $isnewuser=false, $newschedule=false) {

        if (empty($this->instancedata)) {
            $this->create_instance_data();
        }

        // Verify the user passed the instance condition.
        if (!instances::create($this->notificationdata->instanceid)
            ->find_user_completion_conditions($this->instancedata->condition, $this->instancedata, $userid, $isnewuser)) {
            // Remove the user condition.
            $this->disable_user_schedule($userid);
            return true;
        }

        // TODO: Verify it realy need to verify the suppress reached status.
        /*if ($suppressreached) {
            return true;
        }*/
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
        // # Find the next run.
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
     * @param [type] $userid
     * @param [type] $schedule
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
                    $date = $data->notifyinterval['monthdate'] ? $data->notifyinterval['monthdate'] - 1 : $data->notifyinterval['monthdate'];
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
            $delay = $data->delayduration;
            $nextrun->modify("+ $delay seconds");
        } else if ($data->notifydelay == self::DELAYBEFORE) {
            $delay = $data->delayduration;

            if ($expectedruntime) {
                // SEssion condition only send the expected runtime.
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


    protected function get_users_withroles(array $roles, $context) {
        global $DB;

        // TODO: Cache the role users.
        if (empty($roles)) {
            return [];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'rle');

        // TODO: Define user fields, never get entire fields.
        $rolesql = "SELECT DISTINCT u.id, u.*, ra.roleid FROM {role_assignments} ra
        JOIN {user} u ON u.id = ra.userid
        JOIN {role} r ON ra.roleid = r.id
        LEFT JOIN {role_names} rn ON (rn.contextid = :ctxid AND rn.roleid = r.id)
        WHERE (ra.contextid = :ctxid2 ) AND ra.roleid $insql ORDER BY u.id";

        $params = array('ctxid' => $context->id, 'ctxid2' => $context->id) + $inparams;

        $users = $DB->get_records_sql($rolesql, $params);

        return $users;
    }

    /**
     * Build the notification content.
     *
     * @param [type] $user
     * @param [type] $data
     * @param [type] $context
     * @return void
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

        return format_text($headercontent . $staticcontent . $footercontent, FORMAT_HTML, ['noclean' => true, 'overflowdiv' => true]);
    }

    /**
     * Gernerate the dynamic content.
     *
     * @param [type] $contenttype
     * @param [type] $contentlength
     * @param [type] $chapterid
     * @param [type] $context
     * @param [type] $data
     * @return void
     */
    public static function generate_dynamic_content($contenttype, $contentlength, $chapterid, $context, $cm) {

        global $CFG, $DB;

        require_once($CFG->dirroot.'/lib/modinfolib.php');
        require_once($CFG->dirroot.'/mod/book/lib.php');
        require_once($CFG->libdir.'/filelib.php');

        if ($contenttype == self::DYNAMIC_CONTENT) {

            if ($cm->modname == 'book') {
                $chapter = $DB->get_record('book_chapters', ['id' => $chapterid, 'bookid' => $cm->instance]);
                $chaptertext = \file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $chapter->id);

                $content = format_text($chaptertext, $chapter->contentformat, ['noclean' => true, 'overflowdiv' => true]);
                $link = new moodle_url('/mod/book/view.php', ['id' => $cm->id, 'chapterid' => $chapterid]);
            } else {
                $page = $DB->get_record('page', array('id' => $cm->instance), '*', MUST_EXIST);

                $content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
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
     * Generate the details for notification.
     *
     * @param object $data
     * @return stdclass
     */
    public function generate_notification_details($moddata, $user, $context, $notificationoverrides=[]) {

        // Find the cc and bcc users for this schedule.
        $roles = array_merge($this->notificationdata->cc, $this->notificationdata->bcc);
        // Get the users for this bcc and cc roles.
        $roleusers = $this->get_users_withroles($roles, $context);

        // Filter the cc users for this instance.
        $cc =  $this->notificationdata->cc;
        $ccusers = array_filter($roleusers, function($value) use ($cc) {
            return in_array($value->roleid, $cc);
        });

        // Filter the bcc users for this instance.
        $bcc = $this->notificationdata->bcc;
        $bccusers = array_filter($roleusers, function($value) use ($bcc) {
            return in_array($value->roleid, $bcc);
        });

        $result = [
            'recepient' => (object) $user,
            'cc'        => implode(',', array_column($ccusers, 'email')),
            'bcc'       => implode(',', array_column($bccusers, 'email')),
            'subject'   => format_string($this->notificationdata->subject),
            'content'   => $this->build_notification_content($moddata, $context, $notificationoverrides),
        ];

        return (object) $result;
    }

    public function get_tenantrole_sender($scheduledata) {
        // TODO: Tenant based sender fetch goes here.
        return (object) [];
    }



    /**
     * Undocumented function
     *
     * @param [type] $coursecontext
     * @param [type] $groupid
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

    public static function get_schedule_status($value) {
        if ($value == self::STATUS_DISABLED) {
            return get_string('onhold', 'pulseaction_notification');
        } else if ($value == self::STATUS_QUEUED) {
            return get_string('queued', 'pulseaction_notification');
        } else if ($value == self::STATUS_SENT) {
            return get_string('sent', 'pulseaction_notification');
        } else {
            return get_string('failed', 'pulseaction_notification');
        }
    }

    public static function get_schedule_subject($value, $row) {
        global $DB;

        $sender = \core_user::get_support_user();
        $courseid = $DB->get_field('pulse_autoinstances', 'courseid', ['id' => $row->instanceid]);
        $user =  (object) \core_user::get_user($row->userid);
        $course = get_course($courseid ?? SITEID);

        list($subject, $messagehtml) = \mod_pulse\helper::update_emailvars('', $value, $course, $user, null, $sender);

        return $subject . html_writer::link('javascript:void(0);', '<i class="fa fa-info"></i>', [
            'class' => 'pulse-automation-info-block',
            'data-target' => 'view-content',
            'data-instanceid' => $row->instanceid,
            'data-userid' => $row->userid
        ]);
    }

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


