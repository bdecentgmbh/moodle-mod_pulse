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
 * Notification pulse action - Language strings defined.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Pulse notifications';

// ...Capabilities.
$string['notification:receivenotification'] = 'Recevie notifications from pulse';
$string['notification:sender'] = 'Sender of the automation notification';


$string['courseteacher'] = 'Course teacher';
$string['groupteacher'] = 'Group teacher';
$string['tenantrole'] = 'Tenant role';
$string['custom'] = 'Custom';
$string['senderemail'] = 'Sender email';
$string['once'] = 'Once';
$string['daily'] = 'Daily';
$string['weekly'] = 'Weekly';
$string['monthly'] = 'Monthly';
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';
$string['none'] = 'None';
$string['before'] = 'Before';
$string['after'] = 'After';

$string['suppressnotification'] = 'Suppress notification';
$string['dynamicplacholder'] = 'Placeholder';
$string['dynamicdescription'] = 'Description';
$string['dynamiccontent'] = 'Content';

$string['teaser'] = 'Teaser';
$string['full_linked'] = 'Full linked';
$string['full_not_linked'] = 'Full not linked';

// ...Form tab.
$string['formtab'] = 'Notification';
$string['notifyusers'] = 'Send notification';

// ... Report builder.
$string['notificationreport'] = 'Pulse Schedules';
$string['timecreated'] = 'Time created';
$string['nextrun'] = 'Datetime to send notification';
$string['status'] = 'Status';
$string['subject'] = 'Subject';
$string['messagetype'] = 'Message type';
// ...Status of the schedule.
$string['queued'] = 'Queued';
$string['sent'] = 'sent';
$string['failed'] = 'Failed';
$string['onhold'] = 'On hold';
$string['schedulecreatedtime'] = 'Schedule created time';
$string['scheduledtime'] = 'Scheduled time';
$string['cohort'] = 'Cohort';
$string['readmore'] = 'Read more';
$string['instanceid'] = 'Instance ID';

