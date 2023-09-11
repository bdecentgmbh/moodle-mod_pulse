<?php

use pulseaction_notification\notification;

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

function pulseaction_notification_output_fragment_update_chapters($args) {
    $context = $args['context'];

    if (isset($args['mod'])) {
        $cmid = $args['mod'];
        return pulseaction_notification\notification::load_book_chapters($cmid);
    }
}


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
        $user =  (object) ($args['userid'] != null ? core_user::get_user($args['userid']) : current($users));

        $course = get_course($formdata->courseid ?? SITEID);

        list($subject, $messagehtml) = mod_pulse\helper::update_emailvars($content, '', $course, $user, null, $sender);
        $selector = "";

        $data = ['message' => $messagehtml, 'usersselector' => $selector];

        return $OUTPUT->render_from_template('pulseaction_notification/preview', ['data' => $data]);
    }
}

function pulseaction_notification_output_fragment_preview_content($args) {
    global $OUTPUT;

    $context = $args['context'];

    if (isset($args['contentheader'])) {
        $course = get_course($args['courseid'] ?? SITEID);

        $dynamiccontent = '';
        if (isset($args['contentdynamic']) && !empty($args['contentdynamic'])) {

            $module = get_coursemodule_from_id('', $args['contentdynamic']);

            $moddata = (object) [
                'instance' => $module->instance,
                'modname' => $module->modname,
                'id' => $module->id,
            ];
            $context = \context_module::instance($module->id);

            $dynamiccontent = notification::generate_dynamic_content(
                $args['contenttype'],
                $args['contentlength'],
                $args['chapterid'],
                $context,
                $moddata
            );

        }

        $content = $args['contentheader'] . $args['contentstatic'] . $dynamiccontent . $args['contentfooter'];

        $users = get_enrolled_users(\context_course::instance($course->id));
        $list = [];
        foreach ($users as $userid => $user) {
            $list[$userid] = fullname($user);
        }
        $sender = core_user::get_support_user();

        $user =  (object) ($args['userid'] != null ? core_user::get_user($args['userid']) : current($users));

        list($subject, $messagehtml) = mod_pulse\helper::update_emailvars($content, '', $course, $user, null, $sender);

        $selector = html_writer::select($list, 'userselector', $user->id);

        $data = ['message' => $messagehtml, 'usersselector' => $selector];
        return $OUTPUT->render_from_template('pulseaction_notification/preview', ['data' => $data]);
    }
}

