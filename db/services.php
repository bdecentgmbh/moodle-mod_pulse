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
 * Pulse external services.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [

    'mod_pulse_apply_presets' => [
        'classname' => 'mod_pulse\external',
        'methodname' => 'apply_presets',
        'description' => 'Apply presets in mod pulse form',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'mod_pulse_manage_instances' => [
        'classname' => 'mod_pulse\external',
        'methodname' => 'manage_instances',
        'description' => 'Bulk deleted the automation instances in the instance management table',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
