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
 * DB Events -  Define event observers for "User Enrolment Condition".
 *
 * @package   pulsecondition_enrolment
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// User created, deleted and updated event observers for the "User Enrolment Completion" condition in the pulse 2.0.
$observers = [
    [
        'eventname' => 'core\event\user_enrolment_created',
        'callback' => '\pulsecondition_enrolment\conditionform::user_enrolled',
    ],
    [
        'eventname' => 'core\event\user_enrolment_deleted',
        'callback' => '\pulsecondition_enrolment\conditionform::user_enrolled',
    ],
    [
        'eventname' => 'core\event\user_enrolment_updated',
        'callback' => '\pulsecondition_enrolment\conditionform::user_enrolled',
    ],
];
