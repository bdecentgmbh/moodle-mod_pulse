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
 * List of translatation ready strings used on the free and pro versions.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Pulse';
$string['modulename'] = 'Pulse';
$string['modulenameplural'] = 'Pulses';
$string['pulse:addinstance'] = 'Add a new Pulse';
$string['pulse:view'] = 'View Pulse';
$string['content'] = 'Content';
$string['modulename_help'] = 'Pulse is the teacher\'s Swiss army knife to improve student engagement and compliance in moodle courses:
<ul><li><strong>Notifications</strong><br/>
Each Pulse activity can be configured to send a notification once it becomes available to the student. There are a number of placeholders that can be used to personalize the message, like the first name of the student or the name of the course. The notification can be sent to the student, the teacher, the non-editing teacher or the manager. Other course context or user context roles are supported as well, e.g. parent or staff manager.</li>
<li><strong>Completion workflows</strong><br/>
Pulse supports activity completion in three ways (in addition to moodle core ones): upon availability, when marked complete by student and when approved by other role.</li></ul>
<p>As with all things automation, it is essential to put enough thought into what you actually want. Ideally, write down what should happen in which case. This also helps us to support you! If it does not work as you expect, here are the most common issues:</p>
<ul>
<li>The course has not <b>started</b>. Pulse only works in a course which is currently running —> Check if the start date is in the past.</li>
<li>The course has <b>ended</b>. Pulse only works in a course which is currently running —> Check if the end date is set, and if it is, make sure it is in the future.</li>
<li>The course has <b>enrolled students</b>. Pulse (free) only works for students —> Check if the course actually has an enrolled user with an active enrolment status.</li>
<li>The Pulse activity is <b>available</b>. Pulse only works if the activity is available to the student. That is the "trigger" or "condition" we use to determine if we shall actually do something (e.g. award credits). —> Check if the Pulse activity is hidden or has a restriction which is not met by the student; in both cases, it is "disabled" and will not work. If you are not sure, just login as the student and check if the student sees the Pulse activity — if the student sees it, it is enabled; if the student does not see it, it is disabled.</li>
<li>The <b>cron job</b> is not running or has not run yet — awarding credits is done through a scheduled task. It is therefor required that the cron job is running regularly. Moodle recommends every minute, and we can only encourage you to follow that recommendation!</li>
</ul>';
$string['modulename_link'] = 'Pulse';
$string['privacy:metadata'] = 'The Pulse plugin does not store any personal data.';
$string['pluginadministration'] = 'Pulse administration';
$string['search:activity'] = 'Pulse';
$string['sendnotificaton'] = 'Send Pulse notification';
$string['diffnotification'] = 'Use notification content instead of general content';
$string['enable:disable'] = 'Enable / Disable';
$string['pulsenotification'] = 'Pulse notification';

$string['pulse_subject'] = 'Pulse from {Course_FullName} ({Site_FullName}) ';
$string['notifyusers'] = 'Notify course students';
$string['pulse:notifyuser'] = 'Send notification';
$string['messageprovider:mod_pulse'] = 'Send notifcation';
$string['instancename'] = 'Pulse';
$string['resendnotification'] = 'Re-send notification';
$string['resendnotificationdesc'] = 'Invitation has been scheduled for re-sending';
$string['completionrequireapproval'] = 'Require approval by one of the following roles ';
$string['completewhenavaialble'] = 'Completion when available';

$string['completionapproverules'] = 'Completionapproverules';
$string['completionself'] = 'Mark as complete by student to complete this activity';
$string['approved'] = 'Approved';
$string['declined'] = 'Declined';
$string['decline'] = 'Decline';
$string['approve'] = 'Approve';
$string['approveuser'] = 'Approve users - {$a->course}';
$string['approveuserbtn'] = 'Approve users';

$string['restrictionmet'] = 'Restriction met';

$string['markcomplete'] = 'Mark complete';
$string['noreaction'] = 'No Reaction';
$string['rate'] = 'Rate';
$string['updatecompletion'] = 'Update pulse modules completion';
$string['approvedsuccess'] = 'Approval successful';
$string['approvedeclined'] = 'Approval denied';
$string['selfmarked'] = 'Self marked complete on {$a->date}';
$string['approveduser'] = 'Approved by: {$a->user}';
$string['approvedon'] = 'Approved on {$a->date} by {$a->user}';

$string['completion:self'] = 'Mark complete';
$string['completion:approval'] = 'Approval required';
$string['completion:available'] = 'Restrictions must be met';

