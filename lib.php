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
 * Pulse instance libarary file. contains pro feature extended methods
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

define( 'MAX_PULSE_NAME_LENGTH', 50);

global $PAGE;

require_once($CFG->libdir."/completionlib.php");
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/mod/pulse/locallib.php');

/**
 * Add pulse instance.
 *
 * @param  mixed $pulse
 * @return void
 */
function pulse_add_instance($pulse) {
    global $DB;

    $context = context_module::instance($pulse->coursemodule);

    $pulse->timemodified = time();

    if (isset($pulse->pulse_content_editor)) {
        $pulse->pulse_content = file_save_draft_area_files($pulse->pulse_content_editor['itemid'],
                                                    $context->id, 'mod_pulse', 'pulse_content', 0,
                                                    array('subdirs' => true), $pulse->pulse_content_editor['text']);
        $pulse->pulse_contentformat = $pulse->pulse_content_editor['format'];
        unset($pulse->pulse_content_editor);
    }
    // Insert the instance in DB.
    $pulseid = $DB->insert_record('pulse', $pulse);
    // Extend the pro features.
    pulse_extend_add_instance($pulseid, $pulse);

    // Retrun new instance id.
    return $pulseid;
}

/**
 * Update pulse instance.
 *
 * @param  mixed $pulse formdata in object.
 * @return bool Update record instance result.
 */
function pulse_update_instance($pulse) {
    global $DB;

    $context = context_module::instance($pulse->coursemodule);

    $pulse->id = $pulse->instance;
    $pulse->timemodified = time();
    if (isset($pulse->pulse_content_editor)) {
        // Save pulse content areafiles.
        $pulse->pulse_content = file_save_draft_area_files($pulse->pulse_content_editor['itemid'],
                                                    $context->id, 'mod_pulse', 'pulse_content', 0,
                                                    array('subdirs' => true), $pulse->pulse_content_editor['text']);
        $pulse->pulse_contentformat = $pulse->pulse_content_editor['format'];
        unset($pulse->pulse_content_editor);
    }

    if (!isset($pulse->boxicon) && isset($pulse->displaymode)) {
        $pulse->boxicon = '';
    }

    // If module resend triggred then set the notified status to null for instance.
    if (isset($pulse->resend_pulse)) {
        $DB->set_field('pulse_users', 'status', 0, ['pulseid' => $pulse->id]);
        // Reschedule the notification if resend notification enabled.
        $message = get_string('resendnotificationdesc', 'mod_pulse');
        \core\notification::add($message, 'info');
    }
    // Update instance data.
    $updates = $DB->update_record('pulse', $pulse);
    // Extend the updated module instance pro features.
    pulse_extend_update_instance($pulse, $context);

    return $updates;
}


/**
 * Delete Pulse instnace
 *
 * @param  mixed $pulseid
 * @return bool
 */
function pulse_delete_instance($pulseid) {
    global $DB;
    if ($DB->record_exists('pulse', ['id' => $pulseid])) {
        $cm = get_coursemodule_from_instance('pulse', $pulseid);

        if ($DB->delete_records('pulse', ['id' => $pulseid])) {
            pulse_extend_delete_instance($cm->id, $pulseid);
            return true;
        }
    }
    return false;
}

/**
 * Features that supports by pulse module.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_MOD_PURPOSE
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function pulse_supports($feature) {
    if (defined('FEATURE_MOD_PURPOSE') && $feature == FEATURE_MOD_PURPOSE) {
        return MOD_PURPOSE_ADMINISTRATION;
    }
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return false;
        default:
            return null;
    }
}

/**
 * To make the stealth enable to pulse, it must have view link. So pulse supports the view option on features.
 * On dynamic check, removed the view link. if module hidden from students then pulse should has view support.
 *
 * @param cm_info $cm
 * @return void
 */
function mod_pulse_cm_info_dynamic(cm_info &$cm) {
    if ($cm->visible) {
        $cm->set_no_view_link();
    }
}

/**
 * Pulse form editor element options.
 *
 * @return array
 */
function pulse_get_editor_options() {
    return array(
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'trusttext' => true
    );
}

/**
 * Update the user id in the db notified users list.
 *
 * @param  mixed $users List of users currently notified.
 * @param  mixed $pulse Pulse instance object.
 * @return void
 */
function mod_pulse_update_notified_users($users, $pulse) {
    global $DB;

    if (!empty($users)) {
        foreach ($users as $userid) {
            $record = new stdclass();
            $record->userid = $userid;
            $record->pulseid = $pulse->id;
            $record->status = 1;
            $record->timecreated = time();
            $records[] = $record;
        }
        $DB->insert_records('pulse_users', $records);
    }
}

