# Pulse

Pulse is an activity plugin for Moodle that enhances student engagement and compliance by sending notifications to students when modules become available to them. One of the key features of Pulse is its support for availability restrict access methods. With Pulse, course creators can easily notify students when they have completed other modules and are now eligible to access new content. Additionally, Pulse can be used to send course welcome messages.

Pulse also has its own completion criteria, such as approval from specified course roles, marking it as complete by the user, and completion upon availability. Pulse helps with creation and setup using presets, enabling course creators to easily import configurations from preset files.

# Pulse PRO

PulsePro is an advanced version of the mod_pulse plugin and a Moodle general-type plugin. It extends the main feature of Pulse by providing options to create unlimited presets, customizes existing presets, and more. Another key feature of PulsePro is reactions, which allows teachers to approve any user without accessing the LMS. Students can also mark their Pulse completion and share their opinions about the course/pulse using reactions.

With PulsePro reports, administrators can view user reactions, notification reports for each user, approval status, and other relevant data. PulsePro also supports credits methods, which means that students will receive credits to their accounts when the Pulse becomes available to them. Course creators can use Pulse to give credits for completion of other modules using Pulse with availability conditions.

By understanding how Pulse sends notifications, you can optimize the plugin's settings to best meet your course's needs. Pulse and PulsePro are powerful tools that can significantly enhance student engagement and compliance in Moodle courses.

# Installation and Initial Setup.

You can install the Pulse plugin using the Moodle plugin installer. Here are the steps to follow:

1. Download the "**Pulse**" plugin from the Moodle plugins repository or from the Pulse website.
2. Log in to your Moodle site as an administrator.
3. Go to "*`Site administration > Plugins > Install plugins`*".
4. Upload the downloaded plugin ZIP file.
5. Follow the prompts to install the plugin.
6. Once the Pulse plugin is installed, you can configure it by going to Site Administration > Plugins > Activity Modules > Pulse.
From there, you can set up the credit system, enable notifications, and customize the Pulse modules.

Alternatively, you can also install the Pulse plugin manually. Here are the steps to follow:

1. Download the "**Pulse**" plugin from the Moodle plugins repository
2. Unzip the downloaded file.
3. Upload the pulse folder to the moodle/mod directory on your Moodle server.
4. Log in to your Moodle site as an administrator.
5. Go to "*`Site administration > Notifications`*".
6. Follow the prompts to install the plugin.


# Global Configuration Options.

Pulse Pro comes with global configurations

1. **Limit Users Per Task**

The "Limit Users Per Task" setting can be used to limit the number of users processed per task. If you find that the task is taking too long to complete, you can lower the number of users to be processed at once.

2. **Expire Time**

The "Expire Time" setting allows you to specify the time limit within which users can make reactions using the tokenized URL. After the specified time has passed, the link will expire and users will no longer be able to make reactions.

3. **Credits User Profile Field**

You can select any custom profile field for maintaining user credits records. Please ensure that the selected field is locked for students to prevent them from changing their credit scores.

4. **Notification Header and Footer**

The "Notification Header" and "Notification Footer" settings allow you to add a custom header and footer to all invitation and reminder notifications sent by Pulse Pro. These settings are used as templates and added to the head and footer of each notification. You can customize the content of these settings to include relevant information, such as your institution name, course name, or other relevant details.

It's important to note that these settings are global and will apply to all Pulse Pro activities in your Moodle instance. You can access these settings by navigating to the Pulse Pro settings page in your Moodle administration panel.

# Invitation

The Pulse plugin runs in the background using Moodle's scheduled and ad hoc tasks. Notifications are processed and sent to users on each cron run using the "*`mod_pulse\task\notify_users`*" task. If you need to modify the interval time of the Pulse schedule task, please refer to the Moodle task log.

The "notify_users" task fetches available Pulse module instances created in Moodle courses, and then retrieves the available students (users with the capability "mod/pulse:notifyuser") for each Pulse instance. The fetched users are filtered by the availability (Restrict access) of that Pulse.

