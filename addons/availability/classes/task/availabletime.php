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
 * Available time task observer.
 *
 * @package   pulseaddon_availability
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaddon_availability\task;

defined('MOODLE_INTERNAL') || die();

use mod_pulse\addon\util;

require_once($CFG->dirroot . '/mod/pulse/lib/locallib.php');
require_once($CFG->dirroot . '/mod/pulse/lib/mod_pulse_context_module.php');
use mod_pulse_context_module;

/**
 * Scheduled task to update the users pulse availability time.
 */
class availabletime extends \core\task\scheduled_task {
    /**
     * Task name defined.
     *
     * @return string name of the task.
     */
    public function get_name() {
        return get_string('reminders:availabletime', 'mod_pulse');
    }

    /**
     * Cron execution to setup the users availability time update adhoc task.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/mod/pulse/lib.php');

        $availabletime = new \mod_pulse\addon\notification();
        $instances = $availabletime->get_instances();

        $this->update_mod_availability($instances);
    }

    /**
     * Set the adhoc task to update the user activity available time.
     *
     * @param array $instances Pulse instances
     * @return void
     */
    public function update_mod_availability($instances) {

        if (empty($instances)) {
            return true;
        }

        $previousstudentscount = 0;

        $availability = new \pulseaddon_availability\task\availability();

        foreach ($instances as $pulseid => $instance) {
            $task = new \pulseaddon_availability\task\availability();
            $modulecontext = mod_pulse_context_module::create_instance_fromrecord($instance->context);
            $cap = 'mod/pulse:notifyuser';

            $limit = get_config('mod_pulse', 'schedulecount');
            $limit = (!empty($limit)) ? $limit : 100;

            $studentscount = self::get_students_count($instance, $instance->pulse->id);
            if ($studentscount <= $limit && $previousstudentscount + $studentscount < $limit) {
                $alreadyenabled[] = ' u.id NOT IN (
                                SELECT pa.userid FROM {pulseaddon_availability} pa
                                JOIN {pulse} p ON pa.pulseid = p.id
                                WHERE pulseid = :pulseid AND pa.availabletime > p.timemodified
                            )';
                $students = util::get_enrolled_users_sql(
                    $modulecontext,
                    $cap,
                    0,
                    'u.*',
                    null,
                    0,
                    $limit,
                    true,
                    $alreadyenabled,
                    ['pulseid' => $pulseid]
                );
                $instance->students = $students;
                // Update the available time.
                $availability->update_availability($instance);
                $previousstudentscount += $studentscount;

                continue;
            }

            $task->set_custom_data((object) ['pulseid' => $pulseid]);
            \core\task\manager::queue_adhoc_task($task, true);
        }
    }

    /**
     * Get count of students who need availability update
     *
     * @param object $instance Pulse instance object
     * @param int $pulseid ID of the pulse instance
     * @return int Count of students
     */
    public static function get_students_count($instance, $pulseid) {

        $modulecontext = mod_pulse_context_module::create_instance_fromrecord($instance->context);

        $cap = 'mod/pulse:notifyuser';
        $limit = get_config('mod_pulse', 'schedulecount');
        $limit = (!empty($limit)) ? $limit : 100;

        $alreadyenabled[] = '  u.id NOT IN (
                                SELECT pa.userid FROM {pulseaddon_availability} pa
                                JOIN {pulse} p ON pa.pulseid = p.id
                                WHERE pulseid = :pulseid AND pa.availabletime > p.timemodified
                            )';

        return util::count_enrolled_users_sql($modulecontext, $cap, 0, true, $alreadyenabled, ['pulseid' => $pulseid]);
    }
}
