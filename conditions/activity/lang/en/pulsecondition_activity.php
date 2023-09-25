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
 * Strings for "Activity conditions", language 'en'.
 *
 * @package   pulsecondition_activity
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Activity completion';
$string['activitycompletion'] = 'Activity completion';
$string['activitycompletion_help'] = '<b>Activity Completion:</b> This automation will be triggered when an activity within the
    course is marked as completed. You will need to specify the activity within the automation instance.The options for activity
    completion include:<br><b>Disabled:</b> Activity completion condition is disabled.<br><b>All:</b> Activity completion
    condition applies to all enrolled users.<br><b>Upcoming:</b> Activity completion condition only applies to future enrolments.';
$string['selectactivity'] = 'Select activities';
$string['selectactivity_help'] = "The <b>Select Activities</b> setting allows you to choose from all available activities within
    your course that have completion configured. This selection determines which specific activities will trigger the automation
    when their completion conditions are met.";
