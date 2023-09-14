<?php


namespace pulseaction_notification;

use html_writer;
use moodle_exception;
use DateTime;
use DatePeriod;
use mod_pulse\automation\helper;
use pulseaction_notification\notification;
use pulseaction_notification\schedule;


class actionform extends \mod_pulse\automation\action_base {

    /**
     * Shortname for the config used in the form field.
     *
     * @return string
     */
    public function config_shortname() {
        return 'pulsenotification';
    }

    /**
     * Get the icon for this component, displayed on the instances list on the course autotemplates sections.
     *
     * @return string
     */
    public function get_action_icon() {
        global $OUTPUT;
        return $OUTPUT->pix_icon("i/notifications", get_string('notifications'));
    }

    /**
     * Delete notification instances and schedule data for this instance.
     *
     * @param integer $instanceid
     * @return void
     */
    public function delete_instance_action(int $instanceid) {
        global $DB;

        parent::delete_instance_action($instanceid);
        $instancetable = 'pulseaction_notification_sch';
        return $DB->delete_records($instancetable, ['instanceid' => $instanceid]);
    }


    /**
     * Prepare editor fileareas.
     *
     * @param int|null $instanceid
     * @return void
     */
    public function prepare_editor_fileareas(&$data, \context $context) {

        $data = (object) ($data ?: ['id' => 0]);

        $context = \context_system::instance();
        $templateid = $data->templateid ?? $data->id;

        // Support for the instance form. use the course context to prepare and update editor incase it's override in the instance.
        if (isset($data->courseid) && isset($data->instanceid)) {
            $prefix = '_instance';
        }

        $editor = [
            "pulsenotification_headercontent",
            "pulsenotification_staticcontent",
            "pulsenotification_footercontent"
        ];

        foreach ($editor as $field) {

            // Create empty data set for the new template.
            if (!isset($data->$field)) {
                $data->$field = '';
                $data->{$field."format"} = editors_get_preferred_format();
            }

            $id = isset($data->instanceid) && isset($data->override[$field.'_editor']) ? $data->instanceid : $templateid;
            $filearea = isset($prefix) && isset($data->override[$field.'_editor']) ? $field.$prefix : $field;

            $data = file_prepare_standard_editor(
                $data, $field, $this->get_editor_options($context), $context, 'mod_pulse', $filearea, $id
            );

        }

    }

    /**
     * Prepare editor fileareas.
     *
     * @param int|null $instanceid
     * @return void
     */
    public function postupdate_editor_fileareas(&$data, \context $context) {
        $data = (object) $data;

        $context = \context_system::instance();
        $templateid = $data->templateid ?? $data->id;

        $editor = [
            "pulsenotification_headercontent",
            "pulsenotification_staticcontent",
            "pulsenotification_footercontent"
        ];

        if (isset($data->courseid) && isset($data->instanceid) ) {
            $prefix = '_instance';
        }

        foreach ($editor as $field) {

            if (!isset($data->$field) && !isset($data->{$field.'_editor'})) {
                continue;
            }

            $id = $data->instanceid ?? $templateid;
            $filearea = isset($prefix) ? $field.$prefix : $field;

            $data = file_postupdate_standard_editor(
                $data, $field, $this->get_editor_options($context), $context,  'mod_pulse', $filearea, $id
            );
        }
    }

    protected function get_editor_options($context=null) {
        global $PAGE;

        return [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => 50,
            'context' => $context ?: $PAGE->context
        ];
    }

    /**
     * Delete the template action.
     *
     * @param [type] $templateid
     * @return void
     */
    public function delete_template_action($templateid) {
        global $DB;

        return $DB->delete_records('pulseaction_notification', ['templateid' => $templateid]);
    }

    /**
     * Action is triggered for the instance. when triggered notification will create a schedule for the triggered users.
     *
     * Create the notification instance and initiate the schedule for this instance.
     *
     * @param stdclass $instancedata
     * @param int $userid
     * @return void
     */
    public function trigger_action($instancedata, $userid, $expectedtime=null, $newuser=false) {

        $notification = notification::instance($instancedata->pulsenotification_id);
        $notificationinstance = helper::filter_record_byprefix($instancedata, $this->config_shortname());

        // print_object($instancedata);exit;
        $notification->set_notification_data($notificationinstance, $instancedata);

        // Create a schedule for user. This method verify the user activity completion before creating schedules.
        $notification->create_schedule_foruser($userid, '', null, $runtime ?? null, $newuser);

        // Send the scheduled notifications for this user.
        schedule::instance()->send_scheduled_notification($userid);
    }