/**
 * Replace email template placeholders with dynamic datas.
 *
 * @param  mixed $templatetext Email Body content with placeholders
 * @param  mixed $subject Mail subject with placeholders.
 * @param  mixed $course Course object data.
 * @param  mixed $user User data object.
 * @param  mixed $mod Pulse module data object.
 * @param  mixed $sender Sender user data object. - sender is the first enrolled teacher in the course of module.
 * @return array Updated subject and message body content.
 */
function mod_pulse_update_emailvars($templatetext, $subject, $course, $user, $mod, $sender) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');
    $sender = $sender ? $sender : core_user::get_support_user(); // Support user.
    $amethods = EmailVars::vars(); // List of available placeholders.
    $vars = new EmailVars($user, $course, $sender, $mod);

    foreach ($amethods as $funcname) {
        $replacement = "{" . $funcname . "}";
        // Message text placeholder update.
        if (stripos($templatetext, $replacement) !== false) {
            $val = $vars->$funcname;
            // Placeholder found on the text, then replace with data.
            $templatetext = str_replace($replacement, $val, $templatetext);
        }
        // Replace message subject placeholder.
        if (stripos($subject, $replacement) !== false) {
            $val = $vars->$funcname;
            $subject = str_replace($replacement, $val, $subject);
        }
    }
    return [$subject, $templatetext];
}

/**
 * Filter the users who has access to view the instance and not notified before.
 *
 * @param  mixed $students List of student users enrolled in course.
 * @param  mixed $instance Pulse instance
 * @return array List of students who has access.
 */
