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
 * Pulse events condition libarary file.
 *
 * @package   pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Fetch the events data form the condition overrides table.
 * 
 * @return array $list event list
 */
function get_events() {
    global $DB;

    $list = [];
    $eventscoditiondata = $DB->get_records('pulse_condition_overrides', ['triggercondition' => 'events']);
    foreach ($eventscoditiondata as $data) {
        $additional = json_decode($data->additional);
        $list[] = [
            'eventname' => $additional->event,
            'callback' => '\pulsecondition_course\eventobserver::pulse_event_condition_triggered',
        ];
    }
    return $list ?? [];
}