    /**
     * Observe the events, triggered from the main pulse.
     *
     * @param [type] $instancedata
     * @param [type] $method
     * @param [type] $eventdata
     * @return void
     */
    public function trigger_action_event($instancedata, $method, $eventdata) {

        if ($method == 'user_enrolment_deleted') {

            $notificationid = $instancedata->actions['notification']['id'];
            $notification = notification::instance($notificationid);
            $notification->set_notification_data($instancedata->actions['notification'], $instancedata);
            $userid = $eventdata->relateduserid;

            $notification->remove_user_schedules($userid);
        }
    }

    /**
     * Get the notification record to attach the template create form.
     *
     * @param int $templateid
     * @return stdclass
     */
    public function get_data_fortemplate($templateid) {
        global $DB;
        $actiondata = $DB->get_record('pulseaction_notification', ['templateid' => $templateid]);
        return $actiondata;
    }

    /**
     * Get data for instance.
     *
     * @param [type] $instanceid
     * @return void
     */
    public function get_data_forinstance($instanceid) {
        global $DB;
        $instancedata = $DB->get_record('pulseaction_notification_ins', ['instanceid' => $instanceid]);
        return $instancedata;
    }

    public function update_encode_data(&$actiondata) {

        $actiondata = (array) $actiondata;

        $actiondata['recipients'] = json_decode($actiondata['recipients']);
        $actiondata['bcc'] = json_decode($actiondata['bcc']);
        $actiondata['cc'] = json_decode($actiondata['cc']);
        $actiondata['suppress'] = isset($actiondata['suppress']) ? json_decode($actiondata['suppress']) : [];


        $actiondata['notifyinterval'] = json_decode($actiondata['notifyinterval'], true);
    }

    protected function update_data_structure(&$actiondata) {

        // Testing the action data.

       /*  if (isset($actiondata->notify)) {
            $actiondata->notifyinterval = $actiondata->notify;
        } */

        array_walk($actiondata, function(&$value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
        });
    }

    /**
     * Save the template config.
     *
     * @param stdclass $record
     * @param string $component
     * @return void
     */
    public function process_save($record, $component) {
        global $DB;

        $context = \context_system::instance();

        $this->postupdate_editor_fileareas($record, $context);

        // Filter the current action data from the templates data by its shortname.
        $actiondata = $this->filter_action_data((array) $record);

        $actiondata->templateid = $record->templateid;

        if (!empty($actiondata)) {

            // Update the data strucured before save.
            $this->update_data_structure($actiondata);

            // try {
                // In moodle, the main table should be the name of the component.
                // Therefore, generate the table name based on the component name.
                $tablename = 'pulseaction_'. $component;
                // Get the record by using the templateid, one instance is allowed for each template.
                // Manage the action component data for this template.
                if ($notification = $DB->get_record($tablename, ['templateid' => $record->templateid])) {
                    $actiondata->id = $notification->id;
                    // Update the latest data into the action component.
                    $DB->update_record($tablename, $actiondata);
                } else {
                    // Create new instance for this tempalte.
                    $DB->insert_record($tablename, $actiondata);
                }

            // } catch (\Exception $e) {
            //     // Throw  an error incase of issue with manage the data update.
            //     throw new \moodle_exception('actiondatanotsave', $component);
            // }
        }
        return true;
    }

