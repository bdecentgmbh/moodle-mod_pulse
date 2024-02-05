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
 * Pulse condition session common functions to observe moodle default hooks
 *
 * @package   pulsecondition_session
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Name of the session module.
  */
define('PULSE_SESSION_MOD', 'facetoface');

/**
 * Type of the edit attendees page.
 */
define('PULSE_SESSION_MOD_EDITPAGEID', 'mod-facetoface-editattendees');

/**
 * Extended the course navigation to observe the user add/remove from session from the backend by admin/managers.
 * Verify the add param and verifies the page is session edit attendees page. Then triggers the schedule preparation.
 *
 * @param navigation_node $navigation
 * @param stdClass $course Course info data.
 * @param \context $context
 * @return void
 */
function pulsecondition_session_extend_navigation_course(navigation_node $navigation, stdClass $course, $context) {
    global $PAGE, $SCRIPT;

    // Verify the page is facetoface edit attendees page and the admin/teachers added user to signup from backend.
    // Trigger the pulse to get the list of new user signup in this session and create a schedule for those users.
    if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {

        if ($PAGE->pagetype == PULSE_SESSION_MOD_EDITPAGEID && $PAGE->cm->modname == PULSE_SESSION_MOD) {
            \pulsecondition_session\conditionform::prepare_session_signup_schedule($PAGE->cm->instance);
            return true;
        }

        // When the error is raised during the signup, face to face throw exception,
        // This exception prevents the above schedule content to run.
        // Throw exception resets the PAGE urls, cm info, for the reason.
        // In this case the page is set as site index and the course is not frontpage but the current file path is facetoface.
        if ($PAGE->pagetype == 'site-index' && $PAGE->course->id != SITEID && $SCRIPT == '/mod/facetoface/editattendees.php') {
            \pulsecondition_session\conditionform::prepare_session_signup_schedule($PAGE->cm->instance);
            return true;
        }
    }

    // Verify the page is facetoface edit attendees page and the admin/teachers added user to signup from backend.
    // Trigger the pulse to get the list of new user signup in this session and create a schedule for those users.
    if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {

        if ($PAGE->pagetype == PULSE_SESSION_MOD_EDITPAGEID && $PAGE->cm->modname == PULSE_SESSION_MOD) {
            \pulsecondition_session\conditionform::remove_session_signup_schedule($PAGE->cm->instance);
            return true;
        }

        if ($PAGE->pagetype == 'site-index' && $PAGE->course->id != SITEID && $SCRIPT == '/mod/facetoface/editattendees.php') {
            \pulsecondition_session\conditionform::remove_session_signup_schedule($PAGE->cm->instance);
            return true;
        }

    }
}
