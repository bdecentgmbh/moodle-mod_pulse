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
 * Scheduled cron task to completion pulse.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/pulse/lib.php');

/**
 * Update user completion status for pulse. triggered from scheduled task.
 */
class update_completion extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatecompletion', 'mod_pulse');
    }

    /**
     * Cron execution to send the available pulses.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        $this->mod_pulse_completion_crontask();
    }

    /**
     * Cron task for completion check for students in all pulse module.
     *
     * @return void
     */
    public function mod_pulse_completion_crontask() {
        global $DB, $USER, $CFG;

        pulse_mtrace('Pulse activity completion - Pulse Starting');

        pulse_mtrace('Fetching pulse instance list - MOD-Pulse INIT');

        $rolesql = "SELECT  rc.roleid FROM {role_capabilities} rc
                JOIN {capabilities} cap ON rc.capability = cap.name
                JOIN {context} ctx on rc.contextid = ctx.id
                WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:notifyuser']);
        $roles = array_column($roles, 'roleid');

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);

        $sql = "SELECT nt.id as nid, nt.*, '' as pulseend, cm.id as cmid, cm.*, md.id as mid,
        ctx.id as contextid, ctx.*, cu.id as courseid, cu.*
        FROM {pulse} nt
        JOIN {course_modules} cm ON cm.instance = nt.id
        JOIN {modules} md ON md.id = cm.module
        JOIN {course} cu on cu.id = nt.course
        RIGHT JOIN {context} ctx on ctx.instanceid = cm.id and contextlevel = 70
        WHERE md.name = 'pulse' AND cu.visible = 1 AND cu.startdate <= :startdate AND  (cu.enddate = 0 OR cu.enddate >= :enddate)";

        // Completion available criteria is only based on restrictions and others are based on actions.
        // Approval, Self completion are updated when the action is triggered.
        $sql .= ' AND nt.completionavailable = 1';
        $records = $DB->get_records_sql($sql, ['startdate' => time(), 'enddate' => time()]);

        if (empty($records)) {
            pulse_mtrace('No pulse instance are added yet'."\n");
            return true;
        }
        $modinfo = [];
        foreach ($records as $key => $record) {

            $params = [];
            $record = (array) $record;
            $keys = array_keys($record);
            // Pulse.
            $pulseendpos = array_search('pulseend', $keys);
            $pulse = array_slice($record, 0, $pulseendpos);
            $pulse['id'] = $pulse['nid'];

            pulse_mtrace("Check the user module completion - Pulse name: ".$pulse['name']);
            // Precess results.
            list($course, $context, $cm) = pulse_process_recorddata($keys, $record);
            // Get enrolled users with capability.
            $contextlevel = explode('/', $context['path']);
            list($insql, $inparams) = $DB->get_in_or_equal(array_filter($contextlevel));

            // Enrolled  users list.
            $usersql = "SELECT u.*, je.*
                    FROM {user} u
                    JOIN (
                        SELECT DISTINCT eu1_u.id, pc.id as pcid, pc.userid as userid, pc.pulseid,
                        pc.approvalstatus, pc.selfcompletion, cmc.id as coursemodulecompletionid,
                        cmc.completionstate as completionstate
                            FROM {user} eu1_u
                            JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                            JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
                            LEFT JOIN {pulse_completion} pc ON pc.userid = eu1_u.id AND pc.pulseid = ?
                            LEFT JOIN {course_modules_completion} cmc ON cmc.userid = eu1_u.id AND cmc.coursemoduleid = ?
                            JOIN (SELECT DISTINCT userid
                                            FROM {role_assignments}
                                            WHERE contextid $insql
                                            AND roleid $roleinsql
                                        ) ra ON ra.userid = eu1_u.id
                        WHERE 1 = 1 AND ej1_ue.status = 0
                        AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= ?)
                        AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > ?)
                        AND eu1_u.deleted = 0 AND eu1_u.id <> ? AND eu1_u.deleted = 0 AND eu1_u.suspended = 0
                    ) je ON je.id = u.id
                WHERE u.deleted = 0 AND u.suspended = 0 ORDER BY u.lastname, u.firstname, u.id";

            $params[] = $course['id'];
            $params[] = $cm['instance'];
            $params[] = $cm['id'];
            $params = array_merge($params, array_filter($inparams));
            $params = array_merge($params, array_filter($roleinparams));
            $params[] = time();
            $params[] = time();
            $params[] = 1;

            $courseid = $pulse['course'];
            $course = (object) $course;
            $pulse = (object) $pulse;

            if (!in_array($courseid, $modinfo)) {
                $modinfo[$courseid] = new \pulse_course_modinfo($course, 0);
            }

            if (empty($modinfo[$courseid]->cms[$cm['id']])) {
                continue;
            }
            $cm         = $modinfo[$course->id]->get_cm($cm['id']);
            $completion = new \completion_info($course);
            if (!$completion->is_enabled($cm)) {
                continue;
            }
            $students = $DB->get_records_sql($usersql, $params);
            pulse_mtrace('- ' . count($students) . ' user(s) to process');

            if (!empty($students)) {
                $completion = new \completion_info($course);
                $context = \context_module::instance($cm->id);
                foreach ($students as $key => $user) {
                    $modinfo[$course->id]->set_userid($user->id);
                    $md = $modinfo[$course->id];
                    // Get pulse module completion state for user.
                    $currentstate = ($user->completionstate) ?? COMPLETION_INCOMPLETE;
                    $result = pulse_get_completion_state($course, $cm, $user->id, $currentstate, $pulse, $user, $md);
                    if (isset($user->completionstate) && $result == $currentstate) {
                        continue;
                    }

                    if ($user->coursemodulecompletionid === null && $result > 0) { // ADD.
                        pulse_mtrace("-- Added completion [NEW => $result] - user " . $user->id);
                        $completion->update_state($cm, $result, $user->id);
                    } else if ((int) $user->completionstate !== (int) $result) { // UPDATE.
                        pulse_mtrace("-- Updated completion [OLD => $user->completionstate, NEW => $result] - user " . $user->id);
                        $completion->update_state($cm, $result, $user->id);
                    }
                }
            } else {
                pulse_mtrace('There is not users to update pulse module completion');
            }
        }

        pulse_mtrace('Course module completions are updated for all pulse module....');
        return true;
    }
}
