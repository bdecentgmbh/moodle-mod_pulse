<?php


namespace pulseaction_notification;

use html_writer;
use moodle_exception;
use DateTime;
use DatePeriod;
use mod_pulse\automation\helper;
use pulseaction_notification\notification;

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
     * Prepare editor fileareas.
     *
     * @param int|null $instanceid
     * @return void
     */
    public function prepare_editor_fileareas(&$data, \context $context) {

        $data = (object) $data;

        $editor = [
            "pulsenotification_headercontent",
            "pulsenotification_staticcontent",
            "pulsenotification_footercontent"
        ];

       /*  print_object($data);
        exit; */
        foreach ($editor as $filearea) {

            // Create empty data set for the new template.
            if (!isset($data->$filearea)) {
                $data->$filearea = '';
                $data->{$filearea."format"} = editors_get_preferred_format();
            }

            $id = $data->instanceid ?? 0;
            $id = ($id == 0 && isset($data->id)) ? $data->id : 0; // It's called from templates section, then use the templateid.

            $data = file_prepare_standard_editor(
                $data, $filearea, $this->get_editor_options($context), $context,  'mod_pulse', $filearea, $id
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

        $editor = [
            "pulsenotification_headercontent",
            "pulsenotification_staticcontent",
            "pulsenotification_footercontent"
        ];

        foreach ($editor as $filearea) {

            if (!isset($data->$filearea) && !isset($data->{$filearea.'_editor'})) {
                continue;
            }

            $id = $data->instanceid ?? 0;
            $id = ($id == 0 && isset($data->id)) ? $data->id : 0; // It's called from templates section, then use the templateid.

            $data = file_postupdate_standard_editor(
                $data, $filearea, $this->get_editor_options(), $context,  'mod_pulse', $filearea, $id
            );
        }

    }

    protected function get_editor_options($context=null) {
        global $PAGE;

        return [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => 1,
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
    public function trigger_action($instancedata, $userid) {

        $notification = notification::instance($instancedata->pulsenotification_id);
        $notificationinstance = helper::filter_record_byprefix($instancedata, $this->config_shortname());
        $notification->set_instance_data($notificationinstance);
        $notification->create_schedule_foruser($userid);
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


        $actiondata['notifiy'] = json_decode($actiondata['notifyinterval'], true);
    }

    protected function update_data_structure(&$actiondata) {

        // Testing the action data.

        if (isset($actiondata->notify)) {
            $actiondata->notifyinterval = $actiondata->notify;
        }
    }

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

            array_walk($actiondata, function(&$value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
            });

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

        if (!empty($actiondata)) {

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
        }
        return true;
    }

    public function load_instance_form(&$mform, $forminstance) {
        global $CFG;
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

        $suppress = $mform->createElement('select', 'pulsenotification_suppress', get_string('suppressmodule', 'pulseaction_notification'), $activities);
        $mform->insertElementBefore($suppress, 'pulsenotification_notifylimit');

        // Operator element.
        $operators = [
            \mod_pulse\automation\action_base::OPERATOR_ALL => get_string('all', 'pulse'),
            \mod_pulse\automation\action_base::OPERATOR_ANY => get_string('any', 'pulse'),
        ];
        $suppressopertor = $mform->createElement('select', 'pulsenotification_suppressoperator', get_string('suppressoperator', 'pulseaction_notification'), $operators);
        $mform->setDefault('suppressoperator', \mod_pulse\automation\action_base::OPERATOR_ANY);
        $mform->insertElementBefore($suppressopertor, 'pulsenotification_notifylimit');


        $modules = [0 => get_string('none')];
        $books = $modinfo->get_instances_of('book');
        $pages = $modinfo->get_instances_of('page');
        $list = array_merge($books, $pages);
        foreach ($list as $page) {
            $modules[$page->id] = $page->get_formatted_name();
        }

        // array_unshift($modules, get_string('none'));
        $dynamic = $mform->createElement('select', 'pulsenotification_dynamiccontent', get_string('dynamiccontent', 'pulseaction_notification'), $modules);
        $mform->insertElementBefore($dynamic, 'pulsenotification_footercontent_editor');

        // Add 'content_type' element with the following options:
        $content_type_options = array(
            notification::DYNAMIC_DESCRIPTION => get_string('description', 'pulseaction_notification'),
            notification::DYNAMIC_CONTENT => get_string('content', 'pulseaction_notification'),
        );
        $dynamic2 = $mform->createElement('select', 'pulsenotification_contenttype', get_string('contenttype', 'pulseaction_notification'), $content_type_options);
        $mform->insertElementBefore($dynamic2, 'pulsenotification_footercontent_editor');
        $mform->hideIf('pulsenotification_contenttype', 'pulsenotification_dynamiccontent', 'eq', 0);

        // Content Length Group
        $content_length_options = array(
            notification::LENGTH_TEASER => get_string('teaser', 'pulseaction_notification'),
            notification::LENGTH_LINKED => get_string('full_linked', 'pulseaction_notification'),
            notification::LENGTH_NOTLINKED => get_string('full_not_linked', 'pulseaction_notification'),
        );
        $dynamic3 = $mform->createElement('select', 'pulsenotification_contentlength', get_string('contentlength', 'pulseaction_notification'), $content_length_options);
        $mform->insertElementBefore($dynamic3, 'pulsenotification_footercontent_editor');

        $mform->hideIf('pulsenotification_contentlength', 'pulsenotification_dynamiccontent', 'eq', 0);

        asort($mform->_elementIndex);

    }

    public function load_global_form(&$mform, $forminstance) {
        global $CFG, $PAGE;

        require_once($CFG->dirroot.'/course/lib.php');

        // Define the form elements inside the definition function.
        $mform->addElement('html', '<div class="tab-pane fade" id="pulse-notification-content"> ');

        // Sender Group
        $sender_options = array(
            notification::SENDERCOURSETEACHER => get_string('courseteacher', 'pulseaction_notification'),
            notification::SENDERGROUPTEACHER => get_string('groupteacher', 'pulseaction_notification'),
            notification::SENDERTENANTROLE => get_string('tenantrole', 'pulseaction_notification'),
            notification::SENDERCUSTOM => get_string('custom', 'pulseaction_notification')
        );
        $mform->addElement('select', 'pulsenotification_sender', get_string('sender', 'pulseaction_notification'), $sender_options);
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
        $interval[] =& $mform->createElement('select', 'pulsenotification_notify[interval]', get_string('interval', 'pulseaction_notification'), $intervaloptions);
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
        $interval[] =& $mform->createElement('select', 'pulsenotification_notify[weekday]', get_string('interval', 'pulseaction_notification'), $dayweeks);
        $mform->hideIf('pulsenotification_notify[weekday]', 'pulsenotification_notify[interval]', 'neq', notification::INTERVALWEEKLY);

        $dates = range(1, 31);
        // Add 'day_of_month' element if 'monthly' is selected in the 'interval' element.
        $interval[] =& $mform->createElement('select', 'pulsenotification_notify[monthdate]', get_string('interval', 'pulseaction_notification'), $dates);
        $mform->hideIf('pulsenotification_notify[monthdate]', 'pulsenotification_notify[interval]', 'neq', notification::INTERVALMONTHLY);

        // Time to send notification.
        $dates = $this->get_times();
        // Add 'time_of_day' element.
        // Add 'day_of_month' element if 'monthly' is selected in the 'interval' element.
        $interval[] =& $mform->createElement('select', 'pulsenotification_notify[time]', get_string('interval', 'pulseaction_notification'), $dates);
        $mform->hideIf('pulsenotification_notify[time]', 'pulsenotification_notify[interval]', 'eq', notification::INTERVALONCE);

        // Notification interval button groups.
        $mform->addGroup($interval, 'interval', get_string('interval', 'pulseaction_notification'), array(' '), false);

        // Notification delay.
        $delayoptions = array(
            notification::DELAYNONE => get_string('none', 'pulseaction_notification'),
            notification::DELAYBEFORE => get_string('before', 'pulseaction_notification'),
            notification::DELAYAFTER => get_string('after', 'pulseaction_notification'),
        );
        $mform->addElement('select', 'pulsenotification_notifydelay', get_string('delay', 'pulseaction_notification'), $delayoptions);
        $mform->setDefault('delay', 'none');

        $mform->addElement('duration', 'pulsenotification_delayduration', get_string('delayduraion', 'pulseaction_notification'));
        // $mform->setType('pulsenotification_delayduration', PARAM_INT);
        $mform->hideIf('pulsenotification_delayduration', 'pulsenotification_notifydelay', 'eq', notification::DELAYNONE);


        // Limit no of notifications Group
        $mform->addElement('text', 'pulsenotification_notifylimit', get_string('limit', 'pulseaction_notification'));
        $mform->setType('pulsenotification_notifylimit', PARAM_INT);

        // Recipients Group
        // Add 'recipients' element with all roles that can receive notifications.
        $roles = get_roles_with_capability('pulseaction/notification:receivenotification');
        $rolenames = role_fix_names($roles);
        $roleoptions = array_combine(array_column($rolenames, 'id'), array_column($rolenames, 'localname'));

        $mform->addElement('autocomplete', 'pulsenotification_recipients', get_string('recipients', 'pulseaction_notification'), $roleoptions, array('multiple' => 'multiple'));

        // CC Group.
        // Add 'cc' element with all course context and user context roles.
        $courseroles = $forminstance->course_roles();
        $mform->addElement('autocomplete', 'pulsenotification_cc', get_string('ccrecipients', 'pulseaction_notification'), $courseroles, array('multiple' => 'multiple'));

        // Set BCC.
        $mform->addElement('autocomplete', 'pulsenotification_bcc', get_string('bccrecipients', 'pulseaction_notification'), $courseroles, array('multiple' => 'multiple'));

        // Subject.
        $mform->addElement('text', 'pulsenotification_subject', get_string('subject', 'pulseaction_notification'), ['size' => 100]);
        $mform->setType('pulsenotification_subject', PARAM_TEXT);

        $mform->addElement('editor', 'pulsenotification_headercontent_editor', get_string('headercontent', 'pulseaction_notification'), null, $this->get_editor_options());

        $mform->addElement('editor', 'pulsenotification_staticcontent_editor', get_string('staticcontent', 'pulseaction_notification'), null, $this->get_editor_options());

        // Dynamic content goes here.

        // Footer Content
        $mform->addElement('editor', 'pulsenotification_footercontent_editor', get_string('footercontent', 'pulseaction_notification'), null, $this->get_editor_options());

        // Preview Button
        $mform->addElement('button', 'pulsenotification_preview', get_string('preview', 'pulseaction_notification'));

        $mform->addElement('html', html_writer::end_div()); // E.O of actions triggere tab.

    }

    public function get_times() {

        $starttime = new DateTime('00:00'); // Set the start time to midnight
        $endtime = new DateTime('23:59');   // Set the end time to just before midnight of the next day

        // Create an interval of 30 minutes
        $interval = new \DateInterval('PT30M'); // PT30M represents 30 minutes

        // Create a DatePeriod to iterate through the day with the specified interval
        $timeperiod = new DatePeriod($starttime, $interval, $endtime);


        // Loop through the DatePeriod and add each timestamp to the array
        $timelist = array();
        foreach ($timeperiod as $time) {
            $timelist[$time->format('H:i')] = $time->format('H:i'); // Format the timestamp as HH:MM
        }

        return $timelist;
    }


}