function mod_pulse_get_course_students($students, $instance) {
    global $DB, $CFG;
    // Filter available users.
    pulse_mtrace('Filter users based on their availablity..');
    foreach ($students as $student) {
        $modinfo = new \course_modinfo((object) $instance->course, $student->id);
        $cm = $modinfo->get_cm($instance->cm->id);
        if (!$cm->uservisible || pulseis_notified($student->id, $instance->pulse->id)) {
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
function pulseis_notified($studentid, $pulseid) {
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
function mod_pulse_messagetouser($userto, $subject, $messageplain, $messagehtml, $pulse, $sender=true) {
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
        pulse_mtrace( "Pulse send to the user.");
        return true;
    } else {
        pulse_mtrace( "Failed - Pulse send to the user. -".fullname($userto), true);
        return false;
    }
}

/**
 * Get list of instance added in the course.
 *
 * @param  int $courseid Course id.
 * @return array list of pulse instance added in the course.
 */
function pulse_course_instancelist($courseid) {
    global $DB;
    $sql = "SELECT cm.*, pl.name FROM {course_modules} cm
            JOIN {pulse} pl ON pl.id = cm.instance
            WHERE cm.course=:courseid AND cm.module IN (SELECT id FROM {modules} WHERE name=:pulse)";
    return $DB->get_records_sql($sql, ['courseid' => $courseid, 'pulse' => 'pulse']);
}

/**
 * Serve the files from the Pulse file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function pulse_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE && $context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    // Get extended plugins fileareas.
    $availablefiles = pulse_extend_filearea();
    $availablefiles += ['pulse_content', 'intro', 'notificationheader', 'notificationfooter'];
    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, $availablefiles)) {
        return false;
    }

    // Item id is 0.
    $itemid = array_shift($args);

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // ...$args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // ...$args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_pulse', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
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
function mod_pulse_cron_task($extend=true) {
    global $DB;

    pulse_mtrace( 'Fetching notificaion instance list - MOD-Pulse INIT ');

    if ($extend && pulse_extend_invitation()) {
        return true;
    }

    $rolesql = "SELECT  rc.roleid FROM {role_capabilities} rc
            JOIN {capabilities} cap ON rc.capability = cap.name
            JOIN {context} ctx on rc.contextid = ctx.id
            WHERE rc.capability = :capability ";
    $roles = $DB->get_records_sql($rolesql, ['capability' => 'mod/pulse:notifyuser']);
    $roles = array_column($roles, 'roleid');

    list($roleinsql, $roleinparams) = $DB->get_in_or_equal($roles);

    $sql = "SELECT nt.id AS nid, nt.*, '' AS pulseend,
        cm.id as cmid, cm.*, md.id AS mid,
        ctx.id as contextid, ctx.*, cu.id as courseid, cu.* FROM {pulse} nt
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

        $instance = new stdclass();
        $instance->pulse = (object) $pulse;
        $instance->course = (object) $course;
        $instance->context = (object) $context;
        $instance->cm = (object) $cm;
        $instance->students = $students;
        pulse_set_notification_adhoc($instance);
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
function pulse_set_notification_adhoc($instance) {
    $task = new \mod_pulse\task\sendinvitation();
    $task->set_custom_data($instance);
    $task->set_component('pulse');
    \core\task\manager::queue_adhoc_task($task, true);
}

/**
 * Add a get_coursemodule_info function in case any pulse type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function pulse_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionavailable, completionself, completionapproval, completionapprovalroles';
    if (!$pulse = $DB->get_record('pulse', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $pulse->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('pulse', $pulse, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionself'] = $pulse->completionself;
        $result->customdata['customcompletionrules']['completionwhenavailable'] = $pulse->completionavailable;
        $result->customdata['customcompletionrules']['completionapproval'] = $pulse->completionapproval;
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($pulse->completionapprovalroles ) {
        $result->customdata['completionapprovalroles'] = $pulse->completionapprovalroles;

    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_pulse_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionwhenavailable':
                $descriptions[] = get_string('completionwhenavailable', 'pulse');
                break;
            case 'completionself':
                $descriptions[] = get_string('completionself', 'pulse');
                break;
            case 'completionapproval':
                $descriptions[] = get_string('completionrequireapproval', 'pulse');
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Obtains the automatic completion state for this pulse based on any conditions
 * in forum settings.
 *
 * @param  object $course Course data record
 * @param  object $cm Course-module data object
 * @param  int $userid User ID
 * @param  bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @param  mixed $pulse Pulse instance data record
 * @param  mixed $completion Completion data.
 * @param  mixed $modinfo Module info class object.
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function pulse_get_completion_state($course, $cm, $userid, $type, $pulse=null, $completion=null, $modinfo=null) {
    global $CFG, $DB;

    if ($pulse == null) {
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance], "*", MUST_EXIST);
    }

    if ($completion == null) {
        $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulse->id]);
    }
    $status = $type;
    // Module availablity completion for student.
    if ($pulse->completionavailable) {
        if ($modinfo == null) {
            $modinfo = get_fast_modinfo($course->id, $userid);
            $cm = $modinfo->get_cm($cm->id);
            $isvisble = $cm->uservisible;
        } else {
            $cm = $modinfo->get_cm($cm->id);
            $info = new \core_availability\info_module($cm);
            $str = '';
            // Get section info for cm.
            // Check section is accessable by user.
            $section = $cm->get_section_info();
            $sectioninfo = new \core_availability\info_section($section);
            $isvisble = pulse_mod_uservisible($cm, $userid, $sectioninfo, $modinfo, $info);
        }
        if ($isvisble) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }

    // Completion by any selected role user.
    if ($pulse->completionapproval) {
        if (!empty($completion) && $completion->approvalstatus == 1) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }
    // Self completion by own.
    if ($pulse->completionself) {
        if (!empty($completion) && $completion->selfcompletion == 1) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }

    return $status;
}

/**
 * Check user has access to the module
 *
 * @param cm_info $cm Course Module instance
 * @param int $userid User record id
 * @param \core_availability\info_section $sectioninfo Section availability info
 * @param  course_modinfo $modinfo course Module info.
 * @param \core_availability\info_module $info Module availability info.
 * @return void
 */
function pulse_mod_uservisible($cm, $userid, $sectioninfo, $modinfo, $info) {
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
 * Seperate the record data into context and course and cm.
 * In function mod_pulse_completion_crontask, data fetched using JOIN queries,
 * Here the joined datas are seperated.
 *
 * @param  mixed $keys List of fields available for sql data return.
 * @param  mixed $record Pulse Instance data from sql data
 * @return array Returns course, context, cm data.
 */
function pulse_process_recorddata($keys, $record) {
    // Context.
    $ctxpos = array_search('contextid', $keys);
    $ctxendpos = array_search('locked', $keys);
    $context = array_slice($record, $ctxpos, ($ctxendpos - $ctxpos) + 1 );
    $context['id'] = $context['contextid'];
    unset($context['contextid']);
    // Course module.
    $cmpos = array_search('cmid', $keys);
    $cmendpos = array_search('deletioninprogress', $keys);
    $cm = array_slice($record, $cmpos, ($cmendpos - $cmpos) + 1 );
    $cm['id'] = $cm['cmid'];
    unset($cm['cmid']);
    // Course records.
    $coursepos = array_search('courseid', $keys);
    $course = array_slice($record, $coursepos);
    $course['id'] = $course['courseid'];

    return [0 => $course, 1 => $context, 2 => $cm];
}


/**
 * Cron task for completion check for students in all pulse module.
 *
 * @return void
 */
function mod_pulse_completion_crontask() {
    global $DB, $USER, $CFG;

    require_once($CFG->dirroot.'/mod/pulse/locallib.php');

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
                    pc.approvalstatus, pc.selfcompletion, cmc.id as coursemodulecompletionid, cmc.completionstate as completionstate
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
        $students = $DB->get_records_sql($usersql, $params);

        $courseid = $pulse['course'];
        $course = (object) $course;
        $pulse = (object) $pulse;
        if (!in_array($courseid, $modinfo)) {
            $modinfo[$courseid] = new \pulse_course_modinfo($course, 0);
        }
        if (!empty($modinfo[$courseid]->cms[$cm['id']])) {
            $cm = $modinfo[$course->id]->get_cm($cm['id']);
            if (!empty($students)) {
                $completion = new completion_info($course);
                $context = context_module::instance($cm->id);
                if ($completion->is_enabled($cm) ) {
                    foreach ($students as $key => $user) {
                        $modinfo[$course->id]->set_userid($user->id);
                        $md = $modinfo[$course->id];
                        // Get pulse module completion state for user.
                        $currentstate = ($user->completionstate) ?? COMPLETION_INCOMPLETE;
                        $result = pulse_get_completion_state($course, $cm, $user->id, $currentstate, $pulse, $user, $md);
                        if (isset($user->completionstate) && $result == $currentstate) {
                            continue;
                        }
                        $activitycompletion = new \stdclass();
                        $activitycompletion->coursemoduleid = $cm->id;
                        $activitycompletion->userid = $user->id;
                        $activitycompletion->viewed = null;
                        $activitycompletion->overrideby = null;
                        if ($user->coursemodulecompletionid == '') {
                            $activitycompletion->completionstate = $result;
                            $activitycompletion->timemodified = time();
                            $activitycompletion->id = $DB->insert_record('course_modules_completion', $activitycompletion);
                        } else {
                            $activitycompletion->id = $user->coursemodulecompletionid;
                            $activitycompletion->completionstate = $result;
                            $activitycompletion->timemodified = time();
                            $DB->update_record('course_modules_completion', $activitycompletion);
                        }
                        pulse_mtrace("Updated course module completion - user ". $user->id);

                        // Trigger an event for course module completion changed.
                        $event = \core\event\course_module_completion_updated::create(array(
                            'objectid' => $activitycompletion->id,
                            'context' => (object) $context,
                            'relateduserid' => $user->id,
                            'other' => array(
                                'relateduserid' => $user->id
                            )
                        ));
                        $event->add_record_snapshot('course_modules_completion', $activitycompletion);
                        $event->trigger();

                    }
                }
            } else {
                pulse_mtrace('There is not users to update pulse module completion');
            }
        }
    }
    pulse_mtrace('Course module completions are updated for all pulse module....');
    return true;
}

/**
 * Generate approval buttons and self mark completions buttons based on user roles and availability.
 *
 * @param  array $args List of modules id available in the course page
 * @return string encoded html string.
 */
function mod_pulse_output_fragment_completionbuttons($args) {
    global $CFG, $DB, $USER;

    $modules = json_decode($args['modules']);
    list($insql, $inparams) = $DB->get_in_or_equal($modules);
    $sql = "SELECT cm.*, nf.completionapproval, nf.completionapprovalroles, nf.completionself FROM {course_modules} cm
    JOIN {modules} AS md ON md.id = cm.module
    JOIN {pulse} nf ON nf.id = cm.instance WHERE cm.id $insql AND md.name = 'pulse'";
    $records = $DB->get_records_sql($sql, $inparams);

    $html = [];

    foreach ($modules as $moduleid) {
        if (isset($records[$moduleid])) {
            $data = $records[$moduleid];
            $html[$moduleid] = '';
            $extend = true;
            // Approval button generation for selected roles.
            if ($data->completionapproval == 1) {
                $roles = $data->completionapprovalroles;
                if (pulse_has_approvalrole($roles, $moduleid)) {
                    $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid]);
                    $html[$moduleid] .= html_writer::tag('div',
                        html_writer::link($approvelink, get_string('approveuserbtn', 'pulse'),
                        ['class' => 'btn btn-primary pulse-approve-users']),
                        ['class' => 'approve-user-wrapper']
                    );
                } else if (pulse_user_isstudent($moduleid)) {
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $message = pulse_user_approved($records[$moduleid]->instance, $USER->id) ) {
                        $html[$moduleid] .= $message.'<br>';
                    }
                }
            }
            // Generate self mark completion buttons for students.
            if (mod_pulse_is_uservisible($moduleid, $USER->id, $data->course)) {
                if ($data->completionself == 1 && pulse_user_isstudent($moduleid)
                    && !pulse_isusercontext($data->completionapprovalroles, $moduleid)) {
                    // Add self mark completed informations.
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $date = pulse_already_selfcomplete($records[$moduleid]->instance, $USER->id)) {
                        $selfmarked = get_string('selfmarked', 'pulse', ['date' => $date]).'<br>';
                        $html[$moduleid] .= html_writer::tag('div', $selfmarked,
                        ['class' => 'pulse-self-marked badge badge-success']);
                    } else {
                        $selfcomplete = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid, 'action' => 'selfcomplete']);
                        $selfmarklink = html_writer::link($selfcomplete, get_string('markcomplete', 'pulse'),
                            ['class' => 'btn btn-primary pulse-approve-users']
                        );
                        $html[$moduleid] .= html_writer::tag('div', $selfmarklink, ['class' => 'pulse-approve-users']);
                    }
                }
            } else {
                $extend = false;
            }
            // Extend the pro features if the logged in users has able to view the module.
            if ($extend) {
                $instance = new stdclass();
                $instance->pulse = $data;
                $instance->pulse->id = $data->instance;
                $instance->user = $USER;
                $html[$moduleid] .= pulse_extend_reaction($instance, 'content');
            }
        }
    }

    return json_encode($html);
}

