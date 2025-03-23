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

$string['Assignment_vars'] = 'Assignments';
$string['Assignment_vars_help'] = 'Email placeholder for the assignment extension';
$string['Calendar_vars'] = 'Calendar';
$string['Calendar_vars_help'] = 'Calendar information placeholders';
$string['Course_vars'] = 'Course Information';
$string['Course_vars_help'] = 'Course Information placeholders';
$string['Enrolment_vars'] = 'Enrolments & Completion';
$string['Enrolment_vars_help'] = 'Enrolments & Completion information placeholders';
$string['Event_vars'] = 'Logs';
$string['Event_vars_help'] = 'Events conditions vars';
$string['Mod_Metadata_vars'] = 'Metedata';
$string['Mod_Metadata_vars_help'] = 'Metadata information vars';
$string['Mod_session_vars'] = 'Face to Face';
$string['Mod_session_vars_help'] = 'Face to Face module placeholders';
$string['Mod_vars'] = 'Course activities';
$string['Mod_vars_help'] = 'Course activities information placeholders';
$string['Reaction_vars'] = 'Reaction';
$string['Reaction_vars_help'] = 'pulse reaction vars';
$string['Sender_vars'] = 'Sender';
$string['Sender_vars_help'] = 'Sender information placeholders';
$string['Site_vars'] = 'Site';
$string['Site_vars_help'] = 'Site information placeholders';
$string['Training_vars'] = 'Training';
$string['Training_vars_help'] = 'Training information vars';
$string['User_vars'] = 'User Profile';
$string['User_vars_help'] = 'User Profile placeholders';
$string['actions'] = 'Actions';
$string['addinstance'] = 'Add Instance';
$string['addtemplatebtn'] = 'Add automation instance';
$string['all'] = 'All';
$string['any'] = 'Any';
$string['apply_customize'] = 'Apply and Customize';
$string['apply_save'] = 'Apply and Save';
$string['approve'] = 'Approve';
$string['approved'] = 'Approved';
$string['approvedby'] = 'approvedby';
$string['approvedeclined'] = 'Approval denied';
$string['approvedon'] = 'Approved on {$a->date} by {$a->user}';
$string['approvedsuccess'] = 'Approval successful';
$string['approveduser'] = 'Approved by: {$a->user}';
$string['approveuser'] = 'Approve users - {$a->course}';
$string['approveuserbtn'] = 'Approve users';
$string['autoinstance_desc'] = 'Users with the requisite permissions can generate automation instances by selecting an existing template. Within each automation instance, the initial values for settings are inherited from the template. However, should a user desire to deviate from the template\'s values, they have the option to locally override them by activating the "override" toggle and implementing local adjustments to the settings.';
$string['autoinstances'] = 'Auto instances';
$string['automation'] = 'Automation';
$string['automation_reference'] = 'TEMPLATE_REFERENCE';
$string['automation_title'] = '<b> Template title  </b>';
$string['automationinstance_help1'] = 'The icon represents the enabled action(s) in the automation (template). the following actions are available: "Notification", "Assignment". "Membership", "Skills",';
$string['automationinstance_help2'] = 'The title of the automation <b>instance</b>. This should be a generic explanation about what the template is for. It can be changed inline by clicking on the pencil icon.';
$string['automationinstance_help3'] = 'The pills provide additional improtant information about the automation instance. In this case, it explains that it\'s a notification.';
$string['automationinstance_help4'] = 'This is the reference of the instance, and acts as a unique identifier. It is (Usually) a combination of the template\'s reference combined with the course.';
$string['automationinstance_help5'] = 'Click on this icon to edit the automation.';
$string['automationinstance_help6'] = 'Duplicate the automation';
$string['automationinstance_help7'] = 'Open the automation queue (in the report builder), which provides information about all the automations that ran.';
$string['automationinstance_help8'] = 'Use this toggle to turn on or off the automation instance locally. This will override the status of the template, i.e. even if the template is turned off, it can be enabled.';
$string['automationinstance_help9'] = 'Click on this icon to delete the automation instance.';
$string['automationinstance_reference'] = 'INSTANCE_REFERENCE';
$string['automationinstance_title'] = ' <b> Instance title  </b>';
$string['automationreferencedemo'] = 'WELCOME_MESSAGE';
$string['automationreportname'] = 'Automation schedule instances';
$string['automationtemplate_help1'] = 'The icon represents the enabled action(s) in the automation (template). the following actions are available: "Notification", "Assignment". "Membership", "Skills",';
$string['automationtemplate_help2'] = 'The title of the automation <b>template</b>. This should be a generic explanation about what the template is for. It can be changed inline by clicking on the pencil icon.';
$string['automationtemplate_help3'] = 'The pills provide additional improtant information about the automation template. In this case, it explains that it\'s a notification.';
$string['automationtemplate_help4'] = 'This is the reference of the template, and is providing a unique identifier for the template. it will be part of the unique identifier of the automation instance.';
$string['automationtemplate_help5'] = 'Click on this icon to edit the template.';
$string['automationtemplate_help6'] = 'Click on this icon to toggle the visibility of a template. A template which is not visible is hidden in courses. Existing automation instances will still be available, but new ones cannot be added anymore.';
$string['automationtemplate_help7'] = 'Use this toggle to turn on or off a template. A template which is turned off also disabled all automation instances, unless they are locally enabled using an override';
$string['automationtemplate_help8'] = 'How many automation instances are using the template? The number in brackets indicates the number of disabled instances.';
$string['autotemplates'] = 'Automation templates';
$string['autotemplates_desc'] = 'Users with the appropriate permissions create automation templates globally, independent of specific courses. The template itself doesn\'t perform any actions, it serves as the foundation for creating the instances.';
$string['availablefortenants'] = 'Available for tenants';
$string['availablefortenants_help'] = 'Specify for which Moodle Workplace <b>Tenants</b> this template should be available. Select one or more tenants to make the template accessible to specific groups.';
$string['availableincoursecategories'] = 'Available in course categories';
$string['availableincoursecategories_help'] = 'Choose the <b>Course categories</b> where this template should be available. Select one or more categories to determine where users can create instances based on this template.';
$string['box'] = 'Box';
$string['boxicon'] = 'Box Icon';
$string['boxtype'] = 'Box Type';
$string['btntext'] = 'Button text';
$string['btntext_help'] = 'Choose the text that appears on the button users click to mark an activity as complete.';
$string['bulkaction:addinstance'] = 'Add instances ';
$string['bulkaction:deleteinstance'] = 'Delete instances ';
$string['bulkaction:disableinstance'] = 'Disable instances ';
$string['bulkaction:enableinstance'] = 'Enable instances ';
$string['completereaction'] = 'Complete reaction';
$string['completewhenavaialble'] = 'Completion when available';
$string['completewhenavaialble_help'] = 'If enabled, the activity will be considered completed when the user has access to it (i.e. when it is available based on availability restricitions).';
$string['completion:approval'] = 'Approval required';
$string['completion:available'] = 'Restrictions must be met';
$string['completion:self'] = 'Mark complete';
$string['completionapproverules'] = 'Completionapproverules';
$string['completionconfirmation'] = 'Are you sure you want to mark this activity as complete?';
$string['completioncriteria'] = 'Completion criteria';
$string['completionenrolled'] = 'enrolled';
$string['completionfor'] = 'completionfor';
$string['completionmessage'] = 'Marked as completed';
$string['completionrequireapproval'] = 'Require approval by one of the following roles ';
$string['completionrequireapproval_help'] = 'If enabled, the activity will be considered completed when any of the selected roles approves the user.';
$string['completionself'] = 'Mark as complete by student to complete this activity';
$string['completionself_help'] = 'If enabled, the activity will be considered completed when the student marks it as complete on the course page.';
$string['conditiontrigger'] = 'Triggers';
$string['conditiontrigger_help'] = 'Choose the trigger events that will activate this automation. You can select one or more of the following trigger options:<br><b>Activity Completion</b>: This automation will be triggered when an activity within the course is marked as completed. You will need to specify the activity within the automation instance.<br><b>Course Completion</b>: This automation will be triggered when the entire course is marked as completed, where this instance is used.<br><b>Enrolments</b>: This automation will be triggered when a user is enrolled in the course where this instance is located.<br><b>Session</b>: This automation will be triggered when a session is booked within the course. This trigger is only available within the course and should be selected within the automation instance.<br><b>Cohort Membership</b>: This automation will be triggered if the user is a member of one of the selected cohorts.';
$string['configintro'] = 'Global configuration settings for Pulse';
$string['configparams'] = 'Config Params';
$string['configrableparams'] = 'Configurable Params';
$string['confirmaddinstance'] = 'Are you sure! do you want to add a instances for this selected courses.';
$string['confirmation'] = 'Confirmation';
$string['confirmdeleteinstance'] = 'Are you sure! do you want to delete all instance for this selected courses.';
$string['confirmdeletepreset'] = 'Are you sure! do you want to delete the selected preset.';
$string['confirmdeletetemplate'] = 'Are you sure! do you want to delete the automation template';
$string['confirmdisableinstance'] = 'Are you sure! do you want to disable the instances for this selected courses.';
$string['confirmenableinstance'] = 'Are you sure! do you want to enable the instances for this selected courses.';
$string['confirmtext'] = 'Confirmation modal text';
$string['confirmtext_help'] = 'Enter the content that will appear in the confirmation modal.';
$string['content'] = 'Content';
$string['content_help'] = 'Content will be displayed on the course page and used as message body content for the invitation.';
$string['coursecategory'] = 'Course category';
$string['courseinsreport'] = 'Instances for this template';
$string['coursename'] = 'Course name';
$string['courserole'] = 'courserole';
$string['createpreset'] = 'Create Preset';
$string['creditesgroup'] = 'Credit score';
$string['credits'] = 'Credits';
$string['creditsfield'] = 'Credits user profile field';
$string['creditsfielddesc'] = 'Select any of the user custom profile field to maintain the user credits records <br>
    NOTE: Lock the selected field for students to prevent that students change their credit scores';
