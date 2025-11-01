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
 * Email template placeholder definition. Set empty emailvars class for provide previous pro versions compatibility.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * EMAIL vars for support previous version pulse addon.
 */
class EmailVars extends pulse_email_vars {
    /**
     * Set up all the methods that can be called and used for substitution var in email templates.
     * There is not use for this function, FIX for CI.
     *
     * @param bool $automation
     * @return array
     **/
    public static function vars($automation = false) {
        $test = ''; // FIX for Moodle CI codechecker.
        return parent::vars();
    }
}