$string['reactions'] = 'Reaction';
$string['displaytype:notificationonly'] = 'Notification only';
$string['displaytype:contentonly'] = 'Content only';
$string['displaytype:notificationcontent'] = 'Both';

$string['head:firstreminder'] = 'First reminder';
$string['head:secondreminder'] = 'Second reminder';
$string['head:recurringreminder'] = 'Recurring reminder';
$string['remindersubject'] = 'Notification subject';
$string['remindercontent'] = 'Notification content';
$string['recipients'] = 'Notification recipients';
$string['reminderschedule'] = 'Notification schedule';
$string['schedule:fixeddate'] = 'Fixed date';
$string['schedule:relativedate'] = 'Relative date';
$string['courserole'] = 'courserole';
$string['userrole'] = 'userrole';
$string['reactiondisplaytype'] = 'Location';
$string['title'] = 'Title';
$string['reaction:markcomplete'] = ' <a href="{$a->reactionurl}" style="color: #fff;background: #0f6fc5;padding: .375rem .75rem;text-decoration-line: none;" >Mark Complete</a> ';
$string['reaction:rate'] = '';
$string['reaction:approve'] = ' <a href="{$a->reactionurl}" style="color: #fff;background: #0f6fc5;padding:.375rem .75rem;text-decoration-line: none;" > Approve </a> ';
$string['title_help'] = 'The title is used as activity name. It is used as subject to send the invitation.';
$string['content_help'] = 'Content will be displayed on the course page and used as message body content for the invitation.';
$string['completewhenavaialble_help'] = 'If enabled, the activity will be considered completed when the user has access to it (i.e. when it is available based on availability restricitions).';
$string['completionself_help'] = 'If enabled, the activity will be considered completed when the student marks it as complete on the course page.';
$string['completionrequireapproval_help'] = 'If enabled, the activity will be considered completed when any of the selected roles approves the user.';

$string['completereaction'] = 'Complete reaction';
$string['reminders:first'] = 'First reminder';
$string['reminders:second'] = 'Second reminder';
$string['reminders:recurring'] = 'Recurring reminder';
$string['reminders:availabletime'] = 'Availability time';

$string['completioncriteria'] = 'Completion criteria';
$string['self'] = 'Self';
$string['teacher'] = 'Teacher';
$string['reaction'] = 'Reaction';
$string['reports'] = 'Pulse Reports';
$string['selectpulse'] = 'Select pulse instance';
$string['generatereport'] = 'Generate report';
$string['like'] = 'Like';
$string['dislike'] = 'Dislike';
$string['enablereminder:invitation'] = 'Enable invitation';
$string['enablereminder:first'] = 'Enable first reminder';
$string['enablereminder:second'] = 'Enable second reminder';
$string['enablereminder:recurring'] = 'Enable recurring reminder';
$string['invitation'] = 'Invitation';
$string['invitationsubject'] = 'Notification subject';

$string['invitation_help'] = 'Send the invitation to all users with the selected roles.';
$string['sendnotificaton_help'] = 'If enabled, the invitation will be sent.';
$string['resendnotification_help'] = 'If enabled, Invitation reminder will rescheduled and sends the invitation to already notified users too.';
$string['diffnotification_help'] = 'If enabled, the invitation will use notification content and subject (instead of general content and title).';
$string['invitationsubject_help'] = 'Add the subject for the invitation here.';
$string['remindercontent_help'] = 'If notification content enabled, then this content will used as main content for invitations.';
$string['recipients_help'] = 'Please choose the roles which you want to send the notification to. Only users enrolled in this course and with the selected role will receive notifications. Please note that users with a user context role users don\'t need to be enrolled in the course. ';

$string['reactiontype'] = 'Type';
$string['reactiontype_help'] = 'List of reaction types.';
$string['reactiondisplaytype_help'] = 'Please choose where the reaction should be displayed.';
$string['enablereminder:first_help'] = 'If enabled, Pulse will send the first reminder.';
$string['remindersubject_help'] = 'Content will used as subject for the Reminder notifications.';
$string['remindercontent_help'] = 'The content you enter here will be sent to recipients.';

$string['reminderschedule_help'] = 'Define the reminder notification schedule type, <br>
                                    If fixed date is selected, the reminder is sent when the selected date is reached. <br>
                                    If relative date is selected, the reminder is sent after the selected time has passed. The timer starts when the Pulse activity becomes available to the user.';
