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
 * Mod pulse - Edit template.
 *
 * @package    mod_pulse
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pulse\automation\helper;

// Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

require_sesskey();

// Automation template ID to edit.
$id = optional_param('id', null, PARAM_INT);

// Extend the features of admin settings.
admin_externalpage_setup('pulseautomation');


// TODO: Capability checks.

// Page values.
$url = new moodle_url('/mod/pulse/automation/templates/edit.php', ['id' => $id, 'sesskey' => sesskey()]);
$context = \context_system::instance();

// Setup page values.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading(get_string('autotemplates', 'pulse'));

/*
    $PAGE->navbar->add(get_string('modules', 'core'), new moodle_url('/admin/category.php', array('category' => 'modsettings')));
    $PAGE->navbar->add(get_string('pluginname', 'pulse'), new moodle_url('/admin/category.php', array('category' => 'mod_pulse')));
    $PAGE->navbar->add(get_string('autotemplates', 'pulse'), new moodle_url('/mod/pulse/automation/templates/edit.php'));
 */


// Edit automation templates form.
$templatesform = new \mod_pulse\forms\automation_template_form(null, ['id' => $id]);

$overviewurl = new moodle_url('/mod/pulse/automation/templates/list.php');

if ($formdata = $templatesform->get_data()) {

    $result = mod_pulse\automation\templates::manage_instance($formdata);
    // Redirect to menus list.
    redirect($overviewurl);

} else if ($templatesform->is_cancelled()) {
    redirect($overviewurl);
}

// Setup the tempalte data to the form, if the form id param available.
if ($id !== null && $id > 0) {

    if ($record = mod_pulse\automation\templates::create($id)->get_template()) {
        // Set the template data to the templates edit form.
        $templatesform->set_data($record);
    } else {
        // Direct the user to list page with error message, when the requested menu is not available.
        \core\notification::error(get_string('templatesrecordmissing', 'pulse'));
        redirect($overviewurl);
    }

} else {
    // $templatesform->set_data($record);
}
// Page content display started.
echo $OUTPUT->header();

// Smart menu heading.
echo $OUTPUT->heading(get_string('templatessettings', 'pulse'));

// Display the smart menu form for create or edit.
echo $templatesform->display();

// Footer.
echo $OUTPUT->footer();
