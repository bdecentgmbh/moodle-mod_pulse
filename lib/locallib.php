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
 * Pulse context class to create a context_course instance from record.
 *
 * @package   mod_pulse
 * @copyright 2025, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access');

/**
 * Course context class to create a context_course instance from record.
 */
class mod_pulse_context_course extends \context_course {

    /**
     * Convert the record of context into course_context object.
     *
     * @param stdclass $data
     * @return void
     */
    public static function create_instance_fromrecord($data) {
        return \context::create_instance_from_record($data);
    }
}

/**
 * Module context class to create a context_course instance from record.
 */
class mod_pulse_context_module extends \context_module {

    /**
     * Convert the record of context into course_context object.
     *
     * @param stdclass $data
     * @return \context
     */
    public static function create_instance_fromrecord($data) {
        return \context::create_instance_from_record($data);
    }
}