$string['enablereminder:second_help'] = 'If enabled, Pulse will send a second reminder to selected recipients based on the schedule.';
$string['enablereminder:recurring_help'] = 'If enabled, Pulse will send a recurring reminder to selected recipients. Recurring reminders will send to the user in the given interval until the user is no longer enrolled or suspended.';

$string['notsameuser'] = 'You are not the correct user to apply reaction';
$string['previousreminders'] = 'Previous reminders';
$string['reportsfilename'] = 'Pulse reports - {$a->name}';
$string['viewreport'] = 'View report';
$string['pulsenotavailable'] = 'Pulse instance not added in course';
$string['notassignedgroup'] = 'User must be part of a group to filter by participants.';
$string['pulsepro:viewreports'] = 'View Pulse Pro reports';
$string['reactionthankmsg'] = 'Thank you! Your response is saved.<br><br> <span>You can now close this window</span>';

$string['privacy:completion'] = 'Completion';
$string['approvedby'] = 'approvedby';
$string['completionfor'] = 'completionfor';
$string['privacy:metadata:pulsecompletion'] = 'Pulse user activity completions';
$string['privacy:invitation'] = 'Inviation';
$string['privacy:metadata:completion:userid'] = 'ID of the user';
$string['privacy:metadata:completion:approvalstatus'] = 'User approved status';
$string['privacy:metadata:completion:approveduser'] = 'ID of the user who approved the student user';
$string['privacy:metadata:completion:approvaltime'] = 'Time when the user approved by other.';
$string['privacy:metadata:completion:selfcompletion'] = 'Status of the user completion by self';
$string['privacy:metadata:completion:selfcompletiontime'] = 'Time when the user marked the Pulse activity as complete';
$string['privacy:metadata:completion:timemodified'] = 'Time of completion modified';
$string['privacy:metadata:users:userid'] = 'ID of notified user';
$string['privacy:metadata:users:status'] = 'Status of the invitation to find the notification is previous or current one';
$string['privacy:metadata:users:timecreated'] = 'Time of the invitation send to user.';
$string['privacy:metadata:pulseusers'] = 'List of users invitation notified';
$string['privacy:metadata:pulsemessageexplanation'] = 'Invitations are sent to students through the messaging system.';

// Lang strings for the presets.
$string['presets'] = "Pulse presets";
$string['usepreset'] = 'Use preset';
$string['managepresets'] = 'Manage Presets';
$string['title'] = 'Title';
$string['description'] = 'Description';
$string['instruction'] = 'Instruction';
$string['preseticon'] = 'Preset icon';
$string['createpreset'] = 'Create Preset';
$string['statuslabel'] = 'Enable / Disable';
$string['preset_template'] = 'Backup file of pulse activity';
$string['preset_template_help'] = 'Upload the backup file of pulse module course activity';
$string['presetstatus'] = 'Display this preset in list';
$string['presetorder'] = 'Preset template order';
$string['configrableparams'] = 'Configurable Params';
$string['disabled'] = 'Disabled';
$string['enabled'] = 'Enabled';
$string['preset_template'] = 'Preset Template';
$string['title'] = 'Title';
$string['configparams'] = 'Config Params';
$string['presetlist'] = 'Presets List';
$string['presetcreated'] = 'Pulse presets created successfully';
$string['presetupdated'] = 'Pulse presets updated successfully';
$string['update_preset'] = 'Update Preset Template';
$string['deletepreset'] = 'Delete pulsepro preset template';
$string['confirmdeletetemplate'] = 'Are you sure! do you want to delete the preset';
$string['presetdeleted'] = 'Preset deleted successfully';
$string['apply_customize'] = 'Apply and Customize';
$string['apply_save'] = 'Apply and Save';
$string['promotionaltext'] = 'With Pulse Pro you get powerful reminders, in-email reactions and you can create your own presets. ';
$string['learnmore'] = 'Learn More';
$string['presetmodaltitle'] = 'Use preset {$a->title}';
// Credits lang strings.
$string['credits'] = 'Credits';
$string['notificationheader'] = 'Notification header';
$string['notificationheaderdesc'] = '{$a->placeholders}';
$string['notificationfooter'] = 'Notification footer';
$string['notificationfooterdesc'] = '{$a->placeholders}';
$string['creditsfield'] = 'Credits user profile field';
$string['creditsfielddesc'] = 'Select any of the user custom profile field to maintain the user credits records <br>
NOTE: Lock the selected field for students to prevent that students change their credit scores';
$string['setupcredit'] = 'In order to use this feature, you need to configure the user profile field first.
Please ask your local administrator to set this up.';
$string['creditesgroup'] = 'Credit score';
$string['setup'] = 'Setup Field';
$string['actions'] = 'Actions';
$string['cssclass'] = 'CSS class';
$string['normal'] = 'Normal';
$string['box'] = 'Box';
$string['displaymode'] = 'Display mode';
$string['primary'] = 'Primary';
$string['secondary'] = 'Secondary';
$string['danger'] = 'Danger';
$string['warning'] = 'Warning';
$string['light'] = 'Light';
$string['dark'] = 'Dark';
$string['success'] = 'Success';
$string['boxicon'] = 'Box Icon';
$string['boxtype'] = 'Box Type';
$string['updateusercredits'] = 'Update user credits';
$string['logintoreact'] = 'Login before apply reaction';
$string['tokenexpired'] = "Token expired! Your response was not saved.";
$string['detailedlog'] = 'Display detailed log for scheduled task — only use for troubleshooting purposes and disable on a production site';
$string['showhide'] = 'Detailed log';
$string['configintro'] = 'Global configuration settings for Pulse';
$string['tasklimituser'] = 'Limit users per task';
$string['tasklimituserdesc'] = 'Use this setting to limit how many users are processed per task. Lower the number of users if the task is taking too long to finish.';
$string['invitationdbpro'] = 'Invitation send to user not inserted. Please check pulsepro';
$string['invitationnotsend'] = 'Invitation not send to user';
$string['enrolmentemptystartdate'] = '-';
$string['enrolmentemptyenddate'] = '-';