$string['cssclass'] = 'CSS class';
$string['danger'] = 'Danger';
$string['dark'] = 'Dark';
$string['decline'] = 'Decline';
$string['declined'] = 'Declined';
$string['deleteinstance'] = 'Are you sure! do you want to delete the instance';
$string['deletepreset'] = 'Delete preset template';
$string['description'] = 'Description';
$string['deselectall'] = 'De-select all';
$string['detailedlog'] = 'Display detailed log for scheduled task — only use for troubleshooting purposes and disable on a production site';
$string['diffnotification'] = 'Use notification content instead of general content';
$string['diffnotification_help'] = 'If enabled, the invitation will use notification content and subject (instead of general content and title).';
$string['disabled'] = 'Disabled';
$string['dislike'] = 'Dislike';
$string['displaymode'] = 'Display mode';
$string['displaytype:contentonly'] = 'Content only';
$string['displaytype:notificationcontent'] = 'Both';
$string['displaytype:notificationonly'] = 'Notification only';
$string['documentation'] = 'documentation';
$string['editinstance'] = 'Edit instance';
$string['edittemplatetitle'] = 'Edit template title';
$string['enable:disable'] = 'Enable / Disable';
$string['enabled'] = 'Enabled';
$string['enablereminder:first'] = 'Enable first reminder';
$string['enablereminder:first_help'] = 'If enabled, Pulse will send the first reminder.';
$string['enablereminder:invitation'] = 'Enable invitation';
$string['enablereminder:recurring'] = 'Enable recurring reminder';
$string['enablereminder:recurring_help'] = 'If enabled, Pulse will send a recurring reminder to selected recipients. Recurring reminders will send to the user in the given interval until the user is no longer  enrolled or suspended.';
$string['enablereminder:second'] = 'Enable second reminder';
$string['enablereminder:second_help'] = 'If enabled, Pulse will send a second reminder to selected recipients based on the schedule.';
$string['enrolmentemptyenddate'] = '-';
$string['enrolmentemptystartdate'] = '-';
$string['errortemplatenotavailable'] = 'This automation template is not available for this course';
$string['expiretime'] = 'Expiration time for the token';
$string['expiretimedesc'] = 'Enter the expiration time for the token in minutes.';
$string['filterlang'] = 'Filter';
$string['frequencylimit'] = 'Frequency limit';
$string['frequencylimit_help'] = 'Enter the frequency limit for the addon. 0 for unlimited.';
$string['generalsettings'] = 'General settings';
$string['generatereport'] = 'Generate report';
$string['head:firstreminder'] = 'First reminder';
$string['head:recurringreminder'] = 'Recurring reminder';
$string['head:secondreminder'] = 'Second reminder';
$string['hidden'] = 'Hidden';
$string['hideshow'] = 'Show / Hide';
$string['includepulseautomationinfo'] = 'Include pulse automation';
$string['instancecopy'] = 'Duplicate Instance';
$string['instancedataempty'] = 'Instance data empty';
$string['instancename'] = 'Instance Name';
$string['instanceoverrides'] = 'Overridden instances';
$string['instancereport'] = 'Instance automation schedules';
$string['instanceslist'] = 'Course Instances List';
$string['instruction'] = 'Instructions';
$string['internalnotes'] = 'Internal notes';
$string['internalnotes_help'] = 'Add any internal notes or information related to this automation template.';
$string['invitation'] = 'Invitation';
$string['invitation_help'] = 'Send the invitation to all users with the selected roles.';
$string['invitationdbpro'] = 'Invitation send to user not inserted. Please check pulse reminder addon';
$string['invitationnotsend'] = 'Invitation not send to user';
$string['invitationsubject'] = 'Notification subject';
$string['invitationsubject_help'] = 'Add the subject for the invitation here.';
$string['learnmore'] = 'Learn More';
$string['light'] = 'Light';
$string['like'] = 'Like';
$string['logintocourse'] = 'Login and Go to course';
$string['logintoreact'] = 'Login before apply reaction';
$string['manageinstance'] = 'Manage Instance';
$string['managepresets'] = 'Manage Presets';
$string['managepulseaddonplugins'] = 'Manage Pulse addons';
$string['managetemplate'] = 'Manage templates';
$string['markcomplete'] = 'Mark complete';
$string['markcompletebtnstring_custom1'] = 'Acknowledge';
$string['markcompletebtnstring_custom2'] = 'Confirm';
$string['markcompletebtnstring_custom3'] = 'Choose';
$string['markcompletebtnstring_custom4'] = 'Approve';
$string['markcompletebtnstring_default'] = 'Mark as complete';
$string['markcompleteoptionheader'] = 'Mark as complete options';
$string['markedcomplete'] = 'Marked as completed';
$string['markedcompletebtnstring_custom1'] = 'Completion acknowledged on {$a->date}';
$string['markedcompletebtnstring_custom2'] = 'Completion confirmed on {$a->date}';
$string['markedcompletebtnstring_custom3'] = 'Completion chosen on {$a->date}';
$string['markedcompletebtnstring_custom4'] = 'Completion approved on {$a->date}';
$string['markedcompletebtnstring_default'] = 'Self marked complete on {$a->date}';
$string['messageprovider:mod_pulse'] = 'Send notifcation';
$string['mixed'] = 'Mixed';
$string['modalconfirm'] = 'Completion Confirmation';
$string['modulename'] = 'Pulse';
$string['modulename_help'] = 'Pulse is the teacher\'s Swiss army knife to improve student engagement and compliance in moodle courses:
    <ul><li><strong>Notifications</strong><br>
    Each Pulse activity can be configured to send a notification once it becomes available to the student. There are a number of placeholders that can be used to personalize the message, like the first name of the student or the name of the course. The notification can be sent to the student, the teacher, the non-editing teacher or the manager. Other course context or user context roles are supported as well, e.g. parent or staff manager.</li>
    <li><strong>Completion workflows</strong><br>
    Pulse supports activity completion in three ways (in addition to moodle core ones): upon availability, when marked complete by student and when approved by other role.</li></ul>
    <p>As with all things automation, it is essential to put enough thought into what you actually want. Ideally, write down what should happen in which case. This also helps us to support you! If it does not work as you expect, here are the most common issues:</p>
    <ul>
    <li>The course has not <b>started</b>. Pulse only works in a course which is currently running —> Check if the start date is in the past.</li>
    <li>The course has <b>ended</b>. Pulse only works in a course which is currently running —> Check if the end date is set, and if it is, make sure it is in the future.</li>
    <li>The course has <b>enrolled students</b>. Pulse (free) only works for students —> Check if the course actually has an enrolled user with an active enrolment status.</li>
    <li>The Pulse activity is <b>available</b>. Pulse only works if the activity is available to the student. That is the "trigger" or "condition" we use to determine if we shall actually do something (e.g. award credits). —> Check if the Pulse activity is hidden or has a restriction which is not met by the student in both cases, it is "disabled" and will not work. If you are not sure, just login as the student and check if the student sees the Pulse activity — if the student sees it, it is enabled if the student does not see it, it is disabled.</li>
    <li>The <b>cron job</b> is not running or has not run yet — awarding credits is done through a scheduled task. It is therefor required that the cron job is running regularly. Moodle recommends every minute, and we can only encourage you to follow that recommendation!</li>
    </ul>';
