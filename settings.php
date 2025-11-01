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

require_once($CFG->dirroot . '/mod/pulse/lib.php');

$ADMIN->add('modsettings', new admin_category('modpulse', new lang_string('pluginname', 'mod_pulse')));
$settings = new admin_settingpage('pulsegeneralsettings', get_string('generalsettings', 'pulse'), 'moodle/site:config', false);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_pulse/general', '', get_string('configintro', 'pulse')));
    $settings->add(new admin_setting_configcheckbox(
        'mod_pulse/detailedlog',
        get_string('showhide', 'pulse'),
        get_string('detailedlog', 'pulse'),
        false
    ));

    $settings->add(new admin_setting_configtext(
        'mod_pulse/schedulecount',
        get_string('tasklimituser', 'pulse'),
        get_string('tasklimituserdesc', 'pulse'),
        500,
        PARAM_INT
    ));

    // Mark as complete confirmation.
    $settings->add(new admin_setting_configcheckbox(
        'mod_pulse/completionbtnconfirmation',
        get_string('requireconfirm', 'pulse'),
        get_string('requireconfirm_help', 'pulse'),
        false
    ));
    // Mark as complete button text.
    $title = get_string('btntext', 'pulse');
    $description = get_string('btntext_help', 'pulse');
    $btntexts = [
        BUTTON_TEXT_DEFAULT => get_string('markcompletebtnstring_default', 'pulse'),
        BUTTON_TEXT_ACKNOWLEDGE => get_string('markcompletebtnstring_custom1', 'pulse'),
        BUTTON_TEXT_CONFIRM => get_string('markcompletebtnstring_custom2', 'pulse'),
        BUTTON_TEXT_CHOOSE => get_string('markcompletebtnstring_custom3', 'pulse'),
        BUTTON_TEXT_APPROVE => get_string('markcompletebtnstring_custom4', 'pulse'),
    ];
    $settings->add(new admin_setting_configselect(
        'mod_pulse/completionbtntext',
        $title,
        $description,
        BUTTON_TEXT_DEFAULT,
        $btntexts
    ));
    // Confirmation text content.
    $name = 'mod_pulse/completionbtn_content';
    $title = get_string('confirmtext', 'pulse');
    $description = get_string('confirmtext_help', 'pulse');
    $settings->add(new admin_setting_confightmleditor($name, $title, $description, ''));
}

$ADMIN->add('modpulse', $settings);

$settings = null; // Reset the settings.

foreach (core_plugin_manager::instance()->get_plugins_of_type('pulseaddon') as $plugin) {
    // Load all the dashaddon plugins settings pages.
    $plugin->load_settings($ADMIN, 'modpulse', $hassiteconfig);
}

$ADMIN->add('modpulse', new admin_externalpage(
    'managepulseaddonplugins',
    get_string('managepulseaddonplugins', 'pulse'),
    new moodle_url('/mod/pulse/manageaddon.php', ['subtype' => 'pulseaddon'])
));