    public function process_instance_save($instanceid, $record) {
        global $DB;

        // Filter the current action data from the templates data by its shortname.
        $actiondata = $this->filter_action_data((array) $record);

        // $actiondata->templateid = $record->templateid;

        // if (!empty($actiondata)) {

            // Update the data strucured before save.
            $this->update_data_structure($actiondata);

            $actiondata->instanceid = $instanceid;
            // try {
                // In moodle, the main table should be the name of the component.
                // Therefore, generate the table name based on the component name.
                $tablename = 'pulseaction_notification_ins';
                // Get the record by using the templateid, one instance is allowed for each template.
                // Manage the action component data for this template.
                // print_object($record);exit;
                if (isset($instanceid) && $notifyinstance = $DB->get_record($tablename, ['instanceid' => $instanceid])) {
                    $actiondata->id = $notifyinstance->id;

                    $notificationinstance = $actiondata->id;
                    // Update the latest data into the action component.
                    $DB->update_record($tablename, $actiondata);
                } else {
                    // Create new instance for this tempalte.
                    $notificationinstance = $DB->insert_record($tablename, $actiondata);
                }
                // TODO: Create a schedules based on receipents role.
                notification::instance($notificationinstance)->create_schedule_forinstance();
            // } catch (\Exception $e) {
            //     // Throw  an error incase of issue with manage the data update.
            //     throw new \moodle_exception('actiondatanotsave', $component);
            // }
        // }
        return true;
    }

    /**
     * Default override elements.
     *
     * @return array
     */
    public function default_override_elements() {
        // List of pulse notification elements those are available in only instances.
        return [
            'pulsenotification_suppress',
            'pulsenotification_suppressoperator',
            'pulsenotification_dynamiccontent',
            'pulsenotification_contenttype',
            'pulsenotification_chapterid',
            'pulsenotification_contentlength',
        ];
    }

    /**
     * Load the instance elements for the form.
     *
     * @param moodle_form $mform
     * @param actionform $forminstance
     * @return void
     */
    public function load_instance_form(&$mform, $forminstance) {
        global $CFG, $PAGE, $DB;

        require_once($CFG->dirroot.'/lib/modinfolib.php');

        $this->load_global_form($mform, $forminstance);

        // Dynamic Content Group
        // Add 'dynamic_content' element with all activities in the course.
        $courseid = $forminstance->get_customdata('courseid') ?? '';
        $modinfo = \course_modinfo::instance($courseid);

        // Include the suppress activity settings for the instance.
        $completion = new \completion_info(get_course($courseid));
        $activities = $completion->get_activities();
        array_walk($activities, function(&$value) {
            $value = $value->name;
        });

        $suppress = $mform->createElement('autocomplete', 'pulsenotification_suppress',
            get_string('suppressmodule', 'pulseaction_notification'), $activities, array('multiple' => 'multiple'));

        $mform->insertElementBefore($suppress, 'pulsenotification_notifylimit');
        $mform->addHelpButton('pulsenotification_suppress', 'suppressmodule', 'pulseaction_notification');

        // Operator element.
        $operators = [
            \mod_pulse\automation\action_base::OPERATOR_ALL => get_string('all', 'pulse'),
            \mod_pulse\automation\action_base::OPERATOR_ANY => get_string('any', 'pulse'),
        ];
        $suppressopertor = $mform->createElement('select', 'pulsenotification_suppressoperator', get_string('suppressoperator', 'pulseaction_notification'), $operators);
        $mform->setDefault('suppressoperator', \mod_pulse\automation\action_base::OPERATOR_ANY);
        $mform->insertElementBefore($suppressopertor, 'pulsenotification_notifylimit');
        $mform->addHelpButton('pulsenotification_suppressoperator', 'suppressoperator', 'pulseaction_notification');

        $modules = [0 => get_string('none')];
        $books = $modinfo->get_instances_of('book');
        $pages = $modinfo->get_instances_of('page');
        $list = array_merge($books, $pages);
        foreach ($list as $page) {
            $modules[$page->id] = $page->get_formatted_name();
        }

        $dynamic = $mform->createElement('select', 'pulsenotification_dynamiccontent', get_string('dynamiccontent', 'pulseaction_notification'), $modules);
        $mform->insertElementBefore($dynamic, 'pulsenotification_footercontent_editor');
        $mform->addHelpButton('pulsenotification_dynamiccontent', 'dynamiccontent', 'pulseaction_notification');

        // Add 'content_type' element with the following options:
        $content_type_options = array(
            notification::DYNAMIC_DESCRIPTION => get_string('description', 'pulseaction_notification'),
            notification::DYNAMIC_CONTENT => get_string('content', 'pulseaction_notification'),
        );
        $dynamic2 = $mform->createElement('select', 'pulsenotification_contenttype', get_string('contenttype', 'pulseaction_notification'), $content_type_options);
        $mform->insertElementBefore($dynamic2, 'pulsenotification_footercontent_editor');
        $mform->hideIf('pulsenotification_contenttype', 'pulsenotification_dynamiccontent', 'eq', 0);
        $mform->addHelpButton('pulsenotification_contenttype', 'contenttype', 'pulseaction_notification');

        // Load Chapters for selected book.
        $instanceid = $forminstance->get_customdata('instanceid');
        $cmid = $instanceid ? $DB->get_field('pulseaction_notification_ins', 'dynamiccontent', ['instanceid' => $instanceid]) : '';
        if (!empty($cmid)) {
            $sql = 'SELECT bc.id, bc.title FROM {course_modules} cm
            JOIN {book_chapters} as bc ON bc.bookid = cm.instance
            WHERE cm.id = :cmid';
            $chapters = $DB->get_records_sql_menu($sql, ['cmid' => $cmid]);
        }
        $options['ajax'] = 'pulseaction_notification/chaptersource';
        $chapter = $mform->createElement('autocomplete', 'pulsenotification_chapterid', get_string('chapters', 'pulseaction_notification'), $chapters ?? [], $options);
        $mform->insertElementBefore($chapter, 'pulsenotification_footercontent_editor');
        $mform->addHelpButton('pulsenotification_chapterid', 'chapters', 'pulseaction_notification');

        // Content Length Group
        $content_length_options = array(
            notification::LENGTH_TEASER => get_string('teaser', 'pulseaction_notification'),
            notification::LENGTH_LINKED => get_string('full_linked', 'pulseaction_notification'),
            notification::LENGTH_NOTLINKED => get_string('full_not_linked', 'pulseaction_notification'),
        );
        $dynamic3 = $mform->createElement('select', 'pulsenotification_contentlength', get_string('contentlength', 'pulseaction_notification'), $content_length_options);
        $mform->insertElementBefore($dynamic3, 'pulsenotification_footercontent_editor');
        $mform->addHelpButton('pulsenotification_contentlength', 'contentlength', 'pulseaction_notification');

        $mform->hideIf('pulsenotification_contentlength', 'pulsenotification_dynamiccontent', 'eq', 0);

        asort($mform->_elementIndex);

        $PAGE->requires->js_call_amd('pulseaction_notification/chaptersource', 'updateChapter', ['contextid' => $PAGE->context->id]);
    }