$string['modulename_link'] = 'Pulse';
$string['modulenameplural'] = 'Pulses';
$string['months'] = 'months';
$string['newvaluefor'] = 'New value for {$a}';
$string['noextensiongranted'] = 'No extensions have been granted for upcoming assignments.';
$string['noreaction'] = 'No Reaction';
$string['normal'] = 'Normal';
$string['notassignedgroup'] = 'User must be part of a group to filter by participants.';
$string['notificationfooter'] = 'Notification footer';
$string['notificationfooterdesc'] = '{$a->placeholders}';
$string['notificationheader'] = 'Notification header';
$string['notificationheaderdesc'] = '{$a->placeholders}';
$string['notifyusers'] = 'Notify course students';
$string['notsameuser'] = 'You are not the correct user to apply reaction';
$string['numberofinstance'] = 'Number of instance';
$string['numberofoverrides'] = 'Number of overrides';
$string['others_vars'] = 'Others';
$string['others_vars_help'] = 'Additional placeholders';
$string['overrides'] = 'Overrides';
$string['placeholder'] = 'Placeholders';
$string['pluginadministration'] = 'Pulse administration';
$string['pluginname'] = 'Pulse';
$string['preset_template'] = 'Preset Template';
$string['preset_template_help'] = 'Upload the backup file of pulse module course activity';
$string['presetcreated'] = 'Pulse presets created successfully';
$string['presetdeleted'] = 'Preset deleted successfully';
$string['preseticon'] = 'Preset icon';
$string['presetlist'] = 'Presets List';
$string['presetmodaltitle'] = 'Use preset {$a->title}';
$string['presetorder'] = 'Preset template order';
$string['presets'] = 'Pulse presets';
$string['presetstatus'] = 'Display this preset in list';
$string['presetupdated'] = 'Pulse presets updated successfully';
$string['previously'] = 'Previously';
$string['previousreminders'] = 'Previous reminders';
$string['primary'] = 'Primary';
$string['privacy:completion'] = 'Completion';
$string['privacy:invitation'] = 'Inviation';
$string['privacy:metadata'] = 'The Pulse plugin does not store any personal data.';
$string['privacy:metadata:completion:approvalstatus'] = 'User approved status';
$string['privacy:metadata:completion:approvaltime'] = 'Time when the user approved by other.';
$string['privacy:metadata:completion:approveduser'] = 'ID of the user who approved the student user';
$string['privacy:metadata:completion:selfcompletion'] = 'Status of the user completion by self';
$string['privacy:metadata:completion:selfcompletiontime'] = 'Time when the user marked the Pulse activity as complete';
$string['privacy:metadata:completion:timemodified'] = 'Time of completion modified';
$string['privacy:metadata:completion:userid'] = 'ID of the user';
$string['privacy:metadata:pulsecompletion'] = 'Pulse user activity completions';
$string['privacy:metadata:pulsemessageexplanation'] = 'Invitations are sent to students through the messaging system.';
$string['privacy:metadata:pulseusers'] = 'List of users invitation notified';
$string['privacy:metadata:users:status'] = 'Status of the invitation to find the notification is previous or current one';
$string['privacy:metadata:users:timecreated'] = 'Time of the invitation send to user.';
$string['privacy:metadata:users:userid'] = 'ID of notified user';
$string['promotionaltext'] = 'With Pulse Pro you get powerful reminders, in-email reactions and you can create your own presets. ';
$string['pulse:addinstance'] = 'Add a new Pulse';
$string['pulse:addtemplate'] = 'Add a new automation template';
$string['pulse:addtemplateinstance'] = 'Add a new template instance';
$string['pulse:manageinstance'] = 'Access to the instance management';
$string['pulse:notifyuser'] = 'Send notification';
$string['pulse:overridetemplateinstance'] = 'Override the automation instance';
$string['pulse:sender'] = 'Notification sender user';
$string['pulse:view'] = 'View Pulse';
$string['pulse:viewtemplateslist'] = 'View the automation templates list';
$string['pulse_subject'] = 'Pulse from {Course_FullName} ({Site_FullName}) ';
$string['pulseaddonpluginname'] = 'Pulse addon plugin name';
$string['pulsenotavailable'] = 'Pulse instance not added in course';
$string['pulsenotification'] = 'Pulse notification';
$string['pulsetemplink'] = 'Pulse Automation Template';
$string['rate'] = 'Rate';
$string['reaction'] = 'Reaction';
$string['reaction:approve'] = ' <a href="{$a->reactionurl}" style="color: #fff;background: #0f6fc5;padding:.375rem .75rem;text-decoration-line: none;"> Approve </a> ';
$string['reaction:markcomplete'] = ' <a href="{$a->reactionurl}" style="color: #fff;background: #0f6fc5;padding: .375rem .75rem;text-decoration-line: none;">Mark Complete</a> ';
$string['reaction:rate'] = '';
$string['reactiondisplaytype'] = 'Location';
$string['reactiondisplaytype_help'] = 'Please choose where the reaction should be displayed.';
$string['reactions'] = 'Reaction';
$string['reactionthankmsg'] = 'Thank you! Your response is saved.<br><br> <span>You can now close this window</span>';
$string['reactiontype'] = 'Type';
$string['reactiontype_help'] = 'List of reaction types.';
$string['recipients'] = 'Notification recipients';
$string['recipients_help'] = 'Please choose the roles which you want to send the notification to. Only users enrolled in this course and with the selected role will receive notifications. Please note that users with a user context role users don\'t need to be enrolled in the course. ';
$string['reference'] = 'Reference';
$string['reference_help'] = 'Provide a <b>Reference</b> for this automation template. This identifier is also for administrative purposes and helps uniquely identify the template.';
$string['remindercontent'] = 'Notification content';
$string['remindercontent_help'] = 'The content you enter here will be sent to recipients.';
$string['reminders:availabletime'] = 'Availability time';
$string['reminders:first'] = 'First reminder';
$string['reminders:recurring'] = 'Recurring reminder';
$string['reminders:second'] = 'Second reminder';
$string['reminderschedule'] = 'Notification schedule';
$string['reminderschedule_help'] = 'Define the reminder notification schedule type, <br>
                                If fixed date is selected, the reminder is sent when the selected date is reached. <br>
                                If relative date is selected, the reminder is sent after the selected time has passed. The timer starts when the Pulse activity becomes available to the user.';