/**
 * Add the completion and reaction buttons with pulse content on view page.
 *
 * @param cm_info $cm Current Course module.
 * @param stdclass $pulse Pulse record object.
 * @return string $html Completion and reaction buttons html content.
 */
function mod_pulse_cm_completionbuttons(cm_info $cm, stdclass $pulse): string {
    global $USER, $DB;
    $html = '';
    $moduleid = $cm->id;
    $extend = true;
    // Approval button generation for selected roles.
    if ($pulse->completionapproval == 1) {
        $roles = $pulse->completionapprovalroles;
        if (pulse_has_approvalrole($roles, $cm->id)) {
            $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $cm->id]);
            $html .= html_writer::tag('div',
                html_writer::link($approvelink, get_string('approveuserbtn', 'pulse'),
                ['class' => 'btn btn-primary pulse-approve-users']),
                ['class' => 'approve-user-wrapper']
            );
        } else if (pulse_user_isstudent($cm->id)) {
            if (!class_exists('core_completion\activity_custom_completion')
                && $message = pulse_user_approved($cm->instance, $USER->id)) {
                $html .= $message.'<br>';
            }
        }
    }

    // Generate self mark completion buttons for students.
    if (mod_pulse_is_uservisible($moduleid, $USER->id, $cm->course)) {
        if ($pulse->completionself == 1 && pulse_user_isstudent($moduleid)
            && !pulse_isusercontext($pulse->completionapprovalroles, $moduleid)) {
            // Add self mark completed informations.
            if (!class_exists('core_completion\activity_custom_completion')
                && $date = pulse_already_selfcomplete($cm->instance, $USER->id)) {
                $selfmarked = get_string('selfmarked', 'pulse', ['date' => $date]).'<br>';
                $html .= html_writer::tag('div', $selfmarked,
                ['class' => 'pulse-self-marked badge badge-success']);
            } else if (!pulse_already_selfcomplete($cm->instance, $USER->id)) {
                $selfcomplete = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid, 'action' => 'selfcomplete']);
                $selfmarklink = html_writer::link($selfcomplete, get_string('markcomplete', 'pulse'),
                    ['class' => 'btn btn-primary pulse-approve-users']
                );
                $html .= html_writer::tag('div', $selfmarklink, ['class' => 'pulse-approve-users']);
            }
        }
    } else {
        $extend = false;
    }
    // Extend the pro features if the logged in users has able to view the module.
    if ($extend) {
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
        $instance = new stdclass();
        $instance->pulse = $pulse;
        $instance->pulse->id = $cm->instance;
        $instance->user = $USER;
        $html .= pulse_extend_reaction($instance, 'content');
    }
    return $html;
}

