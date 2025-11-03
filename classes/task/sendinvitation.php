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

use moodle_url;
use mod_pulse\helper as pulsehelper;

/**
 * Defined the invitation send method and filter methods.
 */
class sendinvitation extends \core\task\adhoc_task {
    /**
     * List of notified users, Used to update the notified users status after reminders are send to all users.
     *
     * @var array
     */
    public $notifiedusers = [];

    /**
     * Current pulse instance record data.
     *
     * @var stdclass
     */
    public $instance;

    /**
     * Adhoc task execution.
     * For each pulse instance, Enrolled users data fetched and filtered by their acitivty availability status.
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/pulse/lib.php');

        $customdata = $this->get_custom_data();

        // Just return if the task is for previous pulse version.
        // Recreate the task for new pulse version.
        if (empty($customdata->type)) {
            return true;
        }

        $pulseid = $customdata->pulseid;
        $notification = new \mod_pulse\addon\notification();
        $instances = $notification->get_instances('pl.id = :pulseid', ['pulseid' => $pulseid]);

        $instance = $instances[$pulseid];
        $this->instance = $instance;

        if (empty($instance)) {
            return true;
        }

        if (!$DB->record_exists('pulse', ['id' => $this->instance->pulse->id])) {
            return true;
        }

        $this->send_pulse($notification);

        return true;
    }

    /**
     * Send pulse data.
     *
     * @param \mod_pulse\addon\notification $notification Notification instance.
     * @return void
     */
    public function send_pulse(\mod_pulse\addon\notification $notification) {
        global $DB, $USER, $PAGE;

        $instance = $this->instance;

        // Store current user for update the user after filter.
        $currentuser = $USER;

        // Store the current page course and cm for support the filtercodes.
        $currentcourse = $PAGE->course;
        $currentcm = $PAGE->cm;
        $currentcontext = $PAGE->context;

        // Set the current pulse course as page course. Support for filter shortcodes.
        // Filtercodes plugin used $PAGE->course proprety for coursestartdate, course enddata and other course related shortcodes.
        // Tried to use $PAGE->set_course(), But the theme already completed the setup, so we can't use that moodle method.
        // For this reason, here updated the protected _course property using reflection.
        // Only if filtercodes fitler plugin installed and enabled.
        if (\mod_pulse\helper::change_pagevalue()) {
            $coursereflection = new \ReflectionProperty(get_class($PAGE), '_course');
            $coursereflection->setAccessible(true);
            $coursereflection->setValue($PAGE, $instance->course);

            // Setup the course module data to support filtercodes.
            $pulsecm = get_coursemodule_from_instance('pulse', $instance->pulse->id);
            $cmreflection = new \ReflectionProperty(get_class($PAGE), '_cm');
            $cmreflection->setAccessible(true);
            $cmreflection->setValue($PAGE, $pulsecm);

            $context = \context_module::instance($pulsecm->id);
            $contextreflection = new \ReflectionProperty(get_class($PAGE), '_context');
            $contextreflection->setAccessible(true);
            $contextreflection->setValue($PAGE, $context);
        }

        // Pulse moodle trace.
        $instance->users = $notification->get_invitation_student_users($instance);

        if (!empty($instance->users)) {
            foreach ($instance->users as $user) {
                if (isset($user->id)) {
                    $condition = ['userid' => $user->id, 'pulseid' => $instance->pulse->id, 'status' => 1];
                    if (!$DB->record_exists('pulse_users', $condition)) {
                        pulse_mtrace(
                            'Prepare invitation eventdata for the user - ' . $user->id . ' for the pulse ' .
                            $instance->pulse->name
                        );
                        $this->send_notification($user, $instance);
                    }
                }
            }
        }

        // Only for filter codes.
        if (\mod_pulse\helper::change_pagevalue()) {
            // Return to current USER.
            \core\session\manager::set_user($currentuser);

            // SEtup the page course and cm to current values.
            $coursereflection->setValue($PAGE, $currentcourse);

            // Setup the course module data to support filtercodes.
            $cmreflection->setValue($PAGE, $currentcm);

            // Setup the module context to support filtercodes.
            $contextreflection->setValue($PAGE, $currentcontext);
        }

        return true;
    }

