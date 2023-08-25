<?php

use pulseaction_notification\notification;

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());

// $not = notification::instance(5);
// $not->create_schedule_foruser(2);

notification::send_scheduled_notification();
// \mod_pulse\automation\instances::create(27)->trigger_action(3);
