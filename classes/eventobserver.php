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
 * Autoload class event observer
 *
 * @package   mod_pulse
 * @category  Classes - autoloading
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

defined('MOODLE_INTERNAL') || die('No direct access !');

/**
 * Observer for the event's mentioned on the db/events.php.
 */
class eventobserver {

    /**
     * Course module created event observer. To send the activity pulse.
     *
     * @param  mixed $event activity instance data.
     * @return void
     */
    public static function course_module_created($event) {
        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];
            $courseid = $event->courseid;
            self::create_pulse_users($pulseid, $courseid);
        }
    }

    /**
     * Course module created event observer. To send the activity pulse.
     *
     * @param  mixed $event activity instance data.
     * @return void
     */
    public static function course_module_updated($event) {

        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];
            $courseid = $event->courseid;
            self::reset_pulse_users($pulseid, $courseid);
        }
    }

    /**
     * Add pulse users instance in db with empty list of users.
     *
     * @return void
     */
    public static function create_pulse_users($pulseid, $courseid) {
        global $DB;
        if (!$DB->record_exists('pulse_users', ['course' => $courseid, 'pulse' => $pulseid])) {
            // Reset the users list in db to send updated pulse to all users.
            $record = new \stdclass();
            $record->course = $courseid;
            $record->pulse = $pulseid;
            $record->notified_users = json_encode([]);
            $record->timemodified = time();
            $DB->insert_record('pulse_users', $record);
        }
    }

    /**
     * Reset the notified users list for pulse instance.
     *
     * @param  mixed $pulseid
     * @param  mixed $courseid
     * @return void
     */
    public static function reset_pulse_users($pulseid, $courseid) {
        global $DB;
        $pulse = $DB->get_record('pulse', ['id' => $pulseid]);
        if ($pulse->pulse && $pulse->resend_pulse) {
            if ($record = $DB->get_record('pulse_users', ['course' => $courseid, 'pulse' => $pulseid])) {
                // Reset the users list in db to send updated pulse to all users.
                $empty = json_encode([]);
                $record->timemodified = time();
                $record->notified_users = $empty;
                $result = $DB->update_record('pulse_users', $record);

                // Reschedule the notification if resend notification enabled.
                $message= get_string('resendnotificationdesc', 'mod_pulse');
                \core\notification::add($message, 'info');
            }
        }
    }
}