    /**
     * Send reminder notification to available users. Users are filter by selected fixed date or relative date.
     * Once the reminders and invitations are send then it will updates the notified users list in availability table.
     *
     * @param  \stdclass $user User record data
     * @param  stdclass $instance Pulse instance record.
     * @return void
     */
    protected function send_notification($user, $instance) {
        global $DB, $CFG, $USER, $PAGE;

        require_once($CFG->dirroot . '/mod/pulse/lib.php');
        // Store current user for update the user after filter.
        $currentuser = $USER;

        $course = (object) $instance->course;
        $context = (object) $instance->context;
        $pulse = (object) $instance->pulse;
        $filearea = 'invitation_content';

        if (!empty($pulse) && !empty($user)) {
            // Use intro content as message text, if different pulse disabled.
            $subject = ($instance->pulse->diff_pulse) ? $instance->pulse->pulse_subject : $pulse->name;
            $template = ($instance->pulse->diff_pulse) ? $instance->pulse->pulse_content : $pulse->intro;
            $filearea = ($instance->pulse->diff_pulse) ? 'pulse_content' : 'intro';

            // Find the sender for that user.
            $sender = self::find_user_sender($instance->sender, $user->id);

            // Replace the email text placeholders with data.
            if (!empty($pulse->id)) {
                $pulse->url = new moodle_url("/mod/pulse/view.php", ['id' => $pulse->id]);
                $pulse->type = 'pulse';
            }
            [$subject, $messagehtml] = pulsehelper::update_emailvars($template, $subject, $course, $user, $pulse, $sender);

            // Rewrite the plugin file placeholders in the email text.
            $messagehtml = file_rewrite_pluginfile_urls($messagehtml, 'pluginfile.php', $context->id, 'mod_pulse', $filearea, 0);

            // Set current student as user, filtercodes plugin uses current User data.
            \core\session\manager::set_user($user);
            if (isset($user->lang)) {
                $oldforcelang = force_current_language($user->lang); // Force the session lang to user lang.
            }
            // Format filter supports. filter the enabled filters.
            $subject = format_text($subject, FORMAT_HTML);
            $messagehtml = format_text($messagehtml, FORMAT_HTML);
            $messageplain = html_to_text($messagehtml); // Plain text.
            // After format the message and subject return back to previous lang.
            if (isset($oldforcelang)) {
                force_current_language($oldforcelang);
            }

            // Send message to user.
            pulse_mtrace("Sending pulse to the user " . fullname($user) . "\n");

            try {
                $transaction = $DB->start_delegated_transaction();
                if (\mod_pulse\helper::update_notified_user($user->id, $pulse)) {
                    $messagesend = \mod_pulse\helper::messagetouser(
                        $user,
                        $subject,
                        $messageplain,
                        $messagehtml,
                        $pulse,
                        $sender
                    );
                    if ($messagesend) {
                        $notifiedusers[] = $user->id;
                    } else {
                        throw new \moodle_exception('mailnotsend', 'pulse');
                    }
                } else {
                    throw new \moodle_exception('invitationDB', 'pulse');
                }
                $transaction->allow_commit();
            } catch (\Exception $e) {
                // Return to current USER.
                \core\session\manager::set_user($currentuser);
                $transaction->rollback($e);
            }
        }

        return true;
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
     * @return object
     */
    public static function get_sender($courseid) {
        global $DB;
        $rolesql = "SELECT rc.id, rc.roleid FROM {role_capabilities} rc
        JOIN {capabilities} cap ON rc.capability = cap.name
        JOIN {context} ctx on rc.contextid = ctx.id
        WHERE rc.capability = :capability ";
        $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:sender']);
        $roles = array_column($roles, 'roleid');

        [$roleinsql, $roleinparams] = $DB->get_in_or_equal($roles);
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
            WHERE gm.groupid IN (" . implode(',', $groups) . ")
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
