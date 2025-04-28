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
 * Scheduled cron task to send pulse.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/pulse/lib.php');

/**
 * Send notification to users - scheduled task execution observer.
 */
class notify_users extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('notifyusers', 'mod_pulse');
    }

    /**
     * Cron execution to send the available pulses.
     *
     * @return void
     */
    public function execute() {
        global $CFG;

        self::pulse_cron_task();
    }

    /**
     * Pulse cron task to send notification for course users.
     *
     * Here users are filtered by their activity avaialbility status.
     * if the pulse instance are available to user then it will send the notificaion to the user.
     *
     * @param  mixed $extend Extend the pro invitation method.
     * @return void
     */
    public static function pulse_cron_task($extend=true) {
        global $DB;

        pulse_mtrace( 'Fetching notificaion instance list - MOD-Pulse INIT ');

        if ($extend && \mod_pulse\extendpro::pulse_extend_invitation()) {
            return true;
        }

        $notification = new \mod_pulse\addon\notification();
        $notification->send_invitations();

        pulse_mtrace('Pulse message sending completed....');
        return true;
    }

    /**
     * Set adhoc task to send reminder notification for each instance
     *
     * @param  mixed $instance
     * @return void
     */
    public static function pulse_set_notification_adhoc($instance) {
        $task = new \mod_pulse\task\sendinvitation();
        $task->set_custom_data($instance);
        $task->set_component('pulse');
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
