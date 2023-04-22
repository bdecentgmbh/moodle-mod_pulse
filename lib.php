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
require_once($CFG->dirroot.'/mod/pulse/lib/extendpro.php');
require_once($CFG->dirroot.'/mod/pulse/lib/pulse_course_modinfo.php');

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
 * @param  int $userid User ID.
 * @param  mixed $pulse Pulse instance object.
 * @return bool
 */
function mod_pulse_update_notified_user($userid, $pulse) {
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