/**
 * Find the course module is visible to current user.
 *
 * @param  mixed $cmid
 * @param  mixed $userid
 * @param  mixed $courseid
 * @return void
 */
function mod_pulse_is_uservisible($cmid, $userid, $courseid) {
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
function pulse_has_approvalrole($completionapprovalroles, $cmid, $usercontext=true, $userid=null) {
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
    $roleassignments = $DB->get_records_sql($sql, array($userid, CONTEXT_USER));
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
function pulse_isusercontext($completionapprovalroles, $cmid) {
    global $DB, $USER;

    // Test user has user context.
    $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
            FROM {role_assignments} ra
            JOIN {context} c ON ra.contextid = c.id
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ? and c.contextlevel = ?
            ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
    $roleassignments = $DB->get_records_sql($sql, array($USER->id, CONTEXT_USER));
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
function pulse_user_getmentessuser() {
    global $DB, $USER;

    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid
                                            FROM {role_assignments} ra, {context} c, {user} u
                                            WHERE ra.userid = ?
                                                    AND ra.contextid = c.id
                                                    AND c.instanceid = u.id
                                                    AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {

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
function pulse_user_approved($pulseid, $userid) {
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
function pulse_user_isstudent($cmid) {
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
function pulse_already_selfcomplete($pulseid, $userid) {
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
 * Trigger the add pulse instance.
 *
 * @param  mixed $pulseid
 * @param  mixed $pulse
 * @return void
 */
function pulse_extend_add_instance($pulseid, $pulse) {
    $callbacks = get_plugins_with_function('extend_pulse_add_instance');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($pulseid, $pulse);
        }
    }
}

/**
 * Trigger pulse extended plugins to do their own update steps.
 *
 * @param  mixed $pulse Pulse instance data.
 * @param  mixed $context Context module.
 * @return void
 */
function pulse_extend_update_instance($pulse, $context) {
    $callbacks = get_plugins_with_function('extend_pulse_update_instance');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($pulse, $context);
        }
    }
}

/**
 * Trigger pulse extended plugins delete function to do their own delete steps.
 *
 * @param  mixed $cmid Module context id
 * @param  mixed $pulseid Pulse instance id.
 * @return void
 */
function pulse_extend_delete_instance($cmid, $pulseid) {
    $callbacks = get_plugins_with_function('extend_pulse_delete_instance');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($cmid, $pulseid);
        }
    }
}

/** Inject form elements into mod instance form.
 *
 * @param  mform $mform the form to inject elements into.
 * @param  mixed $instance Pulse instance.
 * @param  mixed $method Method of form fields (=reaction only returns the reaction form fields)
 * @return void
 */
function mod_pulse_extend_form($mform, $instance, $method='') {
    $callbacks = get_plugins_with_function('extend_pulse_form');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($mform, $instance, $method);
        }
    }
}

