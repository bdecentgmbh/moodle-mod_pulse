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
 * Scheduled adhoc task to send pulse.
 *
 * @package   mod_pulse/sendinvitation
 * @category  cron
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\task;

class sendinvitation extends \core\task\adhoc_task {

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/mod/pulse/lib.php');
        $instance = $this->get_custom_data();
        // Check pulse enabled.

        // Filter users from course pariticipants by completion.
        $listofusers = mod_pulse_get_course_students((array) $instance->students, $instance);
        // Extend the pulse pro version to send notifications on selected recipients.
        if (!empty($listofusers)) {
            $this->send_pulse($listofusers, $instance->pulse, $instance->course, $instance->context);
        } else {
            mtrace('There is not users to send pulse');
        }
    }


    /**
     * Process template text and send pulse to the course users.
     *
     * @param  mixed $pulseid
     * @return void
     */
    public function send_pulse($users, $pulse, $course, $context) {
        global $DB;
        if (!empty($pulse) && !empty($users)) {
            // Get course module using instanceid.
            $sender = self::get_sender($course->id, $context->id);
            if ($pulse->pulse == true) {
                $notifiedusers = [];
                // Collect list of available enrolled students in course module.
                mtrace('Sending pulse to enrolled users in course '.$course->fullname."\n");
                foreach ($users as $key => $student) {
                    $sender = self::find_user_sender($sender, $student->id);
                    $userto = $student; // Send to.
                    $subject = $pulse->pulse_subject ?: get_string('pulse_subject', 'pulse'); // Message subject.
                    // Use intro content as message text, if different pulse disabled.
                    $template = $pulse->intro;
                    $filearea = 'intro';
                    if ($pulse->diff_pulse) {
                        // Email template content.
                        $template = $pulse->pulse_content;
                        $subject = $pulse->name;
                        $filearea = 'pulse_content';
                    }
                    // Replace the email text placeholders with data.
                    list($subject, $messagehtml) = mod_pulse_update_emailvars($template, $subject, $course,
                        $student, $pulse, $sender);
                    // Rewrite the plugin file placeholders in the email text.
                    $messagehtml = file_rewrite_pluginfile_urls($messagehtml, 'pluginfile.php',
                        $context->id, 'mod_pulse', $filearea, 0);
                    $messageplain = html_to_text($messagehtml); // Plain text.
                    // Send message to user.
                    mtrace("Sending pulse to the user ". fullname($userto) ."\n" );

                    $messagesend = mod_pulse_messagetouser($userto, $subject, $messageplain, $messagehtml, $pulse, $sender);
                    if ($messagesend) {
                        $notifiedusers[] = $userto->id;
                    }
                }
                if ($addnotify) {
                    mod_pulse_update_notified_users($notifiedusers, $pulse);
                }
            }
        }
    }

    public static function find_user_sender($senderdata, $userid) {

        if (!empty($senderdata->groupcontact)) {
            $groups = $senderdata->groupcontact;
            foreach ($groups as $groupid => $group) {
                // Check student assigned in any group.
                if (isset($group->students) && in_array($userid, $group->students)) {
                    if (!empty($group->sender)) { // Group has any teacher role to send notification.
                        return $group->sender;
                    }
                }
            }
        }

        return (!empty($senderdata->coursecontact) ? $senderdata->coursecontact : \core_user::get_support_user());
    }


    public static function get_sender($courseid) {
        global $DB;
        $rolesql = "SELECT  rc.roleid FROM {role_capabilities} rc
        JOIN {capabilities} cap ON rc.capability = cap.name
        JOIN {context} ctx on rc.contextid = ctx.id
        WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:addinstance']);
        $roles = array_column($roles, 'roleid');

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);
        $contextid = \context_course::instance($courseid)->id;
        $usersql = "SELECT DISTINCT eu1_u.*, ra.*
        FROM {user} eu1_u
        JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
        JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
        JOIN (
            SELECT DISTINCT userid, rle.shortname as roleshortname, roleid
                FROM {role_assignments}
                JOIN {role} rle ON rle.id = roleid
                WHERE contextid = ? AND roleid $roleinsql GROUP BY userid
            ) ra ON ra.userid = eu1_u.id
        WHERE 1 = 1 AND ej1_ue.status = 0
        AND (ej1_ue.timestart = 0 OR ej1_ue.timestart <= UNIX_TIMESTAMP(NOW()))
        AND ( ej1_ue.timeend = 0 OR ej1_ue.timeend > UNIX_TIMESTAMP(NOW()) )
        AND eu1_u.deleted = 0 AND eu1_u.suspended = 0 ORDER BY ej1_ue.timestart, ej1_ue.timecreated";

        array_unshift($roleinparams, $contextid);
        array_unshift($roleinparams, $courseid);
        $records = $DB->get_records_sql($usersql, $roleinparams);
        $teacherids = array_keys($records);
        if (empty($teacherids)) {
            return [];
        }

        $coursecontact = reset($records);

        // Get group based contacts.
        $groups = array_keys(groups_get_all_groups($courseid));

        if (!empty($groups)) {
            $sql = "SELECT gm.*
            FROM mdl_groups_members  gm
            WHERE gm.groupid IN (".implode(',', $groups).")
            ORDER BY gm.timeadded ASC";

            $groupmembers = $DB->get_records_sql($sql, []);

            // List of not assigned any group users.
            $users = array_column($groupmembers, 'userid');

            foreach ($teacherids as $id) {
                if (!in_array($id, $users)) {
                    $coursecontact = $records[$id];
                    continue;
                }
            }
            $groups = [];
            foreach ($groupmembers as $key => $mem) {
                if (in_array($mem->userid, $teacherids)) {
                    if (empty($groups[$mem->groupid]['sender'])) {
                        $groups[$mem->groupid]['sender'] = $records[$mem->userid];
                    }
                } else {
                    $groups[$mem->groupid]['students'][] = $mem->userid;
                }
            }
        }

        return (object) ['coursecontact' => $coursecontact, 'groupcontact' => $groups];
    }
}
