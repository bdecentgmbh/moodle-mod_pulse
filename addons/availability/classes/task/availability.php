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
 * Update pulse avaiblibity time for User.
 *
 * @package   pulseaddon_availability
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaddon_availability\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pulse/lib/locallib.php');

use mod_pulse\addon\notification;
use mod_pulse\addon\util;
use stdclass;

/**
 * Update the user instance availability with time for all pulse instance.
 */
class availability extends \core\task\adhoc_task {

    /**
     * Execution part of the adhoc task.
     *
     * Filter the users available time and updated the users status.
     *
     * @return void
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/pulse/lib.php');

        $this->get_instance_data();

        return true;
    }

    /**
     * Get the instance data.
     *
     * @return void
     */
    public function get_instance_data() {
        global $DB;

        $customdata = $this->get_custom_data();
        $pulseid = isset($customdata->pulse->id) ? $customdata->pulse->id : $customdata->pulseid;

        if ($pulseid) {
            $instances = (new notification())->get_instances('pl.id = :pulseid', ['pulseid' => $pulseid]);
            $instance = $instances[$pulseid] ?? [];

            if (empty($instance)) {
                return false;
            }

            $this->get_students($instance);
            $this->update_availability($instance);
        }
    }

    /**
     * Get the students.
     *
     * @param stdclass $instance
     *
     * @return void
     */
    protected function get_students(&$instance) {
        global $DB;

        $modulecontext = \mod_pulse_context_module::create_instance_fromrecord($instance->context);

        $cap = 'mod/pulse:notifyuser';
        $limit = get_config('mod_pulse', 'schedulecount');
        $limit = (!empty($limit)) ? $limit : 100;

        $alreadyenabled[] = ' u.id NOT IN (
                                    SELECT pa.userid FROM {pulseaddon_availability} pa
                                    JOIN {pulse} p ON pa.pulseid = p.id
                                    WHERE pulseid = :pulseid AND pa.availabletime > p.timemodified AND pa.status = 1
                                )';
        $students = util::get_enrolled_users_sql(
            $modulecontext, $cap, 0, 'u.*', null, 0, $limit, true, $alreadyenabled, ['pulseid' => $instance->pulse->id]);

