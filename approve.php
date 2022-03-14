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
 * Approve users and mark completion script.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot. '/lib/tablelib.php');
// Params.
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
// List of approval roles selected in pulse module.
$approvalroles = json_decode($pulse->completionapprovalroles);
$roles = get_user_roles($modulecontext, $USER->id);

$hasrole = false;
foreach ($roles as $key => $role) {
    if (in_array($role->roleid, $approvalroles)) {
        $hasrole = true;
    }
}
$approvalroles = $pulse->completionapprovalroles;
$hasrole = pulse_has_approvalrole($approvalroles, $cmid);

require_login();

// Prevent student to view the script expect the self completion by student.
if ($action != 'selfcomplete') {
    if (!$hasrole) {
        throw new moodle_exception('missingrequiredrole');
    }
}

// Approval by selected roles.
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
        // Update the pulse module completion state for the current user.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $pulse->completionapproval) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        }
        redirect($PAGE->url, get_string('approvedsuccess', 'mod_pulse'));
    }
} else if ($action == 'decline') {
    // Make the user approve status to declined.
    if ($record = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $cm->instance])) {
        $record->approvalstatus = 0;
        $record->approveduser = $USER->id;
        $result = $DB->update_record('pulse_completion', $record);
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $pulse->completionapproval) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
        }
        redirect($PAGE->url, get_string('approvedeclined', 'mod_pulse'));
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

// Participants table filterset.
// Approver user table - pariticipants table wrapper.
$participanttable = new \mod_pulse\table\approveuser("user-index-participants-{$cm->id}");

// Page header output.
echo $OUTPUT->header();
// List of available participants table output.
echo $participanttable->out(10, true);
// Page footer output.
echo $OUTPUT->footer();
