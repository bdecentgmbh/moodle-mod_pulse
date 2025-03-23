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
 * Version information for pulseaddon_availability
 *
 * @package    pulseaddon_availability
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'pulseaddon_availability';
$plugin->release      = '1.0';
$plugin->version      = 2024122607;
$plugin->requires     = 2021051700; // Moodle 3.11.
$plugin->maturity     = MATURITY_STABLE;
$plugin->supported    = [401, 405];
$plugin->dependencies = [
    'mod_pulse' => 2024101206,
];
