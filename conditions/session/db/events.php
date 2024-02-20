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
 * DB Events -  Define event observers for "Session module Condition".
 *
 * @package   pulsecondition_session
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Signup success and signup failed event observers for the "Session module Completion" condition in the pulse 2.0.
$observers = [
    array(
        'eventname' => '\mod_facetoface\event\signup_success',
        'callback' => '\pulsecondition_session\conditionform::signup_success',
    ),
    array(
        'eventname' => '\mod_facetoface\event\signup_failed',
        'callback' => '\pulsecondition_session\conditionform::signup_success',
    ),
    array(
        'eventname' => '\mod_facetoface\event\cancel_booking',
        'callback' => '\pulsecondition_session\conditionform::signup_success',
    ),
];
