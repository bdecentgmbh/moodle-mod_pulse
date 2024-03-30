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
 * Notification pulse action - Library file contains commonly used functions.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use pulseaction_notification\notification;
use pulseaction_notification\schedule;

/**
 * Returns list of fileareas used in the pulsepro reminder contents.
 *
 * @return array list of filearea to support pluginfile.
 */
function pulseaction_notification_extend_pulse_filearea() : array {

    return [
        'pulsenotification_headercontent',
        'pulsenotification_staticcontent',
        'pulsenotification_footercontent',
        'pulsenotification_headercontent_instance',
        'pulsenotification_staticcontent_instance',
        'pulsenotification_footercontent_instance'
    ];
}

/**
 * Updates chapters for a notification output fragment.
 *
 * @param array $args An associative array of arguments.
 *   - 'context' (object) The context object.
 *   - 'mod' (int) The Course Module ID (optional).
 *
 * @return mixed Returns the loaded book chapters or null if 'mod' is not set in the arguments.
 */
function pulseaction_notification_output_fragment_update_chapters($args) {
    $context = $args['context'];

    if (isset($args['mod'])) {
        $cmid = $args['mod'];
        return pulseaction_notification\notification::load_book_chapters($cmid);
    }
}

/**
 * Preview of the instance for sepecific user.
 *
 * @param array $args
 * @return string
 */
function pulseaction_notification_output_fragment_preview_instance_content($args) {
    global $OUTPUT;

    $context = $args['context'];
    if (isset($args['instanceid'])) {

        $insobj = new \mod_pulse\automation\instances($args['instanceid']);
        $formdata = (object) $insobj->get_instance_data();

        $notificationid = $formdata->actions['notification']['id'];
        $notificationobj = pulseaction_notification\notification::instance($notificationid);

        $notificationobj->set_notification_data($formdata->actions['notification'], $formdata);
        $content = $notificationobj->build_notification_content(null, null, $formdata->override);

        $sender = core_user::get_support_user();
        $users = get_enrolled_users(\context_course::instance($formdata->courseid));
        $user = (object) ($args['userid'] != null ? core_user::get_user($args['userid']) : current($users));

        $course = get_course($formdata->courseid ?? SITEID);

        $mod = new stdclass;
        // TODO: Inlcude the vars update from condition plugins.
        if ($formdata->actions['notification']['dynamiccontent']) {
            // Prepare the module data. based on dynamic content and includ the session data.
            $modname = $formdata->actions['notification']['mod']->modname;
            $dynamicmodules[$modname][] = $formdata->actions['notification']['mod']->instance;
            $modules = notification::get_modules_data($dynamicmodules);
            $mod = current($modules[$modname]);
        }
        // Check the session condition are set for this notification. if its added then load the session data for placeholders.
        $sessionincondition = in_array('session', (array) array_keys($formdata->condition));
        if ($sessionincondition && $formdata->condition['session']['status']) {
            $sessionconditiondata = (object) ['modules' => $formdata->condition['session']['modules']];
            schedule::instance()->include_session_data($mod, $sessionconditiondata, $user->id);
        }

        // Include the conditions vars for placeholder replace.
        /* $plugins = \mod_pulse\plugininfo\pulsecondition::instance()->get_plugins_base();
        $conditionvars = [];
        foreach ($plugins as $component => $pluginbase) {
            $vars = $pluginbase->update_email_customvars($args['userid'], $formdata);
            $conditionvars += $vars ?: [];
        } */

        list($subject, $messagehtml) = mod_pulse\helper::update_emailvars($content, '', $course, $user, $mod, $sender);
        $selector = "";

        $data = ['message' => $messagehtml, 'usersselector' => $selector];

        return $OUTPUT->render_from_template('pulseaction_notification/preview', ['data' => $data]);
    }
}

/**
 * Preview the content of the instance.
 *
 * @param [type] $args
 * @return void
 */
function pulseaction_notification_output_fragment_preview_content($args) {
    global $OUTPUT;

    if (isset($args['contentheader'])) {

        parse_str($args['formdata'], $formdata);
        $courseid = $formdata['courseid'] ?? SITEID;
        $course = get_course($courseid);
        $coursecontext = context_course::instance($courseid);

        // Get the enrolled users for this course.
        $users = get_enrolled_users($coursecontext);
        $list = [];
        foreach ($users as $userid => $user) {
            $list[$userid] = fullname($user);
        }
        $sender = core_user::get_support_user();

        $user = (object) ($args['userid'] != null ? core_user::get_user($args['userid']) : current($users));

        $dynamiccontent = '';
        $mod = new stdclass;
        if (isset($args['contentdynamic']) && !empty($args['contentdynamic'])) {

            $module = get_coursemodule_from_id('', $args['contentdynamic']);
            $moddata = (object) [
                'instance' => $module->instance,
                'modname' => $module->modname,
                'id' => $module->id,
            ];
            $context = \context_module::instance($module->id);
            // Generate dynamic content for the instance.
            $dynamiccontent = notification::generate_dynamic_content(
                $args['contenttype'],
                $args['contentlength'],
                $args['chapterid'],
                $context,
                $moddata
            );
            // TODO: Inlcude the vars update from condition plugins.
            if ($args['contentdynamic']) {
                // Prepare the module data. based on dynamic content and includ the session data.
                $modname = $module->modname;
                $dynamicmodules[$modname][] = $module->instance;
                $modules = notification::get_modules_data($dynamicmodules);
                $mod = current($modules[$modname]);
            }

            // Check the session condition are set for this notification. if its added then load the session data for placeholders.
            if (isset($formdata['condition']['session']['status']) && $formdata['condition']['session']['status']) {
                $sessionconditiondata = (object) ['modules' => $formdata['condition']['session']['modules']];
                schedule::instance()->include_session_data($mod, $sessionconditiondata, $user->id);
            }
        }

        $content = $args['contentheader'] . $args['contentstatic'] . $dynamiccontent . $args['contentfooter'];

        // Update the placeholders with course and user data.
        list($subject, $messagehtml) = mod_pulse\helper::update_emailvars($content, '', $course, $user, $mod, $sender);
        // User selector.
        $selector = html_writer::select($list, 'userselector', $user->id);

        $data = ['message' => $messagehtml, 'usersselector' => $selector];
        return $OUTPUT->render_from_template('pulseaction_notification/preview', ['data' => $data]);
    }
}