        $instance->students = $students;
    }

    /**
     * Update the user availability in module.
     *
     * @param stdclass $instance
     * @return void
     */
    public function update_availability($instance) {
        global $DB;

        if (!$pulse = $DB->get_record('pulse', ['id' => $instance->pulse->id])
            || !$DB->get_record('course', ['id' => $instance->course->id]) ) {
            pulse_mtrace('Not found Course:'.$instance->course->id.' or Pulse:'.$instance->pulse->id);
            return true;
        }

        $insertrecords = [];
        if (!empty($instance->students)) {
            $pulseid = $instance->pulse->id;
            $students = array_keys((array) $instance->students);

            $availabilityrecords = $this->fetch_availability_records($pulseid, $students);

            if (isset($instance->cmdata->id)) {

                pulse_mtrace('Update availability init for the instance - '. $instance->cmdata->id);

                $modinfo = new \mod_pulse\pulse_course_modinfo((object) $instance->course, 0);
                $cm = $modinfo->get_cm($instance->cmdata->id);

                $info = new \core_availability\info_module($cm);
                $section = $cm->get_section_info();
                $sectioninfo = new \core_availability\info_section($section);

                foreach ($instance->students as $userid => $user) {

                    pulse_mtrace('Updating user status in pulse availability - '.$userid);

                    $modinfo->set_userid($userid);
                    $status = ($this->find_user_visible($cm, $userid, $modinfo, $sectioninfo, $info)) ? 1 : 0;

                    if (in_array($userid, array_keys($availabilityrecords))) {
                        $record = $availabilityrecords[$userid];
                        if ($status != $record->status) {
                            $record->status = $status;
                            $record->availabletime = time();
                            $DB->update_record('pulseaddon_availability', $record);
                        }
                    } else {
                        $this->prepare_availabilityinsert($pulseid, $userid, $status, $insertrecords);
                    }
                }

                if (!empty($insertrecords)) {
                    $DB->insert_records('pulseaddon_availability', $insertrecords);
                }
            }

            // Reschedule the task for next set of users.
            $task = new \pulseaddon_availability\task\availability();
            $task->set_custom_data((object) ['pulseid' => $instance->pulse->id]);
            \core\task\manager::reschedule_or_queue_adhoc_task($task);
        }

        return true;
    }

    /**
     * Check is user has access to the module.
     *
     * @param cm_info $cm Course module info.
     * @param int $userid USER object id
     * @param course_modinfo $modinfo course module info
     * @param section_info $sectioninfo Section availability info
     * @param availability_info $info Module availability info
     * @return bool User visibility.
     */
    public function find_user_visible($cm, $userid, $modinfo, $sectioninfo, $info) {
        $context = $cm->context;
        if ((!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', $context, $userid))) {
            return false;
        }

        $str = '';
        if ($sectioninfo->is_available($str, false, $userid, $modinfo)
            && $info->is_available($str, false, $userid, $modinfo )) {
            return true;
        }
        return false;
    }

    /**
     * Fetch the available users records from custom module availability table.
     *
     * @param int $pulseid Pulse instance id.
     * @param array $students Enrolled users list to fetch.
     * @return array $newrecord List of users availability.
     */
    public function fetch_availability_records($pulseid, $students) {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED, 'userid');
        $inparams['pulseid'] = $pulseid;

        $sql = 'SELECT * FROM {pulseaddon_availability} WHERE pulseid=:pulseid AND userid ' . $insql;
        $records = $DB->get_records_sql($sql, $inparams);

        $newrecord = [];
        foreach ($records as $key => $record) {
            $newrecord[$record->userid] = $record;
        }

        return $newrecord;
    }

    /**
     * Fetch the list of users who has access to the mentiond pulse.
     *
     * @param int $pulseid Pulse instance id.
     * @return array $newrecord List of users availability.
     */
    public static function fetch_available_users($pulseid) {
        global $DB;

        $params = ['pulseid' => $pulseid, 'status' => 1];
        $records = $DB->get_records('pulseaddon_availability', $params);
        $newrecord = [];
        foreach ($records as $key => $record) {
            $newrecord[$record->userid] = $record;
        }
        return $newrecord;
    }

    /**
     * Prepare the data set to insert in availability records.
     *
     * @param int $pulseid Pulse instance id.
     * @param int $userid User record id.
     * @param int $status Status of module availability.
     * @param array $insertrecords List of records to insert.
     * @return void
     */
    public function prepare_availabilityinsert($pulseid, $userid, $status, &$insertrecords) {
        $record = new stdclass();
        $record->status = $status;
        $record->pulseid = $pulseid;
        $record->userid = $userid;
        $record->availabletime = time();
        $insertrecords[] = (array) $record;
    }

    /**
     * Update the pulse instance available time and status for the user in availability table. This time used to
     * calculate the relative date for sending reminders to users.
     *
     * @param  int $pulseid Pulse instance id
     * @param  int $userid User id.
     * @param  bool $status true|false User visibility status for this instance.
     * @return void
     */
    public function update_user_visible(int $pulseid, int $userid, bool $status): void {
        global $DB;
        // Check module availability already added for user.
        if ($record = $DB->get_record('pulseaddon_availability', ['userid' => $userid, 'pulseid' => $pulseid])) {
            // Only update the availabilty status if both are different.
            // Otherwise don't need to update anything.
            if ($record->status != $status) {
                $record->status = $status;
                $record->availabletime = time();
                $DB->update_record('pulseaddon_availability', $record);
            }

        } else {
            $record = new stdclass();
            $record->status = $status;
            $record->pulseid = $pulseid;
            $record->userid = $userid;
            $record->availabletime = time();
            $DB->insert_record('pulseaddon_availability', (array) $record);
        }
    }
}
