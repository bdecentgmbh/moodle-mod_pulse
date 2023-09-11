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

// User access checks.
require_sesskey();

// Verify the user capability.
$context = \context_system::instance();
require_capability('mod/pulse:viewtemplateslist', $context);

// Automation template ID to edit.
$id = optional_param('id', null, PARAM_INT);

// Extend the features of admin settings.
admin_externalpage_setup('pulseautomation');

// Page values.
$url = new moodle_url('/mod/pulse/automation/templates/edit.php', ['id' => $id, 'sesskey' => sesskey()]);

// Setup page values.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading(get_string('autotemplates', 'pulse'));

// PAGE breadcrumbs.
$PAGE->navbar->add(get_string('mycourses', 'core'), new moodle_url('/course/index.php'));
// $PAGE->navbar->add(format_string($course->shortname), new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('autotemplates', 'pulse'), new moodle_url('/mod/pulse/automation/templates/list.php'));
$PAGE->navbar->add(get_string('edit'));

// Edit automation templates form.
$templatesform = new \mod_pulse\forms\automation_template_form(null, ['id' => $id]);

// Template list url.
$overviewurl = new moodle_url('/mod/pulse/automation/templates/list.php');

// Handling the templates form submitted data.
if ($formdata = $templatesform->get_data()) {

    // Create and update the template.
    $result = mod_pulse\automation\templates::manage_instance($formdata);

    // Redirect to templates list.
    redirect($overviewurl);

} else if ($templatesform->is_cancelled()) {
    // Form cancelled, redirect to the templates list.
    redirect($overviewurl);
}

// Setup the tempalte data to the form, if the form id param available.
if ($id !== null && $id > 0) {

    // Fetch the data of the template and its conditions and actions.
    if ($record = mod_pulse\automation\templates::create($id)->get_template()) {
        // Set the template data to the templates edit form.
        $templatesform->set_data($record);
    } else {
        // Direct the user to list page with error message, when the requested template is not available.
        \core\notification::error(get_string('templatesrecordmissing', 'pulse'));
        redirect($overviewurl);
    }

} else {
    // Trigger the prepare file areas for the new template create.
    $templatesform->set_data([]);
}
// Page content display started.
echo $OUTPUT->header();

// Templates heading.
echo $OUTPUT->heading(get_string('templatessettings', 'pulse'));

// Display the templates form for create or edit.
echo $templatesform->display();

// Footer.
echo $OUTPUT->footer();
