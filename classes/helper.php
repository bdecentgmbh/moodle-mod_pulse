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
 * Mod pulse helper - Commonly used methods in pulse.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

use html_writer;
use core_user;
use pulse_email_vars;
use context_module;
use moodle_url;
use cm_info;
use core_course_category;
use stdclass;
use context_course;

/**
 * Commonly used method in pulse.
 */
class helper {
    /**
     * Replace email template placeholders with dynamic datas.
     *
     * @param mixed $templatetext Email Body content with placeholders
     * @param mixed $subject Mail subject with placeholders.
     * @param mixed $course Course object data.
     * @param mixed $user User data object.
     * @param mixed $mod Pulse module data object.
     * @param mixed $sender Sender user data object. - sender is the first enrolled teacher in the course of module.
     * @param array $conditionvars Condition variables.
     * @param string $type Email type.
     * @return array Updated subject and message body content.
     */
    public static function update_emailvars(
        $templatetext,
        $subject,
        $course,
        $user,
        $mod,
        $sender,
        $conditionvars = [],
        $type = 'notification'
    ) {

        global $DB, $CFG, $USER;

        // Include placholders handler and user profile library.
        require_once($CFG->dirroot . '/mod/pulse/lib/vars.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Load user profile field data.
        $newuser = (object) ['id' => !empty($user->id) ? $user->id : $USER->id];
        profile_load_data($newuser);
        // Make the profile custom field data to separate element of the user object.
        $newuserkeys = array_map(function ($value) {
            return str_replace('profile_field_', '', $value);
        }, array_keys((array) $newuser));

        $user->profilefield = (object) array_combine($newuserkeys, (array) $newuser);
        $user->fullname = fullname($user);

        $course = clone $course;
        // Load course custom profuile fields.
        $course->customfield = \core_course\customfield\course_handler::create()->export_instance_data_object($course->id);

        // Load the course url.
        if (!empty($course->id)) {
            $url = new moodle_url($CFG->wwwroot . '/course/view.php', ['id' => $course->id]);
        }
        if (empty($CFG->allowthemechangeonurl)) {
            $courseurl = $url;
        } else {
            $courseurl = new moodle_url($url);
        }
        $course->courseurl = $courseurl;

        $sender = $sender ? $sender : core_user::get_support_user(); // Support user.
        $amethods = pulse_email_vars::vars('all'); // List of available placeholders.
        // Get formatted name of the category.
        $course->category = is_number($course->category)
            ? core_course_category::get($course->category)->get_formatted_name() : $course->category;

        $vars = new pulse_email_vars($user, $course, $sender, $mod, $conditionvars, $type);

        foreach ($amethods as $varscat => $placeholders) {
            foreach ($placeholders as $funcname) {
                $replacement = "{" . $funcname . "}";
                // Message text placeholder update.
                if (stripos($templatetext, $replacement) !== false) {
                    $val = $vars->$funcname;
                    // Is the var is closure then call the function.
                    if ($val instanceof \Closure) {
                        $val = $val($mod, $user, $course);
                    }

                    if ($val instanceof moodle_url) {
                        // Remove any URL scheme (http or https) from the text if it is followed by a placeholder.
                        if (
                            stripos($templatetext, 'http://' . $replacement) !== false
                            || stripos($templatetext, 'https://' . $replacement) !== false
                        ) {
                            $templatetext = str_ireplace('http://' . $replacement, $replacement, $templatetext);
                            $templatetext = str_ireplace('https://' . $replacement, $replacement, $templatetext);
                        }
                    }

                    if (is_array($val)) {
                        $text = $val['text'] ?? '';
                        $val = format_text($text, $val['format'] ?? FORMAT_HTML, ['trusted' => true, 'noclean' => true]);
                    }

                    // Placeholder found on the text, then replace with data.
                    $templatetext = str_ireplace($replacement, $val, $templatetext);
                }
                // Replace message subject placeholder.
                if (stripos($subject, $replacement) !== false) {
                    $val = $vars->$funcname;
                    // Is the var is closure then call the function.
                    if ($val instanceof \Closure) {
                        $val = $val($mod, $user, $course);
                    }
                    $subject = str_ireplace($replacement, $val, $subject);
                }
            }
        }
        return [$subject, $templatetext];
    }

    /**
     * Find the course module is visible to current user.
     *
     * @param  mixed $cmid
     * @param  mixed $userid
     * @param  mixed $courseid
     * @return void
     */
    public static function pulse_is_uservisible($cmid, $userid, $courseid) {
        // Filter available users.
        if (!empty($cmid)) {
            $modinfo = get_fast_modinfo($courseid, $userid);
            $cm = $modinfo->get_cm($cmid);
            return $cm->uservisible;
        }
    }

    /**
     * Check the current users has role to approve the completion for students in current pulse module.
     *
     * @param  mixed $completionapprovalroles Completion approval roles select in the pulse instance.
     * @param  mixed $cmid Course module id.
     * @param  mixed $usercontext check user context(false it only check and return the coursecontetxt roles)
     * @param  mixed $userid
     * @return void
     */
    public static function pulse_has_approvalrole($completionapprovalroles, $cmid, $usercontext = true, $userid = null) {
        global $USER, $DB;
        if ($userid == null) {
            $userid = $USER->id;
        }
        $modulecontext = context_module::instance($cmid);
        $approvalroles = json_decode($completionapprovalroles);
        $roles = get_user_roles($modulecontext, $userid);
        $hasrole = false;
        foreach ($roles as $key => $role) {
            if (in_array($role->roleid, $approvalroles)) {
                $hasrole = true;
            }
        }
        // Check if user has role in course context level role to approve.
        if (!$usercontext) {
            return $hasrole;
        }
        // Test user has user context.
        $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
                FROM {role_assignments} ra
                JOIN {context} c ON ra.contextid = c.id
                JOIN {role} r ON ra.roleid = r.id
                WHERE ra.userid = ? and c.contextlevel = ?
                ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
        $roleassignments = $DB->get_records_sql($sql, [$userid, CONTEXT_USER]);
        if ($roleassignments) {
            foreach ($roleassignments as $role) {
                if (in_array($role->roleid, $approvalroles)) {
                    return true;
                }
            }
        }
        return $hasrole;
    }

    /**
     * Check the pulse instance contains user context roles in completion approval roles
     *
     * @param  mixed $completionapprovalroles
     * @param  mixed $cmid
     * @return void
     */
    public static function pulse_isusercontext($completionapprovalroles, $cmid) {
        global $DB, $USER;

        // Test user has user context.
        $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
                FROM {role_assignments} ra
                JOIN {context} c ON ra.contextid = c.id
                JOIN {role} r ON ra.roleid = r.id
                WHERE ra.userid = ? and c.contextlevel = ?
                ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
        $roleassignments = $DB->get_records_sql($sql, [$USER->id, CONTEXT_USER]);
        if ($roleassignments) {
            return true;
        }
        return false;
    }

    /**
     * Get mentees assigned students list.
     *
     * @return bool|mixed List of users assigned as child users.
     */
    public static function pulse_user_getmentessuser() {
        global $DB, $USER;

        if (
            $usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid
                                                FROM {role_assignments} ra, {context} c, {user} u
                                                WHERE ra.userid = ?
                                                        AND ra.contextid = c.id
                                                        AND c.instanceid = u.id
                                                        AND u.deleted = 0 AND u.suspended = 0
                                                        AND c.contextlevel = " . CONTEXT_USER, [$USER->id])
        ) {
            $users = [];
            foreach ($usercontexts as $usercontext) {
                $users[] = $usercontext->instanceid;
            }
            return $users;
        }
        return false;
    }

    /**
     * Check and generate user approved information for module.
     *
     * @param  mixed $pulseid
     * @param  mixed $userid
     * @return bool|string Returns approved user data as html.
     */
    public static function pulse_user_approved($pulseid, $userid) {
        global $DB;
        $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulseid]);
        if (!empty($completion) && $completion->approvalstatus) {
            $date = userdate($completion->approvaltime, get_string('strftimedaydate', 'core_langconfig'));
            $approvaltime = isset($completion->approvalstatus) ? $date : 0;
            $params['date'] = ($approvaltime) ? $approvaltime : '-';
            $approvedby = isset($completion->approveduser) ? \core_user::get_user($completion->approveduser) : '';
            $params['user'] = ($approvedby) ? fullname($approvedby) : '-';

            $approvalstr = get_string('approvedon', 'pulse', $params);
            return html_writer::tag('div', $approvalstr, ['class' => 'badge badge-info']);
        }
        return false;
    }

    /**
     * Check the logged in users is student for pulse.
     *
     * @param  mixed $cmid
     * @return bool
     */
    public static function pulse_user_isstudent($cmid) {
        global $USER;
        $modulecontext = context_module::instance($cmid);
        $roles = get_user_roles($modulecontext, $USER->id);
        $hasrole = false;
        $studentroles = array_keys(get_archetype_roles('student'));
        foreach ($roles as $key => $role) {
            if (in_array($role->roleid, $studentroles)) {
                $hasrole = true;
                break;
            }
        }
        return $hasrole;
    }

    /**
     * Find the user already completed the module by self compeltion.
     *
     * @param  mixed $pulseid
     * @param  mixed $userid
     * @return bool true|false
     */
    public static function pulse_already_selfcomplete($pulseid, $userid) {
        global $DB;
        $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulseid]);
        if (!empty($completion) && $completion->selfcompletion) {
            if (isset($completion->selfcompletion) && $completion->selfcompletion != '') {
                $result = userdate($completion->selfcompletiontime, get_string('strftimedaydate', 'core_langconfig'));
            }
        }
        return isset($result) ? $result : false;
    }

    /**
     * Render the pulse content with selected box container with box icon.
     *
     * @param string $content Pulse content.
     * @param string $boxicon Icon.
     * @param string $boxtype Box type name (primary, secondory, danger, warning and others).
     * @return string Pulse content with box container.
     */
    public static function pulse_render_content(string $content, string $boxicon, string $boxtype = 'primary'): string {
        global $OUTPUT;
        $html = html_writer::start_tag('div', ['class' => 'pulse-box']);
        $html .= html_writer::start_tag('div', ['class' => 'alert alert-' . $boxtype]);
        if (!empty($boxicon)) {
            $icon = explode(':', $boxicon);
            $icon1 = isset($icon[1]) ? $icon[1] : 'core';
            $icon0 = isset($icon[0]) ? $icon[0] : '';
            $boxicon = $OUTPUT->pix_icon($icon1, $icon0);
            $html .= html_writer::tag('div', $boxicon, ['class' => 'alert alert-icon pulse-box-icon']);
        }
        $html .= html_writer::tag('div', $content, ['class' => 'pulse-box-content']);
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');
        return $html;
    }

    /**
     * Add the completion and reaction buttons with pulse content on view page.
     *
     * @param cm_info $cm Current Course module.
     * @param stdclass $pulse Pulse record object.
     * @return string $html Completion and reaction buttons html content.
     */
    public static function cm_completionbuttons(cm_info $cm, stdclass $pulse): string {
        global $USER, $DB;
        $html = '';
        $moduleid = $cm->id;
        $extend = true;
        // Approval button generation for selected roles.
        if ($pulse->completionapproval == 1) {
            $roles = $pulse->completionapprovalroles;
            if (self::pulse_has_approvalrole($roles, $cm->id)) {
                $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $cm->id]);
                $html .= html_writer::tag(
                    'div',
                    html_writer::link(
                        $approvelink,
                        get_string('approveuserbtn', 'pulse'),
                        ['class' => 'btn btn-primary pulse-approve-users']
                    ),
                    ['class' => 'approve-user-wrapper']
                );
            } else if (self::pulse_user_isstudent($cm->id)) {
                if (
                    !class_exists('core_completion\activity_custom_completion')
                    && $message = self::pulse_user_approved($cm->instance, $USER->id)
                ) {
                    $html .= $message . '<br>';
                }
            }
        }

        // Generate self mark completion buttons for students.
        if (self::pulse_is_uservisible($moduleid, $USER->id, $cm->course)) {
            if (
                $pulse->completionself == 1 && self::pulse_user_isstudent($moduleid)
                && !self::pulse_isusercontext($pulse->completionapprovalroles, $moduleid)
            ) {
                // Add self mark completed informations.
                if (
                    !class_exists('core_completion\activity_custom_completion')
                    && $date = self::pulse_already_selfcomplete($cm->instance, $USER->id)
                ) {
                    $selfmarked = self::get_complete_state_button_text($pulse->completionbtntext, $date) . '<br>';
                    $html .= html_writer::tag(
                        'div',
                        $selfmarked,
                        ['class' => 'pulse-self-marked badge badge-success']
                    );
                } else if (!self::pulse_already_selfcomplete($cm->instance, $USER->id)) {
                    $additionalclass = $pulse->completionbtnconfirmation ? 'confirmation-' . $moduleid : '';

                    $buttontext = self::get_not_complete_state_button_text($pulse->completionbtntext);
                    $selfcomplete = !$pulse->completionbtnconfirmation ?
                        new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid, 'action' => 'selfcomplete']) :
                        'javascript:void(0);';
                    $selfmarklink = html_writer::link(
                        $selfcomplete,
                        $buttontext,
                        [   'class' => 'btn btn-primary pulse-user-manualcompletion-btn ' . $additionalclass]
                    );
                    $html .= html_writer::tag('div', $selfmarklink, ['class' => 'pulse-user-manualcompletion']);
                }
            }
        } else {
            $extend = false;
        }
        // Extend the pro features if the logged in users has able to view the module.
        if ($extend) {
            $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
            $instance = new \stdclass();
            $instance->pulse = $pulse;
            $instance->pulse->id = $cm->instance;
            $instance->user = $USER;
            $instance->pulse->options = (object) options::init($cm->instance)->get_options();

            $html .= \mod_pulse\extendpro::pulse_extend_cm_infocontent($cm->instance, $instance);
        }
        return $html;
    }

    /**
     * Check user has access to the module
     *
     * @param \cm_info $cm Course Module instance
     * @param int $userid User record id
     * @param \core_availability\info_section $sectioninfo Section availability info
     * @param  \course_modinfo $modinfo course Module info.
     * @param \core_availability\info_module $info Module availability info.
     * @return void
     */
    public static function pulse_mod_uservisible($cm, $userid, $sectioninfo, $modinfo, $info) {
        $context = $cm->context;
        if ((!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', $context, $userid))) {
            return false;
        }

        $str = '';
        if (
            $sectioninfo->is_available($str, false, $userid, $modinfo)
            && $info->is_available($str, false, $userid, $modinfo)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Seperate the record data into context and course and cm.
     * In function mod_pulse_completion_crontask, data fetched using JOIN queries,
     * Here the joined datas are seperated.
     *
     * @param  mixed $keys List of fields available for sql data return.
     * @param  mixed $record Pulse Instance data from sql data
     * @return array Returns course, context, cm data.
     */
    public static function process_recorddata($keys, $record) {
        // Context.
        $ctxpos = array_search('contextid', $keys);
        $ctxendpos = array_search('locked', $keys);
        $context = array_slice($record, $ctxpos, ($ctxendpos - $ctxpos) + 1);
        $context['id'] = $context['contextid'];
        unset($context['contextid']);
        // Course module.
        $cmpos = array_search('cmid', $keys);
        $cmendpos = array_search('deletioninprogress', $keys);
        $cm = array_slice($record, $cmpos, ($cmendpos - $cmpos) + 1);
        $cm['id'] = $cm['cmid'];
        unset($cm['cmid']);
        // Course records.
        $coursepos = array_search('courseid', $keys);
        $course = array_slice($record, $coursepos);
        $course['id'] = $course['courseid'];

        return [0 => $course, 1 => $context, 2 => $cm];
    }

    /**
     * Pulse form editor element options.
     *
     * @param mixed $context Context
     * @return array
     */
    public static function get_editor_options($context = null) {
        global $PAGE, $CFG;

        require_once($CFG->libdir . '/formslib.php');
        return [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $context ?: $PAGE->context,
        ];
    }

    /**
     * Update the user id in the db notified users list.
     *
     * @param  int $userid User ID.
     * @param  mixed $pulse Pulse instance object.
     * @return bool
     */
    public static function update_notified_user($userid, $pulse) {
        global $DB;

        if (!empty($userid)) {
            $record = new stdclass();
            $record->userid = $userid;
            $record->pulseid = $pulse->id;
            $record->status = 1;
            $record->timecreated = time();

            return $DB->insert_record('pulse_users', $record);
        }
        return false;
    }

    /**
     * Filter the users who has access to view the instance and not notified before.
     *
     * @param  mixed $students List of student users enrolled in course.
     * @param  mixed $instance Pulse instance
     * @return array List of students who has access.
     */
    public static function get_course_students($students, $instance) {
        global $DB, $CFG;
        // Filter available users.
        pulse_mtrace('Filter users based on their availablity..');
        foreach ($students as $student) {
            $modinfo = new \course_modinfo((object) $instance->course, $student->id);
            $cm = $modinfo->get_cm($instance->cm->id);
            if (!$cm->uservisible || self::pulseis_notified($student->id, $instance->pulse->id)) {
                unset($students[$student->id]);
            }
        }
        return $students;
    }

    /**
     * Confirm the pulse instance send the invitation to the shared user.
     *
     * @param  int $studentid User id
     * @param  int $pulseid pulse instance id
     * @return bool true if user not notified before.
     */
    public static function pulseis_notified($studentid, $pulseid) {
        global $DB;
        if ($DB->record_exists('pulse_users', ['pulseid' => $pulseid, 'userid' => $studentid, 'status' => 1])) {
            return true;
        }
        return false;
    }

    /**
     * Send pulse notifications to the users.
     *
     * @param  mixed $userto
     * @param  mixed $subject
     * @param  mixed $messageplain
     * @param  mixed $messagehtml
     * @param  mixed $pulse
     * @param  mixed $sender
     * @return void
     */
    public static function messagetouser($userto, $subject, $messageplain, $messagehtml, $pulse, $sender = true) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/pulse/lib.php');

        $eventdata = new \core\message\message();
        $eventdata->name = 'mod_pulse';
        $eventdata->component = 'mod_pulse';
        $eventdata->courseid = $pulse->course;
        $eventdata->modulename = 'pulse';
        $eventdata->userfrom = $sender ? $sender : core_user::get_support_user();
        $eventdata->userto = $userto;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $messageplain;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $messagehtml;
        $eventdata->smallmessage = $subject;
        if (message_send($eventdata)) {
            pulse_mtrace("Pulse send to the user.");
            return true;
        } else {
            pulse_mtrace("Failed - Pulse send to the user. -" . fullname($userto), true);
            return false;
        }
    }

    /**
     * Get list of instance added in the course.
     *
     * @param  int $courseid Course id.
     * @return array list of pulse instance added in the course.
     */
    public static function course_instancelist($courseid) {
        global $DB;
        $sql = "SELECT cm.*, pl.name FROM {course_modules} cm
                JOIN {pulse} pl ON pl.id = cm.instance
                WHERE cm.course=:courseid AND cm.module IN (SELECT id FROM {modules} WHERE name=:pulse)";
        return $DB->get_records_sql($sql, ['courseid' => $courseid, 'pulse' => 'pulse']);
    }

    /**
     * Confirm the filtercodes plugin is installed, installed then need to update the page value otherwise not need to do.
     * Not update the page values, will helps to make the DB fetchs less.
     *
     * @return void
     */
    public static function change_pagevalue() {
        global $CFG;
        static $result;

        if (!isset($result)) {
            require_once($CFG->dirroot . '/lib/filterlib.php');
            $list = filter_get_all_installed();

            if (array_key_exists('filtercodes', $list)) {
                $result = filter_is_enabled('filter/filtercodes');
            }
        }
        return $result;
    }

    /**
     * Get the not completed state button text from the module form.
     *
     * @param int $value Button text value
     */
    public static function get_not_complete_state_button_text($value) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/pulse/lib.php');

        switch ($value) {
            case BUTTON_TEXT_ACKNOWLEDGE:
                $buttontext = get_string('markcompletebtnstring_custom1', 'pulse');
                break;
            case BUTTON_TEXT_CONFIRM:
                $buttontext = get_string('markcompletebtnstring_custom2', 'pulse');
                break;
            case BUTTON_TEXT_CHOOSE:
                $buttontext = get_string('markcompletebtnstring_custom3', 'pulse');
                break;
            case BUTTON_TEXT_APPROVE:
                $buttontext = get_string('markcompletebtnstring_custom4', 'pulse');
                break;
            default:
                $buttontext = get_string('markcompletebtnstring_default', 'pulse');
                break;
        }
        return $buttontext;
    }

    /**
     * Get the completed state button text from the module form.
     *
     * @param int $value Button text value.
     * @param int $date Completion date.
     */
    public static function get_complete_state_button_text($value, $date) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/pulse/lib.php');
        switch ($value) {
            case BUTTON_TEXT_ACKNOWLEDGE:
                $buttontext = get_string('markedcompletebtnstring_custom1', 'pulse', ['date' => $date]);
                break;
            case BUTTON_TEXT_CONFIRM:
                $buttontext = get_string('markedcompletebtnstring_custom2', 'pulse', ['date' => $date]);
                break;
            case BUTTON_TEXT_CHOOSE:
                $buttontext = get_string('markedcompletebtnstring_custom3', 'pulse', ['date' => $date]);
                break;
            case BUTTON_TEXT_APPROVE:
                $buttontext = get_string('markedcompletebtnstring_custom4', 'pulse', ['date' => $date]);
                break;
            default:
                $buttontext = get_string('markedcompletebtnstring_default', 'pulse', ['date' => $date]);
                break;
        }
        return $buttontext;
    }

    /**
     * Postupdate the editor files.
     *
     * @param object $pulse
     * @param mixed $context
     */
    public static function postupdate_editor_files($pulse, $context) {
        global $DB;
        $editors = ['completionbtn_content'];
        $upd = new stdClass();
        $upd->id = $pulse->id;
        foreach ($editors as $editor) {
            $editorformat = $editor . "format";
            $pulse = file_postupdate_standard_editor(
                $pulse,
                $editor,
                self::get_editor_options($context),
                $context,
                'mod_pulse',
                $editor,
                0
            );
            $upd->{$editor}       = $pulse->{$editor};
            $upd->{$editorformat} = $pulse->{$editorformat};
        }
        $DB->update_record('pulse', $upd);
    }

    /**
     * Filter the record data by keys with a specific prefix.
     *
     * @param array|object $record The record data to be filtered.
     * @param string $prefix The prefix to filter keys by.
     *
     * @return array The filtered data with the prefix removed from keys.
     */
    public static function filter_record_byprefix($record, $prefix) {

        // Filter the data based on the shortname.
        $filtered = array_filter((array) $record, function ($key) use ($prefix) {
            return strpos($key, $prefix . '_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        // Remove the prefix from the keys.
        $removedprefix = array_map(function ($key) use ($prefix) {
            return str_replace($prefix . "_", '', $key);
        }, array_keys($filtered));

        // Combine the filtered values with prefix removed keys.
        $final = array_combine(array_values($removedprefix), array_values($filtered));

        return $final;
    }

     /**
      * Find the timetable tool installed.
      *
      * @return bool result of the timetable plugin availability.
      */
    public static function timetable_installed() {
        global $CFG;
        static $result;

        if ($result == null) {
            if (array_key_exists('timetable', \core_component::get_plugin_list('tool'))) {
                require_once($CFG->dirroot . '/admin/tool/timetable/classes/time_management.php');
                $result = true;
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Get tool timetable details user current course progress and due modules course.
     *
     * @param string $var
     * @param \stdClass $course
     * @param int $userid
     * @param \context $context
     * @param \stdClass $mod
     * @return string
     */
    public static function timetable_details(string $var, \stdClass $course, int $userid, $context = null, $mod = null): string {
        global $CFG, $DB, $PAGE;

        require_once($CFG->dirroot . '/enrol/locallib.php');

        // Other than eventdates all are need tools timetable installed.
        if (!self::timetable_installed()) {
            return '';
        }

        $context = $context ?? context_course::instance($course->id);

        // Find the course due date. only if the tool timetable installed.
        if ($var == 'coursedue' && ($timecourse = $DB->get_record('tool_timetable_course', ['course' => $course->id]))) {
            if ($timecourse) {
                $timemanagement = new \tool_timetable\time_management($timecourse->course);
                // Get user enrolment info in course.
                $usercourseenrollinfo = $timemanagement->get_course_user_enrollment($userid);
                $startdate = $usercourseenrollinfo[0]['timestart'] ?? 0;
                $enddate = $usercourseenrollinfo[0]['timeend'] ?? 0;
                $coursduedate = $timemanagement->calculate_course_duedate($startdate, $enddate, $userid);
                return $coursduedate ? userdate($coursduedate) : '';
            }
            return '';
        }

        return '';
    }
}