    /**
     * Global form elements.
     *
     * @param moodle_form $mform
     * @param \automation_instance_form $forminstance
     * @return void
     */
    public function load_global_form(&$mform, $forminstance) {
        global $CFG, $PAGE;

        require_once($CFG->dirroot.'/course/lib.php');

        // Sender Group
        $sender_options = array(
            notification::SENDERCOURSETEACHER => get_string('courseteacher', 'pulseaction_notification'),
            notification::SENDERGROUPTEACHER => get_string('groupteacher', 'pulseaction_notification'),
            notification::SENDERTENANTROLE => get_string('tenantrole', 'pulseaction_notification'),
            notification::SENDERCUSTOM => get_string('custom', 'pulseaction_notification')
        );
        $mform->addElement('select', 'pulsenotification_sender', get_string('sender', 'pulseaction_notification'), $sender_options);
        $mform->addHelpButton('pulsenotification_sender', 'sender', 'pulseaction_notification');

        // Add additional settings for the 'custom' option, if selected.
        $mform->addElement('text', 'pulsenotification_senderemail', get_string('senderemail', 'pulseaction_notification'));
        $mform->setType('pulsenotification_senderemail', PARAM_EMAIL);
        $mform->hideIf('pulsenotification_senderemail', 'pulsenotification_sender', 'neq', notification::SENDERCUSTOM);

        $interval = [];
        // Schedule Group.
        $intervaloptions = array(
            notification::INTERVALONCE => get_string('once', 'pulseaction_notification'),
            notification::INTERVALDAILY => get_string('daily', 'pulseaction_notification'),
            notification::INTERVALWEEKLY => get_string('weekly', 'pulseaction_notification'),
            notification::INTERVALMONTHLY => get_string('monthly', 'pulseaction_notification'),
        );
        $interval[] =& $mform->createElement('select', 'pulsenotification_notifyinterval[interval]', get_string('interval', 'pulseaction_notification'), $intervaloptions);

        // Add additional settings based on the selected interval.
        $dayweeks = array(
            'monday' => get_string('monday', 'pulseaction_notification'),
            'tuesday' => get_string('tuesday', 'pulseaction_notification'),
            'wednesday' => get_string('wednesday', 'pulseaction_notification'),
            'thursday' => get_string('thursday', 'pulseaction_notification'),
            'friday' => get_string('friday', 'pulseaction_notification'),
            'saturday' => get_string('saturday', 'pulseaction_notification'),
            'sunday' => get_string('sunday', 'pulseaction_notification'),
        );

        // Add 'day_of_week' element if 'weekly' is selected in the 'interval' element.
        $interval[] =& $mform->createElement('select', 'pulsenotification_notifyinterval[weekday]', get_string('interval', 'pulseaction_notification'), $dayweeks);
        $mform->hideIf('pulsenotification_notifyinterval[weekday]', 'pulsenotification_notifyinterval[interval]', 'neq', notification::INTERVALWEEKLY);

        $dates = range(1, 31);
        // Add 'day_of_month' element if 'monthly' is selected in the 'interval' element.
        $interval[] =& $mform->createElement('select', 'pulsenotification_notifyinterval[monthdate]', get_string('interval', 'pulseaction_notification'), $dates);
        $mform->hideIf('pulsenotification_notifyinterval[monthdate]', 'pulsenotification_notifyinterval[interval]', 'neq', notification::INTERVALMONTHLY);

        // Time to send notification.
        $dates = $this->get_times();
        // Add 'time_of_day' element.
        // Add 'day_of_month' element if 'monthly' is selected in the 'interval' element.
        $interval[] =& $mform->createElement('select', 'pulsenotification_notifyinterval[time]', get_string('interval', 'pulseaction_notification'), $dates);
        $mform->hideIf('pulsenotification_notifyinterval[time]', 'pulsenotification_notifyinterval[interval]', 'eq', notification::INTERVALONCE);

        // Notification interval button groups.
        $mform->addGroup($interval, 'pulsenotification_notifyinterval', get_string('interval', 'pulseaction_notification'), array(' '), false);
        $mform->addHelpButton('pulsenotification_notifyinterval', 'interval', 'pulseaction_notification');

        // Notification delay.
        $delayoptions = array(
            notification::DELAYNONE => get_string('none', 'pulseaction_notification'),
            notification::DELAYBEFORE => get_string('before', 'pulseaction_notification'),
            notification::DELAYAFTER => get_string('after', 'pulseaction_notification'),
        );
        $mform->addElement('select', 'pulsenotification_notifydelay', get_string('delay', 'pulseaction_notification'), $delayoptions);
        $mform->setDefault('delay', 'none');
        $mform->addHelpButton('pulsenotification_notifydelay', 'delay', 'pulseaction_notification');

        $mform->addElement('duration', 'pulsenotification_delayduration', get_string('delayduraion', 'pulseaction_notification'));
        // $mform->setType('pulsenotification_delayduration', PARAM_INT);
        $mform->hideIf('pulsenotification_delayduration', 'pulsenotification_notifydelay', 'eq', notification::DELAYNONE);
        $mform->addHelpButton('pulsenotification_delayduration', 'delayduraion', 'pulseaction_notification');

        // Limit no of notifications Group
        $mform->addElement('text', 'pulsenotification_notifylimit', get_string('limit', 'pulseaction_notification'));
        $mform->setType('pulsenotification_notifylimit', PARAM_INT);
        $mform->addHelpButton('pulsenotification_notifylimit', 'limit', 'pulseaction_notification');

        // Recipients Group.
        // Add 'recipients' element with all roles that can receive notifications.
        $roles = get_roles_with_capability('pulseaction/notification:receivenotification');
        $rolenames = role_fix_names($roles);
        $roleoptions = array_combine(array_column($rolenames, 'id'), array_column($rolenames, 'localname'));
        $mform->addElement('autocomplete', 'pulsenotification_recipients', get_string('recipients', 'pulseaction_notification'), $roleoptions, array('multiple' => 'multiple'));
        $mform->addHelpButton('pulsenotification_recipients', 'recipients', 'pulseaction_notification');

        // CC Group.
        // Add 'cc' element with all course context and user context roles.
        $courseroles = $forminstance->course_roles();
        $mform->addElement('autocomplete', 'pulsenotification_cc', get_string('ccrecipients', 'pulseaction_notification'), $courseroles, array('multiple' => 'multiple'));
        $mform->addHelpButton('pulsenotification_cc', 'ccrecipients', 'pulseaction_notification');

        // Set BCC.
        $mform->addElement('autocomplete', 'pulsenotification_bcc', get_string('bccrecipients', 'pulseaction_notification'), $courseroles, array('multiple' => 'multiple'));
        $mform->addHelpButton('pulsenotification_bcc', 'bccrecipients', 'pulseaction_notification');

        // Subject.
        $mform->addElement('text', 'pulsenotification_subject', get_string('subject', 'pulseaction_notification'), ['size' => 100]);
        $mform->setType('pulsenotification_subject', PARAM_TEXT);
        $mform->addHelpButton('pulsenotification_subject', 'subject', 'pulseaction_notification');

        $context = \context_system::instance();
        $mform->addElement('editor', 'pulsenotification_headercontent_editor', get_string('headercontent', 'pulseaction_notification'),
            ['class' => 'fitem_id_templatevars_editor'], $this->get_editor_options($context));
            $mform->addHelpButton('pulsenotification_headercontent_editor', 'headercontent', 'pulseaction_notification');
        $forminstance->pulse_email_placeholders($mform);

        // Statecontent editor.
        $mform->addElement('editor', 'pulsenotification_staticcontent_editor', get_string('staticcontent', 'pulseaction_notification'),
            ['class' => 'fitem_id_templatevars_editor'], $this->get_editor_options($context));
        $mform->addHelpButton('pulsenotification_staticcontent_editor', 'staticcontent', 'pulseaction_notification');
        $forminstance->pulse_email_placeholders($mform);

        // Footer Content.
        $mform->addElement('editor', 'pulsenotification_footercontent_editor', get_string('footercontent', 'pulseaction_notification'),
            ['class' => 'fitem_id_templatevars_editor'], $this->get_editor_options($context));
        $mform->addHelpButton('pulsenotification_footercontent_editor', 'footercontent', 'pulseaction_notification');
        $forminstance->pulse_email_placeholders($mform);

        // Preview Button.
        $mform->addElement('button', 'pulsenotification_preview', get_string('preview', 'pulseaction_notification'));
        $mform->addHelpButton('pulsenotification_preview', 'preview', 'pulseaction_notification');

        // Email tempalte placholders.
        $PAGE->requires->js_call_amd('mod_pulse/module', 'init');
        $PAGE->requires->js_call_amd('pulseaction_notification/chaptersource', 'previewNotification', ['contextid' => $PAGE->context->id]);
    }

    /**
     * Get list of options in 30 mins timeinterval for 24 hrs.
     *
     * @return array
     */
    public function get_times() {

        $starttime = new DateTime('00:00'); // Set the start time to midnight.
        $endtime = new DateTime('23:59');   // Set the end time to just before midnight of the next day.

        // Create an interval of 30 minutes.
        $interval = new \DateInterval('PT30M'); // PT30M represents 30 minutes.

        // Create a DatePeriod to iterate through the day with the specified interval.
        $timeperiod = new DatePeriod($starttime, $interval, $endtime);

        // Loop through the DatePeriod and add each timestamp to the array.
        $timelist = array();
        foreach ($timeperiod as $time) {
            $timelist[$time->format('H:i')] = $time->format('H:i'); // Format the timestamp as HH:MM.
        }

        return $timelist;
    }
}