/**
 * Strings for the pulse automation templates.
 */

// ...Template capabilities.
$string['pulse:addtemplate'] = 'Add a new automation template';
$string['pulse:viewtemplateslist'] = 'View the automation templates list';
$string['pulse:addtemplateinstance'] = 'Add a new template instance';
$string['mod/pulse:sender'] = 'Notification sender user';

// ...Templates list string.
$string['autotemplates'] = 'Automation templates';
$string['automation'] = 'Automation';
$string['autotemplates_desc'] = 'Automation templates define the kind of automations you can use on this moodle site. Depending on the configuration of an automation template, they either work as "default" and will be applied to new courses can be forced onto every course or can be used to create and automation instance from within a course.';
// ...create new template btn.
$string['templatecreatenew'] = 'Create new template';
// ...Edit templates page stirng.
$string['templatessettings'] = 'Edit template';
// ...Templates edit/create strings
$string['reference'] = 'Reference';
$string['show'] = 'Show';
$string['hidden'] = 'Hidden';
$string['visibility'] = 'Visibility';
$string['internalnotes'] = 'Internal notes';
$string['status'] = 'Status';
$string['tags'] = 'Tags';
$string['availablefortenants'] = 'Available for tenants';
$string['availableincoursecategories'] = 'Available in course categories';
$string['deleteinstance'] = 'Are you sure! do you want to delete the instance';
// ...Auto templates user notifications strings.
$string['templateupdatesuccess'] = 'Template updated successfully';
$string['templateinsertsuccess'] = 'Template inserted successfully';
$string['templatedeleted'] = 'Automation template instance deleted successfully.';
// ...Admin settings.
$string['generalsettings'] = 'General settings';
$string['conditiontrigger'] = 'Triggers';
$string['triggeroperator'] = 'Trigger operator';
$string['all'] = 'All';
$string['any'] = 'Any';
// ...Add template in instance.
$string['addtemplatebtn'] = 'Add automation instance';
$string['managetemplate'] = 'Manage templates';
$string['upcoming'] = 'Upcoming';
// ...Form tabs
$string['tabgeneral'] = 'General';
$string['tabcondition'] = 'Condition';
$string['autoinstances'] = 'Auto instances';
$string['editinstance'] = 'Edit instance';
// ...Update status modal
$string['templatestatusudpate'] = 'Are you sure that you want to change the status of the template?
<ul class="mt-3">
<li> Choose <b> Update Template </b> if you only want to update the status of the template, but leave the instances untouched </li>.
<li> Choose <b> Update Template & Instances</b> if you want to update the status of the template and also all of its instances. </li> </ul>';
$string['instancecopy'] = 'Duplicate Instance';

