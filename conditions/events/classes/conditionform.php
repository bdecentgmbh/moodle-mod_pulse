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
 * Conditions - Pulse condition class for the "Course Completion".
 *
 * @package   pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_events;

use core\context\user;
use core\reportbuilder\local\entities\context;
use mod_pulse\automation\condition_base;

/**
 * Automation events completion condition form.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Repersents the affected user receive the notification.
     * @var int
     */
    const AFFECTED_USER = 1;

    /**
     * Repersents the related user receive the notification.
     * @var int
     */
    const RELATED_USER = 2;


    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['events'] = get_string('eventscompletion', 'pulsecondition_events');
    }

    /**
     * Status of the condition addon works based on the user enrolment.
     *
     * @return bool
     */
    public function is_user_enrolment_based() {
        return false;
    }

    /**
     * Gets available options. For events upcoming will be in top.
     *
     * @return array List of options.
     */
    public function get_options() {
        return [
            self::DISABLED => get_string('disable'),
            self::FUTURE => get_string('upcoming', 'pulse'),
            self::ALL => get_string('all'),
        ];
    }

    /**
     * Delete the records of condition for the custom instance.
     *
     * @param int $instanceid
     * @return void
     */
    public function delete_condition_instance(int $instanceid) {
        global $DB;

        if ($DB->delete_records('pulsecondition_events', ['instanceid' => $instanceid])) {
            purge_caches(['muc', 'other']);
            return true;
        }

        return false;
    }

    /**
     * Loads the form elements for events completion condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('eventscompletion', 'pulsecondition_events');

        $mform->addElement('select', 'condition[events][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[events][status]', 'eventscompletion', 'pulsecondition_events');

        // Events list.
        $eventlist = self::eventslist();
        $mform->addElement('autocomplete', 'condition[events][event]',
                            get_string('selectevent', 'pulsecondition_events'), $eventlist);
        $mform->hideIf('condition[events][event]', 'condition[events][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[events][event]', 'selectevent', 'pulsecondition_events');

        // Select the which user has been recieve the notification.
        $option = [
            self::AFFECTED_USER => get_string('affecteduser', 'pulsecondition_events'),
            self::RELATED_USER => get_string('relateduser', 'pulsecondition_events'),
        ];

        $mform->addElement('select', 'condition[events][notifyuser]', get_string('notifyuser', 'pulsecondition_events'), $option);
        $mform->hideIf('condition[events][notifyuser]', 'condition[events][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[events][notifyuser]', 'notifyuser', 'pulsecondition_events');

        $mform->addElement('hidden', 'override[condition_events_event]', 1);
        $mform->setType('override[condition_events_event]', PARAM_INT);

        $mform->addElement('hidden', 'override[condition_events_notifyuser]', 1);
        $mform->setType('override[condition_events_notifyuser]', PARAM_INT);

        $courseid = $forminstance->get_customdata('courseid') ?? '';
        $modinfo = get_fast_modinfo($courseid);
        $cmlist = $modinfo->get_cms();
        $cmlist = array_map(fn($cm) => $cm->get_name(), $cmlist);

        $mform->addElement('autocomplete', 'condition[events][modules]',
            get_string('eventmodule', 'pulsecondition_events'), [0 => ''] + $cmlist);
        $mform->hideIf('condition[events][modules]', 'condition[events][status]', 'eq', self::DISABLED);

        $mform->addElement('hidden', 'override[condition_events_modules]', 1);
        $mform->setType('override[condition_events_modules]', PARAM_INT);
    }

    /**
     * Get the all event list form the moodle events list generator.
     *
     * @return array Events list.
     */
    public static function eventslist() {

        $completelist = \report_eventlist_list_generator::get_all_events_list();

        $list = [];
        foreach ($completelist as $key => $event) {
            $list[$key] = $event['raweventname'];
        }
        return $list;
    }

    /**
     * Verify the user is completed the events which is configured in the conditions for the notification.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if completed, false otherwise.
     */
    public function is_user_completed($instancedata, int $userid, \completion_info $completion=null) {
        global $DB;

        if (isset($instancedata->condition['events']) && $instancedata->condition['events']['status']) {
            $eventdata = $instancedata->condition['events'];
            list($sql, $params) = $this->generate_log_sql($eventdata, $userid, $instancedata);
            $status = $DB->record_exists_sql($sql, $params);
            return $status;
        }

        return false;
    }

    /**
     * Generate the log sql to fetch the event for the triggered event.
     *
     * @param array $eventdata
     * @param int $userid
     * @param stdClass $instancedata
     *
     * @return array
     */
    protected function generate_log_sql(array $eventdata, int $userid, $instancedata) {

        if (empty($eventdata)) {
            return [];
        }

        $sql = "SELECT *
            FROM {logstore_standard_log}
            WHERE eventname = :eventname ";

        $params['eventname'] = $eventdata['event'] ?? '';
        $params['userid'] = $userid;

        $notifyuser = $eventdata['notifyuser'] ?? 0;

        if ($notifyuser == self::AFFECTED_USER) {
            $sql .= " AND relateduserid = :userid ";
        } else if ($notifyuser == self::RELATED_USER) {
            $sql .= " AND userid = :userid ";
        }

        // Module configured.
        if (!empty($eventdata['modules'])) {
            $sql .= " AND contextinstanceid = :module";
            $params['module'] = $eventdata['modules'];
        }

        // Module not configured. then event should be core or the course id of the event is same as instance courseid.
        if (!empty($eventdata['modules'])) {
            $sql .= " AND (component = :core OR courseid = :courseid)";
            $params['courseid'] = $instancedata->courseid;
            $params['core'] = 'core';
        }

        // Upcoming condition check.
        if (!empty($eventdata['upcomingtime'])) {
            $sql .= 'AND timecreated >= :upcomingtime';
            $params['upcomingtime'] = $eventdata['upcomingtime'];
        }

        $sql .= ' ORDER BY id DESC ';

        return [$sql, $params];
    }

    /**
     * Pulse event condition trigger.
     *
     * @param stdclasss $eventdata event data.
     * @return bool
     */
    public static function pulse_event_condition_trigger($eventdata) {
        global $DB, $USER;

        $data = $eventdata->get_data();

        // Commit the database transaction.
        \core\event\manager::database_transaction_commited();

        // Events are stored in the log using the shutdown manager, it store the data end of the script.
        // Unfortuanlty the log will be stored to verify the event log to confirm the user is completed this conditions.
        // To store the data before, trigger the conditions check.
        // Fetch the log manager callback from shutdown handler and triggers the dispose method to store the event log data to DB.
        $obj = new \core_shutdown_manager();
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty('callbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($obj);

        // Get the log manager and trigger the dispose method.
        foreach ($callbacks as $lists) {
            foreach ($lists as $methods) {
                if (empty($methods)) {
                    continue;
                }
                list($callback, $method) = $methods;
                if ($callback instanceof \tool_log\log\manager) {
                    // Dispose the log manager to store the event entries.
                    $callback->dispose();
                    break;
                }
            }
        }

        $eventname = $eventdata->eventname ?? '';

        // Trigger the instances, this will trigger its related actions for this user.
        $instances = self::get_events_notifications($eventname);

        // Self condition instance.
        $condition = new self();

        foreach ($instances as $key => $instance) {

            $additional = $instance->additional ? json_decode($instance->additional) : (object)[];

            // Module configured for this instance event, and the event is not for this module, continue to next instance.
            if (property_exists($additional, 'modules') && $additional->modules &&
                $additional->modules !== $data['contextinstanceid']) {
                continue;
            }

            // Modules not configured, component of this event is not core, and the event course id is not this course.
            // Continue to next instance.
            if ((!property_exists($additional, 'modules') || !$additional->modules)
                && $data['component'] != 'core' && $data['courseid'] != $instance->courseid) {
                continue;
            }

            $notifyuser = $additional->notifyuser ?? 0;

            if ($notifyuser == self::AFFECTED_USER) {
                $userid = $data['relateduserid'] ?? $USER->id;
            } else if ($notifyuser == self::RELATED_USER) {
                $userid = $data['userid'] ?? $USER->id;
            }

            // TODO: Condition status check.
            $condition->trigger_instance($instance->instanceid, $userid);
        }
        return true;

    }

    /**
     * Fetch the list of instances which is used the triggered event in the access rules for the given method.
     *
     * Find the instance which contains the given event in the access rule (events).
     *
     * @param string $eventname name of the triggered event.
     * @return array
     */
    public static function get_events_notifications($eventname) {
        global $DB;

        $name = stripslashes($eventname);

        $like = $DB->sql_like('eve.eventname', ':value'); // Like query to fetch the instances assigned this event.
        $eventlike = $DB->sql_like('pat.triggerconditions', ':events');

        $sql = "SELECT *, ai.id as id, ai.id as instanceid FROM {pulse_autoinstances} ai
            JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
            LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'events'
            LEFT JOIN {pulsecondition_events} eve ON eve.instanceid = ai.id
            WHERE $like AND (co.status > 0 OR $eventlike)";

        $params = ['events' => '%"events"%', 'value' => $name];

        $records = $DB->get_records_sql($sql, $params);

        return $records;
    }

    /**
     * Fetch the events data form the condition overrides table.
     *
     * @return array $list event list
     */
    public static function get_events() {
        global $DB;

        $list = [];
        $events = []; // Events added for observe.

        $eventscoditiondata = $DB->get_records('pulse_condition_overrides', ['triggercondition' => 'events']);
        foreach ($eventscoditiondata as $data) {
            $additional = json_decode($data->additional);
            if (!isset($additional->event) || $additional->event == '') {
                continue;
            }

            // Verify the event is already observed in the pulse event condition, to prevent multiple observe of single event.
            if (in_array($additional->event, $events)) {
                continue;
            }

            $list[] = [
                'eventname' => $additional->event,
                'callback' => '\pulsecondition_events\conditionform::pulse_event_condition_trigger',
            ];

            // Prevent multiple event observer for one event.
            $events[] = $additional->event;
        }
        return $list ?? [];
    }

    /**
     * Schedule override join.
     *
     * @return array
     */
    public function schedule_override_join() {

        return [
            'event.status as event_status, event.additional as event_additional, event.isoverridden as event_isoverridden',
            "LEFT JOIN {pulse_condition_overrides} event ON event.instanceid = pati.instanceid AND
                event.triggercondition = 'events'",
        ];
    }

    /**
     * List of placeholders rendered form the events.
     *
     * @return array
     */
    public function get_email_placeholders() {

        $vars = [
            "Event_Name",
            "Event_Namelinked",
            "Event_Description",
            "Event_Time",
            "Event_Context",
            "Event_Contextlinked",
            "Event_Affecteduserfullname",
            "Event_Affecteduserfullnamelinked",
            "Event_Relateduserfullname",
            "Event_Relateduserfullnamelinked",
        ];
        return ['Event' => $vars];
    }

    /**
     * Update email custom vars.
     *
     * @param int $userid
     * @param stdClass $instancedata
     * @param stdClass $schedulerecord
     * @return void
     */
    public function update_email_customvars($userid, $instancedata, $schedulerecord) {
        global $DB, $OUTPUT;
        // Check the event condition are set for this notification. if its added then load the event data for placeholders.
        $eventin = in_array('events', (array) $instancedata->template->triggerconditions);
        $eventin = (property_exists($schedulerecord, 'event_isoverridden') && $schedulerecord->event_isoverridden == 1)
            ? $schedulerecord->event_status : $eventin;

        if ($eventin) {

            $eventdata = (array) json_decode($schedulerecord->event_additional);

            list($sql, $params) = $this->generate_log_sql($eventdata, $userid, $instancedata);
            $record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

            if (empty($record)) {
                return [];
            }

            $logmanager = get_log_manager();
            $event = (new \logstore_database\log\store($logmanager))->get_log_event($record);

            if ($event == null) {
                return [];
            }

            $vars = [];
            $vars['name'] = $event->get_name();

            // Only encode as an action link if we're not downloading.
            if ($url = $event->get_url()) {
                $link = new \action_link($url, $vars['name'],
                    new \popup_action('click', $url, 'action', ['height' => 440, 'width' => 700]));
                $vars['namelinked'] = $OUTPUT->render($link);
            }

            $vars['description'] = $event->get_description();

            // Event time.
            $dateformat = get_string('strftimedatetimeaccurate', 'core_langconfig');
            $vars['time'] = userdate($event->timecreated, $dateformat);

            // Event_Context.
            if ($event->contextid) {
                // If context name was fetched before then return, else get one.
                $context = \context::instance_by_id($event->contextid, IGNORE_MISSING);
                $vars['context'] = ($context) ? $context->get_context_name(true) : get_string('other');

                // Event_Contextlinked.
                if ($context instanceof \context) {
                    if ($url = $context->get_url()) {
                        $vars['contextlinked'] = \html_writer::link($url, $vars['context']);
                    }
                }
            }
            // Event_Affecteduserfullname.
            if (!empty($event->relateduserid)) {
                $vars['affecteduserfullname'] = $this->get_user_fullname($event->relateduserid);
                $params = ['id' => $event->relateduserid];
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                // Event_Affecteduserfullnamelinked.
                $vars['affecteduserfullnamelinked'] = \html_writer::link(
                    new \moodle_url('/user/view.php', $params), $vars['affecteduserfullname']);
            }
            // Event_Relateduserfullname.
            if (!empty($event->userid) && $vars['relateduserfullname'] = $this->get_user_fullname($event->userid)) {
                $params = ['id' => $event->userid];
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                $vars['relateduserfullnamelinked'] = \html_writer::link(
                    new \moodle_url('/user/view.php', $params), $vars['relateduserfullname']);
            }

            return ['event' => $vars];
        }

        return [];
    }

    /**
     * Gets the user full name.
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullnames it will fetch it from db.
     *
     * @since Moodle 2.9
     * @param int $userid
     * @return string|false
     */
    protected function get_user_fullname($userid) {
        global $PAGE;

        if (empty($userid)) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        $userfieldsapi = \core_user\fields::for_name();
        $fields = $userfieldsapi->get_sql('', false, '', '', false)->selects;
        if ($user = \core_user::get_user($userid, $fields)) {
            $userfullname = fullname($user, has_capability('moodle/site:viewfullnames', $PAGE->context));
        } else {
            $userfullname = false;
        }

        return $userfullname;
    }

    /**
     * Defined the strucure of tables for the backup.
     *
     * @param [type] $instances
     * @return void
     */
    public function backup_define_structure(&$instances) {
        global $DB;

         // Automation templates.
        $events = new \backup_nested_element('automationtemplates');
        $eventsfields = new \backup_nested_element('pulse_autotemplates', ['id'], [
            "instanceid", "eventname", "notifyuser",
        ]);

        $instances->add_child($events);
        $events->add_child($eventsfields);

        $eventsfields->set_source_table('pulsecondition_events', ['instanceid' => \backup::VAR_PARENTID]);
    }

    /**
     * After save the condition form, clear the observers from cache and recreated the list.
     *
     * @param int $instanceid
     * @param object $data
     * @return void
     */
    public function process_instance_save($instanceid, $data) {
        parent::process_instance_save($instanceid, $data);

        // Remove the event observers and recreate.
        $cache = \cache::make('core', 'observers');
        $cache->delete('all');
        // Build the observers again.
        $list = \core\event\manager::get_all_observers();
        $cache->set('all', $list);
        purge_caches(['muc', 'other']);
    }
}