$string['remindersubject'] = 'Notification subject';
$string['remindersubject_help'] = 'Content will used as subject for the Reminder notifications.';
$string['report:viewreports'] = 'View Pulse reports';
$string['reports'] = 'Pulse Reports';
$string['reportsfilename'] = 'Pulse reports - {$a->name}';
$string['requireconfirm'] = 'Require confirmation';
$string['requireconfirm_help'] = '<b>Unchecked (Default):</b> No confirmation is required. Clicking the button will immediately mark the activity as complete.<br><b>Checked:</b> A confirmation modal will open, and the user must confirm or cancel the action before the activity is marked as complete.';
$string['resendnotification'] = 'Re-send notification';
$string['resendnotification_help'] = 'If enabled, Invitation reminder will rescheduled and sends the invitation to already notified users too.';
$string['resendnotificationdesc'] = 'Invitation has been scheduled for re-sending';
$string['restrictionmet'] = 'Restriction met';
$string['schedule:fixeddate'] = 'Fixed date';
$string['schedule:relativedate'] = 'Relative date';
$string['schedulecount'] = 'Number of schedule count';
$string['schedulecountdesc'] = 'This setting allows you to control how many scheduled task notifications are sent during each cron run. By specifying a numerical value, you can regulate the rate at which system administrators receive notifications regarding the completion or status of scheduled tasks.';
$string['search:activity'] = 'Pulse';
$string['secondary'] = 'Secondary';
$string['selectall'] = 'Select all';
$string['selectpulse'] = 'Select pulse instance';
$string['selectwithoutins'] = 'Select all without instances';
$string['self'] = 'Self';
$string['selfmarked'] = 'Self marked complete on {$a->date}';
$string['sendnotificaton'] = 'Send Pulse notification';
$string['sendnotificaton_help'] = 'If enabled, the invitation will be sent.';
$string['setup'] = 'Setup Field';
$string['setupcredit'] = 'In order to use this feature, you need to configure the user profile field first.
Please ask your local administrator to set this up.';
$string['show'] = 'Show';
$string['showhide'] = 'Detailed log';
$string['showless'] = 'Show less';
$string['showmore'] = 'Show more';
$string['status'] = 'Status';
$string['status_help'] = 'Select the status for this automation template:<b>Enabled:</b> Allows instances of this template to be created. Enabling the template may also prompt the user to decide whether to enable all existing instances based on the template.<b>Disabled:</b> Turns off the automation template and its instances. Users can still enable instances individually if needed. Disabling the template may prompt the user to decide whether to disable all existing instances based on the template.';
$string['statuslabel'] = 'Enable / Disable';
$string['subplugintype_pulseaction'] = 'Pulse action';
$string['subplugintype_pulseaction_plural'] = 'Pulse automation actions';
$string['subplugintype_pulseaddon'] = 'Pulse addon';
$string['subplugintype_pulseaddon_plural'] = 'Pulse addons';
$string['subplugintype_pulsecondition'] = 'Pulse automation condition';
$string['subplugintype_pulsecondition_plural'] = 'Pulse automation conditions';
$string['success'] = 'Success';
$string['suspended'] = 'Suspended';
$string['tabcondition'] = 'Condition';
$string['tabgeneral'] = 'General';
$string['tabmanageinstance'] = 'Instance Management';
$string['tags'] = 'Tags';
$string['tags_help'] = 'Add <b>Tags</b> to this template for administrative purposes. Tags can help categorize and organize templates.';
$string['tasklimituser'] = 'Limit users per task';
$string['tasklimituserdesc'] = 'Use this setting to limit how many users are processed per task. Lower the number of users if the task is taking too long to finish.';
$string['teacher'] = 'Teacher';
$string['templatecreatenew'] = 'Create new template';
$string['templatedeleted'] = 'Automation template instance deleted successfully.';
$string['templatedisablesuccess'] = 'Automation template instances disabled successfully';
$string['templateenablesuccess'] = 'Automation template instances enabled successfully';
$string['templateinsertsuccess'] = 'Template inserted successfully';
$string['templateorphaned'] = 'Orphaned';
$string['templatesorphanederror'] = 'Instance is orphaned';
$string['templatessettings'] = 'Edit template';
$string['templatestatusudpate'] = 'Are you sure that you want to change the status of the template?
<ul class="mt-3">
<li> Choose <b> Update Template </b> if you only want to update the status of the template, but leave the instances untouched </li>.
<li> Choose <b> Update Template & Instances</b> if you want to update the status of the template and also all of its instances. </li> </ul>';
$string['templateupdatesuccess'] = 'Template updated successfully';
$string['title'] = 'Title';
$string['title_help'] = 'Enter a <b>Title</b> for this automation template. This title is for administrative purposes and helps identify the template.';
$string['tokenexpired'] = 'Token expired! Your response was not saved.';
$string['triggeroperator'] = 'Trigger operator';
$string['triggeroperator_help'] = 'Choose the operator that determines how the selected triggers are evaluated:<br><b>Any</b>: At least one of the selected triggers must occur to activate the automation.<br><b>All</b>: All of the selected triggers must occur simultaneously to activate the automation.';
$string['unset'] = 'Unset';
$string['upcoming'] = 'Upcoming';
$string['update_preset'] = 'Update Preset Template';
$string['updatecompletion'] = 'Update pulse modules completion';
$string['updateinstance'] = 'Update Template & Instance';
$string['updatetemplate'] = 'Update Template';
$string['updateusercredits'] = 'Update user credits';
$string['usepreset'] = 'Use preset';
$string['userrole'] = 'userrole';
$string['vardocstr'] = 'The full list of available placeholders can be found in the ';
$string['view'] = 'View';
$string['viewreport'] = 'View report';
$string['visibility'] = 'Visibility';
$string['visibility_help'] = 'Choose whether you want this template to be visible or hidden.
<b>Note:</b> If hidden, users won\'t be able to create new instances based on this template, but existing instances will still be available.';
$string['warning'] = 'Warning';
$string['withselect'] = 'With selection:';

