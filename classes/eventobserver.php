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
 * Event observer class definition.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

/**
 * Observer class for the course module deleted and user enrolment deleted events. It will remove the user data from pulse.
 */
class eventobserver {

    /**
     * course module deleted event observer.
     * Remove the user and instance records for the deleted modules from pulsepro tables.
     *
     * @param  mixed $event
     * @return void
     */
    public static function course_module_deleted($event) {
        global $CFG, $DB;
        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];
            $courseid = $event->courseid;
            // Remove pulse user completion records.
            if ($DB->record_exists('pulse_completion', ['pulseid' => $pulseid])) {
                $DB->delete_records('pulse_completion', ['pulseid' => $pulseid]);
            }

            // Remove pulse user notified records.
            if ($DB->record_exists('pulse_users', ['pulseid' => $pulseid])) {
                $DB->delete_records('pulse_users', ['pulseid' => $pulseid]);
            }
        }
    }

    /**
     * User unenrolled event observer.
     * Remove the unenrolled user records related to all the instnace created in the course form the table records.
     *
     *
     * @param  mixed $event
     * @return bool true
     */
    public static function user_enrolment_deleted($event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/mod/pulse/lib.php');
        $userid = $event->relateduserid; // Unenrolled user id.
        $courseid = $event->courseid;
        // Retrive list of pulse instance added in course.
        $list = \mod_pulse\helper::course_instancelist($courseid);
        if (!empty($list)) {
            $pulselist = array_column($list, 'instance');
            list($insql, $inparams) = $DB->get_in_or_equal($pulselist);
            $inparams[] = $userid;
            $select = " pulseid $insql AND userid = ? ";
            // Remove the user completion records.
            $DB->delete_records_select('pulse_completion', $select, $inparams);
            $DB->delete_records_select('pulse_users', $select, $inparams);
        }

        self::trigger_action_event('user_enrolment_deleted', $event);

        return true;
    }

    /**
     * User enrolment trigger actions.
     *
     * @param [type] $event
     * @return void
     */
    public static function user_enrolment_created($event) {
        $userid = $event->relateduserid; // Unenrolled user id.
        $courseid = $event->courseid;

        $list = \mod_pulse\automation\helper::get_course_instances($courseid);
        if (!empty($list)) {
            foreach ($list as $instanceid => $instance) {
                \mod_pulse\automation\instances::create($instanceid)->trigger_action($userid, null, true);
            }
        }
    }

    /**
     * Trigger an action event for all instances in a course.
     *
     * @param string $method The method to trigger.
     * @param stdClass $event The event object.
     */
    public static function trigger_action_event($method, $event) {

        $courseid = $event->courseid;

        $list = \mod_pulse\automation\helper::get_course_instances($courseid);
        if (!empty($list)) {
            foreach ($list as $instanceid => $instance) {
                \mod_pulse\automation\instances::create($instanceid)->trigger_action_event($method, $event);
            }
        }
    }
}
