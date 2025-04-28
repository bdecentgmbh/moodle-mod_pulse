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
 * Send reminder notification to users filter by the users availability.
 *
 * @package   mod_pulse
 * @copyright 2024, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\addon;

defined('MOODLE_INTERNAL') || die('No direct access !');

use mod_pulse\helper;
use stdclass;

require_once($CFG->dirroot.'/mod/pulse/lib.php');

/**
 * Send reminder notification to users filter by the users availability.
 */
class notification {

    /**
     * Fetched complete record for all instances.
     *
     * @var array
     */
    private $records;

    /**
     * Module info sorted by course.
     *
     * @var mod_info|array
     */
    public $modinfo = [];

    /**
     * List of created pulse instances in LMS.
     *
     * @var array
     */
    protected $instances;

    /**
     * Pulse instance data record.
     *
     * @var object
     */
    public $instance;

    /**
     * Fetch all pulse instance data with course and context data.
     * Each instance are set to adhoc task to send reminders.
     *
     * @param  string $additionalwhere Additional where condition to filter the pulse record
     * @param  array $additionalparams Parameters for additional where clause.
     * @return array
     */
    public function get_instances($additionalwhere='', $additionalparams=[]) {
        global $DB;

        $select[] = 'pl.id AS id'; // Set the schdule id as unique column.

        // Get columns not increase table queries.
        // ...TODO: Fetch only used columns. Fetching all the fields in a query will make double the time of query result.
        $tables = [
            'pl' => $DB->get_columns('pulse'),
            'c' => $DB->get_columns('course'),
            'ctx' => $DB->get_columns('context'),
            'cm' => array_fill_keys(['id', 'course', 'module', 'instance'], ""), // Make the values as keys.
            'md' => array_fill_keys(['id', 'name'], ""),
        ];

        foreach ($tables as $prefix => $table) {
            $columns = array_keys($table);
            // Columns.
            array_walk($columns, function(&$value, $key, $prefix) {
                $value = "$prefix.$value AS ".$prefix."_$value";
            }, $prefix);

            $select = array_merge($select, $columns);
        }

        // Number of notification to send in this que.
        $limit = get_config('pulse', 'schedulecount') ?: 100;

        // Final list of select columns, convert to sql mode.
        $select = implode(', ', $select);

        $sql = "SELECT $select
                FROM {pulse} pl
                JOIN {course} c ON c.id = pl.course
                JOIN {course_modules} cm ON cm.instance = pl.id
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                JOIN {modules} md ON md.id = cm.module
                WHERE md.name = 'pulse' AND cm.visible = 1 AND c.visible = 1
                AND c.startdate <= :startdate AND (c.enddate = 0 OR c.enddate >= :enddate)";

        $sql .= $additionalwhere ? ' AND '.$additionalwhere : '';

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'startdate' => time(),
            'enddate' => time(),
        ];
        $params = array_merge($params, $additionalparams);
        $this->records = $DB->get_records_sql($sql, $params);

        if (empty($this->records)) {
            pulse_mtrace('No pulse instance are added yet'."\n");
            return false;
        }
        pulse_mtrace('Fetched available pulse modules');

        foreach ($this->records as $record) {

            $instance = new stdclass();
            $instance->pulse = (object) helper::filter_record_byprefix($record, 'pl');
            $instance->course = (object) helper::filter_record_byprefix($record, 'c');
            $instance->context = (object) helper::filter_record_byprefix($record, 'ctx');
            $cm = (object) helper::filter_record_byprefix($record, 'cm');
            $instance->module = (object) helper::filter_record_byprefix($record, 'md');
            $instance->record = $record;

            if (!in_array($instance->course->id, $this->modinfo)) {
                $this->modinfo[$instance->course->id] = get_fast_modinfo($instance->course->id, 0);
            }
            $instance->modinfo = $this->modinfo[$instance->course->id];

            if (!empty($cm->id) && !empty($this->modinfo[$instance->course->id]->cms[$cm->id])) {
                $instance->cmdata = $cm;
                $instance->cm = $instance->modinfo->get_cm($cm->id);
                // Fetch list of sender users for the instance.
                $instance->sender = \mod_pulse\task\sendinvitation::get_sender($instance->course->id);

                $this->instances[$instance->pulse->id] = $instance;
            }

        }