/** Extende the pro plugins validation error messages.
 *
 * @param  mixed $data module form submitted data.
 * @param  mixed $files Module form submitted files.
 * @return array list of validation errors.
 */
function mod_pulse_extend_formvalidation($data, $files) {
    $callbacks = get_plugins_with_function('extend_pulse_validation');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            return $pluginfunction($data, $files);
        }
    }
}

/** Inject form elements into mod instance form.
 * @param mform $mform the form to inject elements into.
 */
function mod_pulse_extend_formdata($mform) {
    $callbacks = get_plugins_with_function('extend_pulse_formdata');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($mform);
        }
    }
}

/** Extend form post process method from pro plugin.
 * @param object $data module form submitted data object.
 */
function pulse_extend_postprocessing($data) {
    $callbacks = get_plugins_with_function('extend_pulse_postprocessing');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($data);
        }
    }
}

/**
 * Extended the support of data processing before defalut values are set to form.
 *
 * @param  mixed $defaultvalues Current default values.
 * @param  mixed $currentinstance status of instance is current (true/false)
 * @param  mixed $context Module context data record.
 * @return void
 */
function pulse_extend_preprocessing(&$defaultvalues, $currentinstance, $context) {
    $callbacks = get_plugins_with_function('extend_pulse_preprocessing');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $pluginfunction($defaultvalues, $currentinstance, $context);
        }
    }
}

/**
 * Call the extended email placeholder filters to replace the content.
 *
 * @param  mixed $instance Pulse instance data object.
 * @param  mixed $displaytype Location to display the reaction.
 * @return string $html
 */