// Help texts.
// Sender.
$string['sender'] = 'Sender';
$string['sender_help'] = 'Choose the sender of the notification from the following options:<br><b>Course Teacher</b>: The notification will be sent from the course teacher (the first one assigned if there are several). If the user is not in any group, it falls back to the site support contact. Note that this is determined by capability, not by an actual role.<br><b>Group Teacher</b>: The notification will be sent from the non-editing teacher who is a member of the same group as the user (the first one assigned if there are several). If there\'s no non-editing teacher in the group, it falls back to the course teacher. Note that this is determined by capability, not by an actual role.<br><b>Tenant Role (Workplace Feature)</b>: The notification will be sent from the user assigned to the specified role in the tenant (the first one assigned if there are several). If there\'s no user with the selected role, it falls back to the site support contact. Note that this is determined by capability, not by an actual role.<br><b>Custom</b>: If selected, an additional setting for "Sender Email" will be displayed where you can enter a specific email address to be used as the sender.';
// Interval.
$string['interval'] = 'Interval';
$string['interval_help'] = 'Choose the interval for sending notifications:<br><b>Once</b>: Send the notification only one time.<br><b>Daily</b>: Send the notification every day at the time selected below.<br><b>Weekly</b>: Send the notification every week on the day of the week and time of below.<br><b>Monthly</b>: Send the notification every month on the day of the month and time of below.';
// Delay.
$string['delay'] = 'Delay';
$string['delay_help'] = 'Choose the delay option for sending notifications:
<br><b>None:</b> Send notifications immediately upon the condition being met, considering the schedule limitations (e.g., weekday or time of day).<br><b>Before:</b> Send the notification a specified number of days/hours before the condition is met. Note that this is only possible for timed events, e.g., appointment sessions.<br><b>After:</b> Send the notification a specified number of days/hours after the condition is met. This is possible for all conditions.';
// Delay duration.
$string['delayduraion'] = 'Delay duraion';
$string['delayduraion_help'] = 'Please enter the duration time for the delay in sending the notification. This duration should be specified in terms of days or hours, depending on the selected delay option.';
// Limit.
$string['limit'] = 'Limit of the notifications';
$string['limit_help'] = 'Enter a number to limit the total number of notifications sent. <br><b>Note:</b>Enter "0" for no limit. This is only relevant if the schedule is not set to "<i>Once</i>".';
// Recipients.
$string['recipients'] = 'Recipients';
$string['recipients_help'] = 'Select one or more roles that have the capability to receive notifications. By default, it\'s set for all graded roles, including students. Users selected here will be used in the query to determine who gets notifications.';
// CC recipients.
$string['ccrecipients'] = 'Cc ';
$string['ccrecipients_help'] = 'Select course context and user context roles that will receive the notification as a <b>CC (Carbon Copy)</b> to the main recipient. Course context roles determine users by enrolment in the course and membership of a group, while user context roles determine users by their relation to the recipient (assigned role in user).';
// BCC recipients.
$string['bccrecipients'] = 'Bcc ';
$string['bccrecipients_help'] = 'Select course context and user context roles that will receive the notification as a <b>BCC (Blind Carbon Copy)</b> to the main recipient. Course context roles determine users by enrolment in the course and membership of a group, while user context roles determine users by their relation to the recipient (assigned role in user).';
// Subject.
$string['subject'] = 'Subject';
$string['subject_help'] = 'Enter the subject for the notification.';
// Header content.
$string['headercontent'] = 'Header content';
$string['headercontent_help'] = 'Enter the first part of the body for the notification. This field supports filters and placeholders.';
// Static content.
$string['staticcontent'] = 'Static content';
$string['staticcontent_help'] = 'Enter the second part of the body for the notification. This field supports filters and placeholders.';
// Footer content.
$string['footercontent'] = 'Footer content';
$string['footercontent_help'] = 'Enter the last part of the body for the notification. This field supports filters and placeholders.';
// Preview.
$string['preview'] = 'Preview';
$string['preview_help'] = 'Click this button to open a modal window that displays the notification, allowing you to select an example user to determine the content of the notification.';
// Suppress module.
$string['suppressmodule'] = 'Suppress module';
$string['suppressmodule_help'] = 'Choose one or more activities that, when completed, will suppress the notification from being sent. You can select the operand below to determine how these activities affect notification.';
// Suppress operator.
$string['suppressoperator'] = 'Suppress operator';
$string['suppressoperator_help'] = 'Choose the operand that determines how the selected activities completion affects the notification:<br><b>Any:</b> If any of the selected activities above are completed, the notification shall not be sent.<br><b>All:</b> If all of the selected activities above are completed, the notification shall not be sent.';
// Dynamic content.
$string['dynamiccontent'] = 'Dynamic content';
$string['dynamiccontent_help'] = 'Select an activity within the course to add content below the static content. This is only available in the automation instance within the course.';
// Content type.
$string['contenttype'] = 'Content type';
$string['contenttype_help'] = 'Choose the type of content to be added below the static content:<br><b>Description:</b> If selected, the description of the selected activity shall be added to the body of the notification.<br><b>Content:</b> If selected, the content of the selected activity shall be added to the body of the notification. Note that this should support specific mod types like Page and Book with the ability to select specific chapters.';
// Content length.
$string['contentlength'] = 'Content length';
$string['contentlength_help'] = ' Choose the content length to include in the notification:<br><b>Teaser:</b> If selected, only the first paragraph shall be used, with a "Read More" link added after it.<br><b>Full, Linked:</b> If selected, the entire content shall be used, with a link to the content after it.<br><b>Full, Not Linked:</b> If selected, the entire content shall be used without a link to the content after it.';
// Chapters.
$string['chapters'] = 'Chapters';
$string['chapters_help'] = 'Provides support to select specific chapters from a Book activity.';
// ...Course instance warning messages.
$string['coursehidden'] = 'Course is hidden from the students. <span> Please enable the visibility of the course to send notifications.</span>';
$string['noactiveusers'] = 'Course doesn\'t contain any active enrolments. <span> Please enroll users in the course.</span>';
$string['coursenotstarted'] = 'Course has not started. <span> Please set the course start date to a date in the past.</span>';
$string['courseenddatereached'] = 'Course has ended. <span> Please set the course end date to a date in the future or remove the end date.</span>';
// ...Reports filter string.
$string['automationinstance'] = 'Automation instance';
$string['automationtemplate'] = 'Automation template';

$string['privacy:metadata:pulseaction_notification'] = 'Information about the user to send the notification';
$string['privacy:metadata:pulseaction_notification_sch:userid'] = 'The ID of the user is tracked.';
$string['privacy:metadata:pulseaction_notification_sch:status'] = 'The status of the user is tracked.';
$string['privacy:metadata:pulseaction_notification_sch:timecreated'] = 'The created time of the user is tracked.';
