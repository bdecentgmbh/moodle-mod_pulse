<?php


namespace pulseaction_notification;

use book;
use DateTime;
use mod_pulse\automation\helper;
use mod_pulse\automation\instances;
use mod_pulse\helper as pulsehelper;
use mod_pulse\plugininfo\pulseaction;

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

    protected $instancedata;

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
        $action = instances::create($notification->instanceid);
        $this->set_instance_data($action->get_instance_data());
    }

    public function set_instance_data($instancedata) {
        $data = (object) $instancedata;
        $this->instancedata = $this->update_data_structure($data);
    }

    public function update_data_structure($actiondata) {

        $actiondata->recipients = is_array($actiondata->recipients)
            ? $actiondata->recipients : json_decode($actiondata->recipients);

        $actiondata->bcc = is_array($actiondata->bcc) ? $actiondata->bcc : json_decode($actiondata->bcc);
        $actiondata->cc = is_array($actiondata->cc) ? $actiondata->cc : json_decode($actiondata->cc);

        $actiondata->notifyinterval = is_array($actiondata->notifyinterval)
            ? $actiondata->notifyinterval : json_decode($actiondata->notifyinterval);

        /* $isoverride = '';
        $context = ($isoverride) ? \context_course::instance($actiondata->courseid) : \context_system::instance();
        $id = $actiondata->instanceid ?? $actiondata->templateid;

        $actiondata->headercontent = file_rewrite_pluginfile_urls(
            $actiondata->headercontent, 'pluginfile.php', $context->id, 'mod_pulse',
            'pulsenotification_headercontent', $id
        );
        $actiondata->staticcontent = file_rewrite_pluginfile_urls(
            $actiondata->staticcontent, 'pluginfile.php', $context->id, 'mod_pulse',
            'pulsenotification_staticcontent', $id
        );
        $actiondata->footercontent = file_rewrite_pluginfile_urls(
            $actiondata->footercontent, 'pluginfile.php', $context->id, 'mod_pulse',
            'pulsenotification_footercontent', $id
        ); */

        return $actiondata;
    }

    protected function generate_schedule_record(int $userid) {
        $record = [
            'instanceid' => $this->instancedata->instanceid,
            'userid' => $userid,
            'type' => $this->instancedata->notifyinterval->interval,
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        return $record;
    }

    protected function insert_schedule($data) {
        global $DB;

        if ($record = $DB->get_record('pulseaction_notification_sch', ['instanceid' => $data['instanceid'], 'userid' => $data['userid']])) {
            $data['id'] = $record->id;
            // Update the status to enable for notify.
            $DB->update_record('pulseaction_notification_sch', $data);
        } else {
            $DB->insert_record('pulseaction_notification_sch', $data);
        }
    }

    protected function get_schedule($data) {
        global $DB;

        if ($record = $DB->get_record('pulseaction_notification_sch', ['instanceid' => $data->instanceid, 'userid' => $data->userid])) {
            return $record;
        }

        return false;
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

        $id = $this->instancedata->instanceid;
        if ($record = $DB->get_record('pulseaction_notification_sch', ['instanceid' => $id, 'userid' => $userid])) {
            return $record->lastrun != null ? true : false;
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

        $DB->delete_records('pulseaction_notification_sch', ['instanceid' => $this->instancedata->id]);
    }


    public function create_schedule_forinstance() {
        // Generate the notification instance data.
        if (empty($this->instancedata)) {
            $this->create_instance_data();
        }
        // Course context.
        $context = \context_course::instance($this->instancedata->courseid);
        // Roles to receive the notifications.
        $roles = $this->instancedata->recipients;
        if (empty($roles)) {
            // No roles are defined to recieve notifications. Remove the schedules for this instance.
            $this->remove_schedules();
            return true; // No roles are defined to recieve notifications. Break the schedule creation.
        }
        // Get the users for this receipents roles.
        $users = $this->get_users_withroles($roles, $context);
        foreach ($users as $userid => $user) {
            $this->create_schedule_foruser($user->id);
        }

        return true;
    }

    /**
     * Create schedule for the user.
     *
     * @param int $userid
     * @return void
     */
    public function create_schedule_foruser($userid) {

        if (empty($this->instancedata)) {
            $this->create_instance_data();
        }
        // Notification interval is once per user, it already notified to the user. break the trigger here.
        if ($this->instancedata->notifyinterval->interval == self::INTERVALONCE && $this->is_user_notified($userid)) {
            return true;
        }

        // Generate the schedule record.
        $data = $this->generate_schedule_record($userid);

        // # Find the next run.
        $nextrun = $this->generate_the_nextrun($userid);
        // Include the next run to schedule.
        $data['nextrun'] = date('Y-m-d H:i:s', $nextrun);
        // Insert the new schedule or update schedule.
        $this->insert_schedule($data);

        return true;
    }

    protected function generate_the_nextrun($userid, $schedule=null) {
        global $DB;

        $data = $this->instancedata;
        $data->userid = $userid;

        if (!$schedule) {
            $schedule = $this->get_schedule($data);
        }
        $nextrun = new DateTime('last day');
        if (!empty($schedule)  && !empty($schedule->lastrun)) {
            $lastrun = new DateTime($schedule->lastrun);
            $nextrun = $lastrun;
        }

        $interval = self::INTERVALMONTHLY; // $data->notifyinterval->interval;

        switch ($interval) {

            case self::INTERVALDAILY:
                $time = $data->notifyinterval->time;
                $nextrun->modify('+1 day');
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;

            case self::INTERVALWEEKLY:
                $day = $data->notifyinterval->weekday;
                $time = $data->notifyinterval->time;
                $nextrun->modify("Next ".$day);
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;

            case self::INTERVALMONTHLY:
                $monthdate = $data->notifyinterval->monthdate;
                if ($monthdate != 31) { // If the date is set as 31 then use the month end.
                    $nextrun->modify('first day of next month');
                    $date = $data->notifyinterval->monthdate ? $data->notifyinterval->monthdate - 1 : $data->notifyinterval->monthdate;
                    $nextrun->modify("+$date day");
                } else {
                    $nextrun->modify('last day of next month');
                }

                $time = $data->notifyinterval->time ?? '0:00';
                $timeex = explode(':', $time);
                $nextrun->setTime(...$timeex);
                break;
        }

        return $nextrun->getTimestamp();
    }

    protected function get_users_withroles(array $roles, $context) {
        global $DB;

        // TODO: Cache the role users.

        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'rle');

        // TODO: Define user fields, never get entire fields.
        $rolesql = "SELECT DISTINCT u.*, ra.roleid FROM {role_assignments} ra
        JOIN {user} u ON u.id = ra.userid
        JOIN {role} r ON ra.roleid = r.id
        LEFT JOIN {role_names} rn ON (rn.contextid = :ctxid AND rn.roleid = r.id)
        WHERE (ra.contextid = :ctxid2 ) AND ra.roleid $insql ORDER BY u.id";

        $params = array('ctxid' => $context->id, 'ctxid2' => $context->id) + $inparams;

        $users = $DB->get_records_sql($rolesql, $params);

        return $users;
    }

    protected function build_notification_content($user, $data, $context) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/lib/modinfolib.php');
        require_once($CFG->dirroot.'/mod/book/lib.php');
        require_once($CFG->libdir.'/filelib.php');


        $headercontent = format_text($this->instancedata->headercontent, FORMAT_HTML, ['noclean' => true, 'overflowdiv' => true]);
        $staticcontent = format_text($this->instancedata->staticcontent, FORMAT_HTML, ['noclean' => true, 'overflowdiv' => true]);
        $footercontent = format_text($this->instancedata->footercontent, FORMAT_HTML, ['noclean' => true, 'overflowdiv' => true]);

        $dynamiccontent = $this->instancedata->dynamiccontent;
        $cm = get_coursemodule_from_id($data->md_name, $data->cm_id);

        if ($dynamiccontent) {
            if ($data->md_name == 'book') {
                // TODO: Chapterid.
                $chapter = $DB->get_record('book_chapters', ['id' => 1, 'bookid' => $data->cm_instance]);
                $chaptertext = \file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $chapter->id);
                // TODO: Content length strip.
                $staticcontent .= format_text($chaptertext, $chapter->contentformat, ['noclean' => true, 'overflowdiv' => true]);
            } else {
                $page = $DB->get_record('page', array('id' => $data->cm_instance), '*', MUST_EXIST);
                // TODO: Content length.
                $staticcontent .= file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
            }
        }

        return $headercontent . $staticcontent . $footercontent;
    }

    /**
     * Generate the details for notification.
     *
     * @param object $data
     * @return stdclass
     */
    public function generate_notification_details($data) {
        // Course data.
        $course = helper::filter_record_byprefix($data, 'c');
        // User record data.
        $user = helper::filter_record_byprefix($data, 'ue');
        // Course context data.
        $context = (object) helper::filter_record_byprefix($data, 'ctx');

        // Find the cc and bcc users for this schedule.
        $roles = array_merge($this->instancedata->cc, $this->instancedata->bcc);
        // Get the users for this bcc and cc roles.
        $roleusers = $this->get_users_withroles($roles, $context);

        // Filter the cc users for this instance.
        $cc =  $this->instancedata->cc;
        $ccusers = array_filter($roleusers, function($value) use ($cc) {
            return in_array($value->roleid, $cc);
        });

        // Filter the bcc users for this instance.
        $bcc = $this->instancedata->bcc;
        $bccusers = array_filter($roleusers, function($value) use ($bcc) {
            return in_array($value->roleid, $bcc);
        });

        $result = [
            'recipient' => (object) $user,
            'cc'        => implode(',', array_column($ccusers, 'email')),
            'bcc'       => implode(',', array_column($bccusers, 'email')),
            'subject'   => format_string($this->instancedata->subject),
            'content'   => $this->build_notification_content($user, $data, $context),
        ];

        return (object) $result;
    }

    public function get_tenantrole_sender($scheduledata) {
        // TODO: Tenant based sender fetch goes here.
        return (object) [];
    }


    /**
     * Send the notificatino schedule to users based on the limit.
     *
     * @return void
     */
    public static function send_scheduled_notification() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/mod/pulse/automation/autmationlib.php');

        $timestamp = date('Y-m-d H:i:s'); // Current timestamp in sql timestamp format.

        $select[] = 'ns.id AS id'; // Set the schdule id as unique column.

        // Get columns not increase table queries.
        // TODO: Fetch only used columns. Fetching all the fields in a query will make double the time of query result.
        // TODO: All field 0.0010919570922852 => Width id only 0.00058603286743164 seconds.
        $tables = [
            'ns' => $DB->get_columns('pulseaction_notification_sch'),
            'ai' => $DB->get_columns('pulse_autoinstances'),
            'pat' => $DB->get_columns('pulse_autotemplates'),
            'pati' => $DB->get_columns('pulse_autotemplates_ins'),
            'ni' => $DB->get_columns('pulseaction_notification_ins'),
            'an' => $DB->get_columns('pulseaction_notification'),
            'ue' => $DB->get_columns('user'),
            'c' => $DB->get_columns('course'),
            'ctx' => $DB->get_columns('context'),
            'cm' => array_fill_keys(["id", "course", "module", "instance"], ""),
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
        $limit = 100;

        $senderjoin = '';

        // $select .= ", (".self::sender_sql().") AS sender";
        $DB->set_debug(true);

        // Fetch the schedule which is status as 1 and nextrun not empty and not greater than now.
        $sql = "SELECT $select FROM {pulseaction_notification_sch} ns
            JOIN {pulse_autoinstances} AS ai ON ai.id = ns.instanceid
            JOIN {pulse_autotemplates} AS pat ON pat.id = ai.templateid
            JOIN {pulse_autotemplates_ins} AS pati ON pati.instanceid = ai.id
            JOIN {pulseaction_notification_ins} AS ni ON ni.instanceid = ns.instanceid
            JOIN {pulseaction_notification} AS an ON an.templateid = ai.templateid
            JOIN {user} AS ue ON ue.id = ns.userid
            JOIN {course} as c ON c.id = ai.courseid
            JOIN {context} AS ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            JOIN {course_modules} AS cm ON cm.id = ni.dynamiccontent
            JOIN {modules} AS md ON md.id = cm.module
            WHERE ns.status = 1 AND ns.nextrun <> 0 AND ns.nextrun <= :current_timestamp ORDER BY ns.timemodified ASC ";

        $schedules = $DB->get_records_sql($sql, ['current_timestamp' => $timestamp] , 0, $limit);

        $supportuser = \core_user::get_support_user();

        foreach ($schedules as $sid => $notify) {
            // Schedule data.
            $schedule = (object) helper::filter_record_byprefix($notify, 'ns');
            // Course data.
            $course = (object) helper::filter_record_byprefix($notify, 'c');
            // Course data.
            $user = (object) helper::filter_record_byprefix($notify, 'ue');
            // Filter the notification data by its prefix.
            $notificationdata = helper::filter_record_byprefix($notify, 'an');
            // Filter the notification instance data by its prefix.
            $notificationinstancedata = helper::filter_record_byprefix($notify, 'ni');
            // Merge the notification overrides data and its notification data.
            $notifymergedata = (object) helper::merge_instance_overrides($notificationinstancedata, $notificationdata);

            // Verify the notification instance limit of notification is reached for this user.
            if (isset($schedule->limit) && $schedule->limit >= $notifymergedata->limit) {
                continue; // Limit reached break this continue to next user.
            }

            // Create notification instance.
            // Set the notification instance data merge with notification and instance data .
            $notification = self::instance($notify->an_id);
            $notification->set_instance_data($notifymergedata);

            // Generate the details to send the notification, it contains user, cc, bcc and schedule data.
            $detail = $notification->generate_notification_details($notify);

            // Courseid is needed in the message api.
            $pulse = (object) ['course' => $notify->c_id];

            // Find the sender for this schedule.
            if ($notifymergedata->sender == self::SENDERCUSTOM) {
                // Use the custom sender email as the support user email.
                $sender = (object) ['firstname' => '', 'lastname' => '', 'email' => $notifymergedata->senderemail];
            } else if ($notifymergedata->sender == self::SENDERTENANTROLE) {
                $sender = $notification->get_tenantrole_sender($notify);
            } else {
                // Get user groups is sender is configured as group teacher.
                $groupids = $notifymergedata->sender == self::SENDERGROUPTEACHER ? groups_get_user_groups($notify->c_id, $notify->ns_userid) : 0;
                // Course context data.
                $context = \mod_pulse_context_course::create_instance_fromrecord((object) helper::filter_record_byprefix($notify, 'ctx'));
                // Get the course teacher if group teacher not available it will fallback to course teacher automatically.
                $sender = (object) self::get_sender_users($context, $groupids);
            }

            // Add bcc and CC to sender user custom headers.
            $sender->customheaders = [
                "Bcc: $detail->bcc\r\n",
                "Cc: $detail->cc\r\n",
            ];

            // Message main content.
            $subject = $detail->subject;

            // Update the email placeholders.
            // list($subject, $messagehtml) = pulsehelper::update_emailvars($detail->content, $subject, $course, $user, null, $sender);

            $messagehtml = $detail->content;
            $messageplain = html_to_text($messagehtml);
            // Send the notification to user
            // TODO: NOTE using notification API takes 16 queries. Direct email_to_user method will take totally 9 queries.
            $messagesend = \mod_pulse\helper::messagetouser(
                $detail->recipient, $subject, $messageplain, $messagehtml, $pulse, $sender
            );

            // $messagesend = email_to_user($detail->recipient, $sender, $subject, $messageplain, $messagehtml);

            if ($messagesend) {
                // Update the current time as lastrun.
                // Update the lastrun and increase the limit.
                $lastrun = date('Y-m-d H:i:s');
                $schedule->lastrun = $lastrun;
                $update = [
                    'id' => $notify->id,
                    'lastrun' => $lastrun,
                    'limit' => $schedule->limit ? $schedule->limit + 1 : 1,
                    'timemodified' => time(), // TODO: Update the timemodified for all other record update.
                ];

                // Generate a next runtime. Only if user has limit to receive notifications. otherwise made the nextrun null.
                $update['nextrun'] = ($schedule->limit >= $update['limit'])
                    ? null : $notification->generate_the_nextrun($notify->ue_id, $schedule);
                // Update the schedule.
                $DB->update_record('pulseaction_notification_sch', $update);
            }
        }

        $DB->set_debug(false);

    }

/*  public static function sender_sql() {

        $now = time();
        return "SELECT eu1_u.*
                FROM {user} eu1_u
                JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = c.id)
                WHERE 1 = 1 AND ej1_ue.status = 0 AND ej1_e.status = 0 AND ej1_ue.timestart < $now AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > $now) AND eu1_u.deleted = 0 AND eu1_u.id <> 1 AND eu1_u.deleted = 0 LIMIT 1";
    } */


    /**
     * Undocumented function
     *
     * @param [type] $coursecontext
     * @param [type] $groupid
     * @return stdclass
     */
    protected static function get_sender_users($coursecontext, $groupid) {

        $withcapability = 'mod/pulse:addinstance';
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

        return current($sender);
    }


}


