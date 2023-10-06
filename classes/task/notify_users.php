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
 * Scheduled cron task to send pulse.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/pulse/lib.php');

/**
 * Send notification to users - scheduled task execution observer.
 */
class notify_users extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('notifyusers', 'mod_pulse');
    }

    /**
     * Cron execution to send the available pulses.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        self::pulse_cron_task();
    }

    /**
     * Pulse cron task to send notification for course users.
     *
     * Here users are filtered by their activity avaialbility status.
     * if the pulse instance are available to user then it will send the notificaion to the user.
     *
     * @param  mixed $extend Extend the pro invitation method.
     * @return void
     */
    public static function pulse_cron_task($extend=true) {
        global $DB;

        pulse_mtrace( 'Fetching notificaion instance list - MOD-Pulse INIT ');

        if ($extend && \mod_pulse\extendpro::pulse_extend_invitation()) {
            return true;
        }

        $rolesql = "SELECT rc.id, rc.roleid FROM {role_capabilities} rc
                JOIN {capabilities} cap ON rc.capability = cap.name
                JOIN {context} ctx on rc.contextid = ctx.id
                WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:notifyuser']);
        $roles = array_column($roles, 'roleid');

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);

        $sql = "SELECT nt.id AS nid, nt.*, '' AS pulseend,
            cm.id as cmid, cm.*, md.id AS mid,
            ctx.id as contextid, ctx.*, cu.id as courseid, cu.*,
            cu.idnumber as courseidnumber, cu.groupmode as coursegroupmode FROM {pulse} nt
            JOIN {course_modules} cm ON cm.instance = nt.id
            JOIN {modules} md ON md.id = cm.module
            JOIN {course} cu ON cu.id = nt.course
            RIGHT JOIN {context} ctx ON ctx.instanceid = cm.id and contextlevel = 70
            WHERE md.name = 'pulse' AND cm.visible = 1 AND cu.visible = 1
            AND cu.startdate <= :startdate AND  (cu.enddate = 0 OR cu.enddate >= :enddate)";

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
            // Context.
            $ctxpos = array_search('contextid', $keys);
            $ctxendpos = array_search('locked', $keys);
            $context = array_slice($record, $ctxpos, ($ctxendpos - $ctxpos) + 1 );
            $context['id'] = $context['contextid']; unset($context['contextid']);
            // Course module.
            $cmpos = array_search('cmid', $keys);
            $cmendpos = array_search('deletioninprogress', $keys);
            $cm = array_slice($record, $cmpos, ($cmendpos - $cmpos) + 1 );
            $cm['id'] = $cm['cmid']; unset($cm['cmid']);
            // Course records.
            $coursepos = array_search('courseid', $keys);
            $course = array_slice($record, $coursepos);
            $course['id'] = $course['courseid'];
            $course['groupmode'] = isset($course['coursegroupmode']) ? $course['coursegroupmode'] : '';
            $course['idnumber'] = isset($course['courseidnumber']) ? $course['courseidnumber'] : '';
            pulse_mtrace( 'Initiate pulse module - '.$pulse['name'].' course - '. $course['id'] );
            // Get enrolled users with capability.
            $contextlevel = explode('/', $context['path']);
            list($insql, $inparams) = $DB->get_in_or_equal(array_filter($contextlevel));
            // Enrolled  users list.
            $usersql = "SELECT u.*
                    FROM {user} u
                    JOIN (SELECT DISTINCT eu1_u.id
                    FROM {user} eu1_u
                    JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
                    JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
                    JOIN (SELECT DISTINCT userid
                            FROM {role_assignments}
                            WHERE contextid $insql
                            AND roleid $roleinsql GROUP BY userid
                        ) ra ON ra.userid = eu1_u.id
                WHERE 1 = 1 AND ej1_ue.status = 0
                AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= ?)
                AND (ej1_ue.timeend = 0 OR ej1_ue.timeend > ?)
                AND eu1_u.deleted = 0 AND eu1_u.id <> ? AND eu1_u.deleted = 0) je ON je.id = u.id
                WHERE u.deleted = 0 AND u.suspended = 0 ORDER BY u.lastname, u.firstname, u.id";

            $params[] = $course['id'];
            $params = array_merge($params, array_filter($inparams));
            $params = array_merge($params, array_filter($roleinparams));
            $params[] = time();
            $params[] = time();
            $params[] = 1;
            $students = $DB->get_records_sql($usersql, $params);

            $courseid = $pulse['course'];

            $instance = new \stdclass();
            $instance->pulse = (object) $pulse;
            $instance->course = (object) $course;
            $instance->context = (object) $context;
            $instance->cm = (object) $cm;
            $instance->students = $students;
            self::pulse_set_notification_adhoc($instance);
        }
        pulse_mtrace('Pulse message sending completed....');
        return true;
    }

    /**
     * Set adhoc task to send reminder notification for each instance
     *
     * @param  mixed $instance
     * @return void
     */
    public static function pulse_set_notification_adhoc($instance) {
        $task = new \mod_pulse\task\sendinvitation();
        $task->set_custom_data($instance);
        $task->set_component('pulse');
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
