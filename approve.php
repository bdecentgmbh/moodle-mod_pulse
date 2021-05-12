<?php

require_once('../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$userid = optional_param('userid', null, PARAM_INT);

$PAGE->set_url( new moodle_url('/mod/pulse/approve.php', ['cmid' => $cmid]) );
$modulecontext = context_module::instance($cmid);

$cm = get_coursemodule_from_id('pulse', $cmid);
$pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
$course = get_course($cm->course);
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_context($modulecontext);
$PAGE->set_heading(get_string('approveuser', 'pulse', ['course' => $course->fullname]));

$approvalroles = json_decode($pulse->completionapprovalroles);
$roles = get_user_roles($modulecontext, $USER->id);

$hasrole = false;
foreach ($roles as $key => $role) {
    if (in_array($role->roleid, $approvalroles)) {
        $hasrole = true;
    }
}

require_login();

if ($action != 'selfcomplete') {
    if (!$hasrole) {
        throw new moodle_exception('missingrequiredrole');
    }
}


if ($action == 'approve' && $userid) {
    if ($record = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $cm->instance])) {
        $record->approvalstatus = 1;
        $record->approveduser = $USER->id;
        $record->approvaltime = time();
        $record->timemodified = time();
        $result = $DB->update_record('pulse_completion', $record);
    } else {
        $record = new stdclass();
        $record->userid = $userid;
        $record->pulseid = $cm->instance;
        $record->approvalstatus = 1;
        $record->approveduser = $USER->id;
        $record->timemodified = time();
        $record->approvaltime = time();
        $result = $DB->insert_record('pulse_completion', $record);
    }

    if ($result) {
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $pulse->completionapproval) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        }
        redirect($PAGE->url, 'User approved successfully');
    }
} else if ($action == 'decline') {
    if ($record = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $cm->instance])) {
        $record->approvalstatus = 0;
        $record->approveduser = $USER->id;        
        $result = $DB->update_record('pulse_completion', $record);
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $pulse->completionapproval) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
        }
        redirect($PAGE->url, 'User completion declined');
    }

} else if ($action == 'selfcomplete') {

    if ($record = $DB->get_record('pulse_completion', ['userid' => $USER->id, 'pulseid' => $cm->instance])) {
        $record->selfcompletion = 1;
        $record->selfcompletiontime = time();
        $record->timemodified = time();
        $result = $DB->update_record('pulse_completion', $record);
    } else {
        $record = new stdclass();
        $record->userid = $USER->id;
        $record->pulseid = $cm->instance;
        $record->selfcompletion = 1;
        $record->selfcompletiontime = time();
        $record->timemodified = time();
        $result = $DB->insert_record('pulse_completion', $record);
    }

    if ($result) {
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $pulse->completionself) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        }
        redirect(new moodle_url('/course/view.php', ['id' => $course->id]), 'Marked as completed');
    }
}

$approveuser = new mod_pulse\approveuser($cmid);
echo $OUTPUT->header();
echo $approveuser->getuserslist();
echo $OUTPUT->footer();