<?php

use pulseaction_notification\schedule;

require_once('../../../../config.php');

require_once($CFG->dirroot.'/mod/pulse/actions/notification/lib.php');

$PAGE->set_context(context_system::instance());

// $not = notification::instance(5);
// $not->create_schedule_foruser(2);

// pulseaction_notification_output_fragment_preview_instance_content(['context' => '1', 'instanceid' => 59]);
// notify_users
schedule::instance()->send_scheduled_notification();

// \mod_pulse\automation\instances::create(27)->trigger_action(3);
