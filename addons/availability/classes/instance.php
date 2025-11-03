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

namespace pulseaddon_availability;

/**
 * Availability addon instance class.
 *
 * @package    pulseaddon_availability
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance extends \mod_pulse\addon\base {
    /**
     * Get the name of the addon.
     *
     * @return string
     */
    public function get_name() {
        return 'availability';
    }

    /**
     * Delete availability records for this pulse instance.
     *
     * @return void
     */
    public function instance_delete() {
        global $DB;

        // Remove pulse availability records.
        if ($DB->record_exists('pulseaddon_availability', ['pulseid' => $this->pulseid])) {
            $DB->delete_records('pulseaddon_availability', ['pulseid' => $this->pulseid]);
        }
    }

    /**
     * Handle user unenrolment event.
     * Removes user records from availability, completion and related tables.
     *
     * @param \core\event\user_enrolment_deleted $event The unenrolment event
     * @return void
     */
    public static function event_user_enrolment_deleted($event) {
        global $DB;

        $userid = $event->relateduserid; // Unenrolled user id.
        $courseid = $event->courseid;
        $list = \mod_pulse\helper::course_instancelist($courseid);

        if (!empty($list)) {
            $pulselist = array_column($list, 'instance');
            [$insql, $inparams] = $DB->get_in_or_equal($pulselist);
            $inparams[] = $userid;
            $select = " pulseid $insql AND userid = ? ";
            // Remove the user availability records.
            $DB->delete_records_select('pulseaddon_availability', $select, $inparams);
            $DB->delete_records_select('pulse_completion', $select, $inparams);
        }
    }

    /**
     * course module deleted event observer.
     * Remove the user and instance records for the deleted modules from pulsepro tables.
     *
     * @param  stdclass $event
     * @return void
     */
    public static function event_course_module_deleted($event) {
        global $CFG, $DB;

        if ($event->other['modulename'] == 'pulse') {
            $pulseid = $event->other['instanceid'];

            // Remove pulse user credits records.
            if ($DB->record_exists('pulseaddon_availability', ['pulseid' => $pulseid])) {
                $DB->delete_records('pulseaddon_availability', ['pulseid' => $pulseid]);
            }
        }
    }
}
