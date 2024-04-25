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
 * Pulse module settings.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('modsettings', new admin_category('modpulse', new lang_string('pluginname', 'mod_pulse')));
$settings = new admin_settingpage('pulsegeneralsettings', get_string('generalsettings', 'pulse'), 'moodle/site:config', false);

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('mod_pulse/general', '', get_string('configintro', 'pulse')));
    $settings->add(new admin_setting_configcheckbox('mod_pulse/detailedlog', get_string('showhide', 'pulse'),
            get_string('detailedlog', 'pulse'), false));

    $settings->add(new admin_setting_configtext('mod_pulse/schedulecount', get_string('schedulecount', 'pulse'),
    get_string('schedulecountdesc', 'pulse'), 500, PARAM_INT));

}
$ADMIN->add('modpulse', $settings);
$settings = null; // Reset the settings.