function pulse_extend_reaction($instance, $displaytype='notification') {
    $html = '';
    $callbacks = get_plugins_with_function('extend_pulse_reaction');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $html .= $pluginfunction($instance, $displaytype);
        }
    }
    return $html;
}


/**
 * Check the pulsepro extended the invitation method.
 * if extended the invitation then the invitations are send using pulse pro plugin.
 * @return void
 */
function pulse_extend_invitation() {
    $callbacks = get_plugins_with_function('extend_pulse_invitation');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            return $pluginfunction();
        }
    }
}

/**
 * List of extended the function used in the backup steps.
 *
 * @param  mixed $pulse
 * @param  mixed $userinfo
 * @return void
 */
function pulse_extend_backup_steps($pulse, $userinfo) {
    $callbacks = get_plugins_with_function('extend_pulse_backup_steps');
    if (!empty($callbacks)) {
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                return $pluginfunction($pulse, $userinfo);
            }
        }
    }
    return $pulse;
}

/**
 * List of extended plugins restore contents.
 *
 * @param  mixed $contents
 * @return void
 */
function pulse_extend_restore_content(&$contents) {
    $callbacks = get_plugins_with_function('extend_pulse_restore_content');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $contents = $pluginfunction($contents);
        }
    }
}

/**
 * Extended plugins restore structures used in the acitivty restore.
 *
 * @param  mixed $paths
 * @return void
 */
function pulse_extend_restore_structure(&$paths) {
    $callbacks = get_plugins_with_function('extend_pulse_restore_structure');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $paths = $pluginfunction($paths);
        }
    }
}

/**
 * List of extended plugins fileareas list to add into pluginfile function.
 *
 * @return void
 */
function pulse_extend_filearea(): array {
    $callbacks = get_plugins_with_function('extend_pulse_filearea');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $fileareas = $pluginfunction();
            return $fileareas;
        }
    }
    return [];
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_pulse_core_calendar_provide_event_action(calendar_event $event,
                                                     \core_calendar\action_factory $factory,
                                                     int $userid = 0) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['pulse'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $context = context_module::instance($cm->id);

    if (!has_capability('mod/pulse:notifyuser', $context, $userid)) {
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/pulse/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Extend the pro features of preset. Triggered during the import preset data clean.
 *
 * @param string $method Preset method to extend
 * @param array $backupdata Preset template data.
 * @return void
 */
function pulse_extend_preset($method, &$backupdata) {
    $callbacks = get_plugins_with_function('extend_preset_formatdata');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $backupdata = $pluginfunction($method, $backupdata);
        }
    }
}

/**
 * Extend the pro features of preset. Convert the record data format into moodle form editor format.
 *
 * @param string $pulseid Preset method to extend
 * @param array $configdata Custom config data.
 * @return void
 */
function pulse_preset_update($pulseid, $configdata) {
    $callbacks = get_plugins_with_function('extend_preset_update');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $backupdata = $pluginfunction($pulseid, $configdata);
        }
    }
}

/**
 * Fragement output to list all the presets in the pulse module add/edit form.
 *
 * @param array $args context and Course ID with context.
 */
function mod_pulse_output_fragment_get_presetslist(array $args) {
    global $OUTPUT;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $courseid = $args['courseid'];
    $presets = \mod_pulse\preset::generate_presets_list($courseid);
    return $OUTPUT->render_from_template('mod_pulse/presets_list', $presets);
}

/**
 * Fragement output to preview the selected preset. Loads all the available informations and configurable params as form elements.
 *
 * @param array $args Preset ID and Course ID with context.
 */
function mod_pulse_output_fragment_get_preset_preview(array $args) : ?string {
    global $CFG;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $presetid = $args['presetid'];
    $courseid = $args['courseid'];
    $sectionid = $args['section'];
    $preset = new mod_pulse\preset($presetid, $courseid, $context, $sectionid);
    return $preset->output_fragment();
}

/**
 * Fragement output to result of apply methods on selected preset.
 * Trigger the apply preset method in preset to create the pulse module using the selected preset and apply method.
 *
 * @param array $args Custom config data and Current module form data with context.
 */
function mod_pulse_output_fragment_apply_preset(array $args) : ?string {
    global $CFG;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $formdata = $args['formdata'];
    $pageparams = $args['pageparams'];
    $external = new \mod_pulse\external();
    $result = $external->apply_presets($context->id, $formdata, $pageparams);

    return $result;
}

/**
 * Create presets during the plugin installation and upgradation.
 *
 * @param array $presets List of presets with details.
 * @param boolean $pro Create template for pro version.
 * @return array List of created presets id.
 */