        return $this->instances;
    }

    /**
     * Setup the invitation reminder adhoc task for selected roles.
     * Users are filtered based on their module visibilty.
     *
     * @return void
     */
    public function send_invitations() {
        global $DB;

        $instances = $this->get_instances("pl.pulse=:enabled", ['enabled' => 1]);

        if (!empty($instances) && (is_array($instances) || is_object($instances))) {

            foreach ($instances as $pulseid => $instance) {

                // Selected roles for the reminder recipents.
                if (!$instance->pulse) {
                    continue;
                }

                pulse_mtrace('Start sending invitation for instance - '. $instance->pulse->name);

                pulse_mtrace('Sending invitation to students');
                self::set_invitation_adhoctask($instance);

            }
        }
    }


    /**
     * Get the invitation student users for the given instance and type.
     *
     * @param stdclass $instance The instance to get the student users for.
     * @return array|bool The list of student users or false if the instance is empty.
     */
    public function get_invitation_student_users($instance) {

        global $DB;

        if (empty($instance)) {
            return false;
        }

        $instance->type = 'invitation';
        $this->instance = $instance;

        $limit = get_config('mod_pulse', 'schedulecount') ?: 100;
        $context = \context_module::instance($this->instance->context->instanceid);

        $cap = 'mod/pulse:notifyuser';

        $additionalwhere[] = 'u.id IN (
            SELECT userid FROM {pulseaddon_availability} WHERE pulseid = :pulseidavail AND status = 1
        )';

        $additionalparams = ['pulseidavail' => $this->instance->pulse->id];

        $additionalwhere[] = 'u.id NOT IN (
            SELECT userid
            FROM {pulse_users}
            WHERE pulseid = :pulseidremain AND status = 1
        )';

        $additionalparams += ['pulseidremain' => $this->instance->pulse->id];

        // Get the role ids.
        $rolesql = "SELECT rc.id, rc.roleid FROM {role_capabilities} rc
                JOIN {capabilities} cap ON rc.capability = cap.name
                JOIN {context} ctx on rc.contextid = ctx.id
                WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:notifyuser']);
        $roles = array_column($roles, 'roleid');
        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'roleins');

        $contextlevel = explode('/', $this->instance->context->path);
        list($insql, $inparams) = $DB->get_in_or_equal(array_filter($contextlevel), SQL_PARAMS_NAMED, 'ins');

        $additionalwhere[] = "u.id IN (
            SELECT ra.userid
            FROM {role_assignments} ra
            JOIN {role} rle ON rle.id = ra.roleid
            WHERE contextid $insql AND ra.roleid $roleinsql
        )";

        $additionalparams += $roleinparams;
        $additionalparams += $inparams;

        $joins[] = 'JOIN {pulseaddon_availability} pla ON pla.userid = u.id AND pla.pulseid = :pulseidavail2';

        $joinparams = ['pulseidavail2' => $this->instance->pulse->id];

        $users = \mod_pulse\addon\util::get_enrolled_users_sql(
            $context, $cap, null, 'u.*, pla.availabletime, pla.status as isavailable', 'u.lastname, u.firstname',
            0, $limit, true, $additionalwhere, $additionalparams, $joins, $joinparams);

        return $users;
    }


    /**
     * Set adhoc task for reminders send message for each instance.
     *
     * @param  stdclass $instance Pulse instnace data.
     * @return void
     */
    public static function set_invitation_adhoctask($instance) {

        $task = new \mod_pulse\task\sendinvitation();

        if (!empty($instance)) {
            $data = (object) ['pulseid' => $instance->pulse->id, 'type' => 'invitation'];
            $task->set_custom_data($data);
            $task->set_component('pulseaddon_reminder');
            \core\task\manager::queue_adhoc_task($task, true);
        }
    }

}