For each pulse instance, a new ad-hoc task will be set to send notifications.

Once the students to notify are identified, Pulse finds the correct sender user, who is the user with the "*`mod/pulse:sender`*" capability. When an instance has more than one sender, Pulse will use the first user as the sender. If the course is in group mode, Pulse will use the sender from the group.

## Configuring Pulse Invitations

Pulse provides several configurations to customize and prepare invitations for course participants.

1. **Enable/Disable Invitation Sending:**
Pulse offers the option to enable or disable sending invitations to course participants.

2. **Resend Notifications:**
If needed, you can resend notifications to all course participants using the "Resend Notification" configuration. Even if they have already received the invitation, it will be resent to them.

3. **Use Notification Content:**
When enabled, invitations will use the notification content and subject instead of the general content and title.

4. **Invitation Subject and Content:**
Pulse provides separate options for the invitation subject and content. Tags are placed under the invitation content editor, which serves as placeholders for dynamic values such as student names, course names, course URLs, and sender names. These tags work for both the subject and content of the invitation.

![invitation-pulse](https://lmsacelab.com/doc-images/invitation.png)

## Pulse Pro Invitations

Pulse Pro offers even more reliable invitations and additional features. In the Pro version, you can set invitation recipients by course role. For example, if you set the recipient role as "teacher", course teachers will receive notifications for each student.

Course creators can use this feature to notify teachers when new students are enrolled in the course when students complete modules, and in other situations.

![invitation-pulse](https://lmsacelab.com/doc-images/invitation-pro.png)

# Reminders

The Reminders feature in Pulse allows teachers to configure and send multiple notifications to course participants. With the free version of Pulse, only one notification can be sent, but the Pro version provides additional options for sending reminders.

There are three types of reminders that can be configured in Pulse Pro:

1. **First Reminder**
2. **Second Reminder**
3. **Recurring Reminder**

For each type of reminder, the teacher can configure the following:

1. **Notification content** - the message that will be sent in the notification
2. **Notification subject** - the subject line of the notification
3. **Notification recipients** - who will receive the notification, including individual students, user roles (such as a parent or manager), or course roles (such as a teacher or non-editing teacher)

For fixed reminders (the first and second reminders), the teacher can set a fixed date (such as June 20th) or a relative date (such as X minutes/hours/days/weeks/months after the activity becomes available) for when the reminder will be sent.

For recurring reminders, only a relative date can be set. The teacher can choose to send a reminder every X minutes/hours/days/weeks/months, depending on their preferences.

It's important to note that reminders will only be sent when the activity is available. If the activity is not yet available or is no longer available, reminders will not be sent.

Overall, the Reminders feature in Pulse Pro can help teachers stay in touch with their students and keep them engaged with course content by sending timely and relevant notifications.

Before sending invitations and reminders, PulsePro ensures that users are available for the pulse instances. The scheduled task called "*`local_pulsepro\task\availabletime`*" runs on a regular basis (e.g. every cron run) and fetches a list of pulse instances. It then splits the users for each instance based the records based on the configured limit in global settings. Next, it sets up an ad-hoc task called "`local_pulsepro\task\availability`" to verify and store the user availability for each user.

The availability of each user is determined based on the following criteria:

* The user is currently enrolled in the course containing the pulse instance
* The user has completed all availability restrictions required to access the pulse instance
* The pulse instance is currently available to the user based on any date restrictions set by the teacher
Once the availability for each user has been determined, the teacher can then proceed to send invitations or reminders to the appropriate recipients.

Sending reminders is handled by three separate scheduled tasks:

1. `local_pulsepro\task\first_reminder`
2. `local_pulsepro\task\second_reminder`
3. `local_pulsepro\task\recurring_reminder`

Each task fetches the pulse instances that have been configured to send the corresponding type of reminder (i.e. first reminder, second reminder, or recurring reminder). It then splits the users based on a limit set by the teacher (e.g. 100 users per task) and sets up an ad-hoc task to send the reminder to those users.

It's worth noting that reminders will not be sent if the pulse instance is not currently available to the user. For example, if a user has not yet completed a prerequisite activity, they will not receive a reminder until the activity has been completed.

![firstreminder-pulsepro](https://lmsacelab.com/doc-images/first-reminder.png)

![secondreminder-pulsepro](https://lmsacelab.com/doc-images/second-reminder.png)


# Feature: Credits

PulsePro provides another great feature, credits, which can help motivate students to engage with the course by giving them rewards for completing modules or meeting certain criteria.

Credits are awarded to students through a scheduled task called "*`local_pulsepro\task\credits`*". When this task is triggered, it retrieves a list of Pulse instances and filters qualified course participants to assign credits. The task then splits the records based on the configured limit in global settings and sets up an ad-hoc task for each set of users. The credits are updated in the user's profile field when the ad hoc task is running.

The enrol_credit plugin enables students to enroll in courses using their accumulated credits. This plugin works by checking the user's credit balance before allowing them to enroll in the course. If the user has enough credits, they will be enrolled, otherwise, they will be prompted to earn more credits before gaining access to the course.

## Configuration

1. To set up the credit system, create a custom text field in the user's profile and select it in the PulsePro global settings as the "**Credits user profile field**".
2. Once the field is configured, the actions section in Pulse will have a "Credit score" option that allows instructors to assign credits to students.

The feature can be used to restrict access to Pulse based on other activity completion. Once a student completes the activity, they will be granted access to the Pulse module and awarded credits.

In summary, Pulse's credit feature is a valuable tool for motivating students and promoting engagement. With careful configuration and appropriate restrictions, students can be rewarded for their efforts and encouraged to continue their learning journey.

![credits-pulsepro](https://lmsacelab.com/doc-images/credits.png)


# Feature: Reactions

PulsePro includes a reactions feature that enables recipients of notifications to react directly from the email without being logged into the platform. This feature uses tokenized links, which are valid for a configurable amount of time (set in the global settings). Once the configured amount of time elapses, the recipient can still react, but they will need to log into the platform.

The reaction can be accessed via a page that can only be accessed with a valid, tokenized link before it expires. A pulse activity can only have one reaction, which can be chosen from a dropdown menu in the pulse module configuration page. The available reactions are:

1. **No Reaction**
If this option is selected, no further action is needed from the recipient. The notification will be marked as read, and no response will be recorded.

2. **Mark Complete**
Similar to the "Mark as Complete" feature in Moodle, this allows the student to indicate that they have completed the Pulse activity. They can do this via reactions, making it easy and convenient for them to track their progress in the course.

3. **Rate**
With this feature, the recipient of a Pulse notification can give a thumbs up or down to the Pulse via reaction. This feedback can help gauge the effectiveness of the Pulse activity and improve its design.

4. **Approve**
This feature allows users with a selected role to approve a student's completion of a Pulse activity. The selected role is specified in the completion condition of the Pulse activity. When the selected users approve a student's completion, the Pulse activity is marked as complete for the student.

Once a reaction is enabled, the teacher can decide where the reaction is displayed. There are three options available:

1. **Notification Only** - This option displays the reaction only in the notification message or emails that the recipient receives.

2. **Notification and Content** - This option displays the reaction both in the notification message and in the content section of the Pulse activity.

3. **Content Only** - This option displays the reaction only in the content section of the Pulse activity.

The reactions feature provides an easy way to get immediate responses to notifications and to quickly mark activities as complete or approved. The tokenized links ensure that recipients can react directly from the email without having to log into the platform, making it a convenient feature for busy students and teachers alike.

Reactions setup and output IMAGES ARE GOES HERE.

# Feature: Pulse Presets

Pulse Presets are a set of pre-configured settings for Pulse activities that can be saved and reused later, making it easier for teachers to create and manage Pulse activities. Admins can create and customize presets for teachers, allowing them to easily use Pulse for common tasks without having to configure each Pulse activity manually.

With Pulse Presets, teachers can pick pre-configured Pulse activities and customize them to their needs. They can also view the configuration options of a preset before applying it. Site administrators can use Pulse without any initial configuration and can pick which of the presets shipping with Pulse they want to enable/disable for their teachers.

In Pulse Pro, admins can create new presets, adjust the existing ones to their needs, add instructions to each preset, and order them for their teachers. Presets are managed in the plugin's global settings page and are essentially backup files of a Pulse activity that can be restored with a click of a button. Each preset has a title, icon (optional), description (optional), instructions (optional), a backup file of the Pulse activity (required), configurable parameters (optional), status (enabled/disabled), and sort order.

Pulse Presets improve the usability of Pulse, given its complexity and various use cases, making it super simple and fast to use.

# Completion

One of the key features of Pulse is its support for completion criteria, which allows course creators to specify when an activity is considered complete. In this document, we will describe the three completion criteria available in Pulse: Complete When Available, Self Mark Completed, and Require Approval.

1. **Complete When Available:** The completion criteria "complete when available" is a setting in Moodle's Pulse activity plugin. When this criterion is selected, the activity is considered complete as soon as it becomes available to the user. This can happen in two ways: either immediately after an enrollment becomes active, if no restrictions are added, or as soon as all restrictions are met.

For example, if an activity is set to be available to all students on a certain date, the activity will be considered complete for each student as soon as that date arrives and the activity becomes available to them. Similarly, if there are prerequisites or completion conditions set for the activity, it will only be considered complete for the student once they have met all of those conditions and the activity becomes available to them.

2. **Self-Mark Completed:**
The completion criteria "self mark completed" is a variation of the default "Students can manually mark the activity as completed" criteria in Pulse. However, there is a significant difference between them. In the "self mark completed" criteria, once the student marks the activity as completed, they will not be able to undo it. The completion status will remain as "completed" forever.

This criterion is useful in scenarios where students need to complete a specific task or assignment before they can move on to the next module or activity. Once they complete the task, they can mark it as done and move on to the next module without worrying about accidentally unmarking the task as completed. This helps to maintain a clear record of their progress throughout the course.

3. **Require Approval:**
The completion criteria "require approval" means that the activity will be considered complete only when a specified role approves it. This means that a certain role, which can be selected from all the roles available in the course or user context, will need to confirm that the activity has been completed before it is marked as complete.

For example, if the specified role is set as "teacher," then a teacher will need to review and approve the activity before it is considered complete. This criterion is useful when a certain level of verification or quality control is needed before an activity is marked as complete.

To set the completion criteria for an activity in Pulse, navigate to the activity settings, and choose "Completion Tracking." Then, select the desired completion criteria from the dropdown menu. Once saved, the activity will be considered complete based on the selected criteria.

In summary, Pulse's completion criteria options provide course creators with the flexibility to customize how activities are considered complete based on their unique needs and goals.

# Reports

PulsePro provides a detailed report of user responses, including reactions, invitations, reminders received time, self-marked completion, and approval status. The report can be accessed by the site administrator through the Moodle report interface.

The report includes the following information:

1. User information: The report displays the user's name and last access date of the course.

3. Reactions: This section displays user reactions to each activity, including the likes, dislikes, and other types of feedback. The reactions can be used by the teacher to evaluate the user's engagement with the activity.

4. Invitation status: This section displays the date and time when an invitation was sent to each user.

5. Reminder status: This section displays the date and time when a reminder was sent to each user.

6. Self-Marked Completion: The report shows whether the user marked the pulse instance as complete by themselves.

7. Approval Status: If the completion criteria require approval, the report displays the approval status for each user.

8. Pulse completion status: This section provides information about the user's completion status for each activity. The completion criteria can be set by the teacher or administrator.

The report can be exported to various formats such as CSV, Excel, and PDF for further analysis or sharing. This report helps course creators and administrators to track the progress of their students, monitor engagement, and identify areas where improvements can be made.