$string['instancereport'] = 'Instance automation schedules';
$string['automationreportname'] = 'Automation schedule instances';
$string['overrides'] = 'Overrides';
$string['updateinstance'] = 'Update Template & Instance';
$string['updatetemplate'] = 'Update Template';


$string['instancename'] = 'Instance Name';
$string['view'] = 'View';
// ... Instance override modal title.
$string['instanceoverrides'] = 'Overridden instances';

// Help Texts.
// Title.
$string['title_help'] = 'Enter a <b>Title</b> for this automation template. This title is for administrative purposes and helps identify the template.';
// Reference.
$string['reference_help'] = 'Provide a <b>Reference</b> for this automation template. This identifier is also for administrative purposes and helps uniquely identify the template.';
// Visibility.
$string['visibility_help'] = 'Choose whether you want this template to be visible or hidden.
<b>Note:</b> If hidden, users won\'t be able to create new instances based on this template, but existing instances will still be available.';
// Internal notes.
$string['internalnotes_help'] = 'Add any internal notes or information related to this automation template.';
// Status.
$string['status_help'] = 'Select the status for this automation template:<b>Enabled:</b> Allows instances of this template to be created. Enabling the template may also prompt the user to decide whether to enable all existing instances based on the template.<b>Disabled:</b> Turns off the automation template and its instances. Users can still enable instances individually if needed. Disabling the template may prompt the user to decide whether to disable all existing instances based on the template.';
// Tags.
$string['tags_help'] = 'Add <b>Tags</b> to this template for administrative purposes. Tags can help categorize and organize templates.';
// Available for tenants.
$string['availablefortenants_help'] = 'Specify for which Moodle Workplace <b>Tenants</b> this template should be available. Select one or more tenants to make the template accessible to specific groups.';
// Available in course categories.
$string['availableincoursecategories_help'] = 'Choose the <b>Course categories</b> where this template should be available. Select one or more categories to determine where users can create instances based on this template.';
// Trigger operator.
$string['triggeroperator_help'] = 'Choose the operator that determines how the selected triggers are evaluated:<br><b>Any</b>: At least one of the selected triggers must occur to activate the automation.<br><b>All</b>: All of the selected triggers must occur simultaneously to activate the automation.';
// Condition trigger.
$string['conditiontrigger_help'] = 'Choose the trigger events that will activate this automation. You can select one or more of the following trigger options:<br><b>Activity Completion</b>: This automation will be triggered when an activity within the course is marked as completed. You will need to specify the activity within the automation instance.<br><b>Course Completion</b>: This automation will be triggered when the entire course is marked as completed, where this instance is used.<br><b>Enrolments</b>: This automation will be triggered when a user is enrolled in the course where this instance is located.<br><b>Session</b>: This automation will be triggered when a session is booked within the course. This trigger is only available within the course and should be selected within the automation instance.<br><b>Cohort Membership</b>: This automation will be triggered if the user is a member of one of the selected cohorts.';

// Number of schedules notification.
$string['schedulecount'] = 'Number of schedule count';
$string['schedulecountdesc'] = 'This setting allows you to control how many scheduled task notifications are sent during each cron run. By specifying a numerical value, you can regulate the rate at which system administrators receive notifications regarding the completion or status of scheduled tasks.';

// ...Automation templates table instruction help texts.
$string['instruction'] = 'Instructions';
$string['automationtemplate_help1'] = 'The icon represents the enabled action(s) in the automation (template). the following actions are available: "Notification", "Assignment". "Membership", "Skills",';
$string['automationtemplate_help2'] = 'The title of the automation template. this should be a generic explanation about what the template is for. it can be changed inline by clicking on the pencil icon.';
$string['automationtemplate_help3'] = 'The pills provide additional improtant information about the automation template. In this case, it explains that it\'s a notification.';
$string['automationtemplate_help4'] = 'This is the reference of the template, and is providing a unique identifier for the template. it will be part of the unique identifier of the automation instance.';
$string['automationtemplate_help5'] = 'Click on this icon to edit the template.';
$string['automationtemplate_help6'] = 'Click on this icon to toggle the visibility of a template. A template which is not visible is hidden in courses. Existing automation instances will still be available, but new ones cannot be added anymore.';
$string['automationtemplate_help7'] = 'Use this toggle to turn on or off a template. A template which is turned off also disabled all automation instances, unless they are locally enabled using an override';
$string['automationtemplate_help8'] = 'How manu automation instances are using template. The number is brackets indicates the numder of disabled instances.';