function pulse_create_presets($presets=[], $pro=false) {
    global $DB, $CFG;
    if (!isloggedin() || isguestuser()) {
        return [];
    }
    $fs = get_file_storage();
    if (empty($presets)) {
        $presets = pulse_free_presets();
    }
    foreach ($presets as $key => $preset) {
        $sql = "SELECT id FROM {pulse_presets} WHERE ".$DB->sql_like('title', ':title');
        if ($DB->record_exists_sql($sql, ['title' => $preset['title']])) {
            continue;
        }
        $file = $preset['preset_template'];
        $preset['preset_template'] = file_get_unused_draft_itemid();
        $presetid = $DB->insert_record('pulse_presets', $preset);

        $filerecord = new stdClass();
        $filerecord->component = 'mod_pulse';
        $filerecord->contextid = \context_system::instance()->id;
        $filerecord->filearea = "preset_template";
        $filerecord->filepath = '/';
        $filerecord->itemid = $presetid;
        $filerecord->filename = $file;

        if (!$fs->file_exists($filerecord->contextid, $filerecord->component, $filerecord->filearea,
        $filerecord->itemid, $filerecord->filepath, $filerecord->filename)) {
            if ($pro) {
                $backuppath = $CFG->dirroot . "/local/pulsepro/assets/$file";
            } else {
                $backuppath = $CFG->dirroot . "/mod/pulse/assets/$file";
            }
            $fs->create_file_from_pathname($filerecord, $backuppath);
        }
        $created[] = $presetid;
    }
    return (isset($created)) ? $created : [];
}

/**
 * Demo presets data shipped with plugin by default for demo purpose.
 *
 * @return array List of demo presets.
 */
function pulse_free_presets(): array {
    global $CFG;
    if (file_exists($CFG->dirroot.'/mod/pulse/assets/presets.xml')) {
        $presetsxml = simplexml_load_file($CFG->dirroot.'/mod/pulse/assets/presets.xml');
        $result = json_decode(json_encode($presetsxml), true);
        return $result;
    }
    return array();
}

/**
 * Update the pulse content with bootstrap box before rendered in course page.
 *
 * @param cm_info $cm
 * @return void
 */
function mod_pulse_cm_info_view(cm_info $cm) {
    global $DB, $USER;

    $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
    $content = $cm->content;
    $course = $cm->get_course();
    $senderdata = \mod_pulse\task\sendinvitation::get_sender($course->id, $cm->context->id);
    $sender = \mod_pulse\task\sendinvitation::find_user_sender($senderdata, $USER->id);
    list($subject, $content) = mod_pulse_update_emailvars($content, '', $course,
                            $USER, $pulse, $sender);
    $cm->set_content($content);
    if (isset($pulse->cssclass) && $pulse->cssclass) {
        $cm->set_extra_classes($pulse->cssclass);
    }
    if (isset($pulse->displaymode) && $pulse->displaymode == 1) {
        $boxtype = ($pulse->boxtype) ? $pulse->boxtype : 'primary';
        $boxicon = ($pulse->boxicon) ? $pulse->boxicon : '';
        $content = $cm->content;
        $content = mod_pulse_render_content($content, $boxicon, $boxtype);
        $cm->set_content($content);
    }

    $completionbtn = mod_pulse_cm_completionbuttons($cm, $pulse);
    if (!empty($completionbtn)) {
        $content = $cm->content;
        $content .= html_writer::tag('div', $completionbtn, ['class' => 'pulse-completion-btn']);
        $cm->set_content($content);
    }
}

/**
 * Render the pulse content with selected box container with box icon.
 *
 * @param string $content Pulse content.
 * @param string $boxicon Icon.
 * @param string $boxtype Box type name (primary, secondory, danger, warning and others).
 * @return string Pulse content with box container.
 */
function mod_pulse_render_content(string $content, string $boxicon, string $boxtype = 'primary'): string {
    global $OUTPUT;
    $html = html_writer::start_tag('div', ['class' => 'pulse-box']);
    $html .= html_writer::start_tag('div', ['class' => 'alert alert-'.$boxtype]);
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
 * Custom method to prevent the mtrace logs based on admin config.
 *
 * @param string $message Message to log on cron.
 * @param bool $detail Need to display this in log even detailedlog config disable state.
 * @return void
 */
function pulse_mtrace($message, $detail=false) {
    $showdetail = get_config('mod_pulse', 'detailedlog');
    if ($showdetail || $detail) {
        mtrace($message);
    }
}
