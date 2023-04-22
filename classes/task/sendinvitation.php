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
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\task;

/**
 * Defined the invitation send method and filter methods.
 */
class sendinvitation extends \core\task\adhoc_task {

    /**
     * Adhoc task execution.
     * For each pulse instance, Enrolled users data fetched and filtered by their acitivty availability status.
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/pulse/lib.php');
        $instance = $this->get_custom_data();
        // Check pulse enabled.

        if (!$DB->record_exists('course_modules', ['id' => $instance->cm->id])) {
            return true;
        }
        // Filter users from course pariticipants by completion.
        $listofusers = mod_pulse_get_course_students((array) $instance->students, $instance);
        // Extend the pulse pro version to send notifications on selected recipients.
        if (!empty($listofusers)) {
            $this->send_pulse($listofusers, $instance->pulse, $instance->course, $instance->context);
        } else {
            pulse_mtrace('There is not users to send pulse');
        }
    }

    /**
     * Send pulse data.
     *
     * @param  mixed $users Users data record.
     * @param  mixed $pulse Pulse instance record.
     * @param  mixed $course Course data record.
     * @param  mixed $context Module Context data record.
     * @return void
     */
    public function send_pulse($users, $pulse, $course, $context) {
        global $DB;
        if (!empty($pulse) && !empty($users)) {
            // Get course module using instanceid.
            $senderdata = self::get_sender($course->id, $context->id);
            if ($pulse->pulse == true) {
                $notifiedusers = [];
                // Collect list of available enrolled students in course module.
                pulse_mtrace('Sending pulse to enrolled users in course '.$course->fullname."\n");
                foreach ($users as $key => $student) {
                    $sender = self::find_user_sender($senderdata, $student->id);
                    $userto = $student; // Send to.
                    $subject = $pulse->pulse_subject ?: get_string('pulse_subject', 'pulse'); // Message subject.
                    // Use intro content as message text, if different pulse disabled.
                    $template = $pulse->intro;
                    $filearea = 'intro';
                    if ($pulse->diff_pulse) {
                        // Email template content.
                        $template = $pulse->pulse_content;
                        $filearea = 'pulse_content';
                    }
                    // Replace the email text placeholders with data.
                    list($subject, $messagehtml) = mod_pulse_update_emailvars($template, $subject, $course,
                        $student, $pulse, $sender);
                    // Rewrite the plugin file placeholders in the email text.
                    $messagehtml = file_rewrite_pluginfile_urls($messagehtml, 'pluginfile.php',
                        $context->id, 'mod_pulse', $filearea, 0);
                    // Format filter supports. filter the enabled filters.
                    $messagehtml = format_text($messagehtml, FORMAT_HTML);

                    $messageplain = html_to_text($messagehtml); // Plain text.
                    // Send message to user.
                    pulse_mtrace("Sending pulse to the user ". fullname($userto) ."\n" );

                    try {
                        $transaction = $DB->start_delegated_transaction();
                        if (mod_pulse_update_notified_user($userto->id, $pulse)) {
                            $messagesend = mod_pulse_messagetouser($userto, $subject, $messageplain, $messagehtml, $pulse, $sender);
                            if ($messagesend) {
                                $notifiedusers[] = $userto->id;
                            } else {
                                throw new \moodle_exception('mailnotsend', 'pulse');
                            }
                        } else {
                            throw new \moodle_exception('invitationDB', 'pulse');
                        }
                        $transaction->allow_commit();
                    } catch (\Exception $e) {
                        $transaction->rollback($e);
                    }
                }
            }
        }
    }

    /**
     * Find the correct sender user from the course and group contacts.
     *
     * @param  mixed $senderdata Listof course and group contact users
     * @param  mixed $userid // Studnet user id
     * @return object Sender user obejct
     */
    public static function find_user_sender($senderdata, $userid) {

        if (!empty($senderdata->groupcontact)) {
            $groups = $senderdata->groupcontact;
            foreach ($groups as $groupid => $group) {
                $group = (object) $group;
                // Check student assigned in any group.
                if (isset($group->students) && in_array($userid, array_values($group->students))) {
                    if (!empty($group->sender)) { // Group has any teacher role to send notification.
                        return $group->sender;
                    }
                }
            }
        }

        return (!empty($senderdata->coursecontact) ? $senderdata->coursecontact : \core_user::get_support_user());
    }


    /**
     * Get list of available senders users from group and course seperately.
     *
     * @param  mixed $courseid
     * @return void
     */
    public static function get_sender($courseid) {
        global $DB;
        $rolesql = "SELECT  rc.roleid FROM {role_capabilities} rc
        JOIN {capabilities} cap ON rc.capability = cap.name
        JOIN {context} ctx on rc.contextid = ctx.id
        WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:sender']);
        $roles = array_column($roles, 'roleid');

        list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);
        $contextid = \context_course::instance($courseid)->id;
        $usersql = "SELECT eu1_u.*, ra.*
        FROM {user} eu1_u
        JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = eu1_u.id
        JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid AND ej1_e.courseid = ?)
        JOIN (
            SELECT userid, Max(rle.shortname) as roleshortname, MAX(roleid) as roleid
                FROM {role_assignments}
                JOIN {role} rle ON rle.id = roleid
                WHERE contextid = ? AND roleid $roleinsql GROUP BY userid
            ) ra ON ra.userid = eu1_u.id
        WHERE 1 = 1 AND ej1_ue.status = 0
        AND ( ej1_ue.timestart = 0 OR ej1_ue.timestart <= ? )
        AND ( ej1_ue.timeend = 0 OR ej1_ue.timeend > ? )
        AND eu1_u.deleted = 0 AND eu1_u.suspended = 0 ORDER BY ej1_ue.timestart, ej1_ue.timecreated";

        array_unshift($roleinparams, $contextid);
        array_unshift($roleinparams, $courseid);
        $roleinparams[] = time();
        $roleinparams[] = time();
        $records = $DB->get_records_sql($usersql, $roleinparams);
        $teacherids = array_keys($records);
        // If no teachers enroled in course then use the support user.
        if (empty($teacherids)) {
            return [];
        }

        $coursecontact = current($records); // Get first course contact user.

        // Get group based contacts.
        $groups = array_keys(groups_get_all_groups($courseid));

        if (!empty($groups)) {
            $sql = "SELECT gm.*
            FROM {groups_members}  gm
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
