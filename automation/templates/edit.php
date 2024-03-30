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
use mod_pulse\automation\manage;

// Require config.
require(__DIR__.'/../../../../config.php');

// Login check after config inlcusion.
require_login();

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// User access checks.
require_sesskey();

// Verify the user capability.
$context = \context_system::instance();
require_capability('mod/pulse:viewtemplateslist', $context);

// Automation template ID to edit.
$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', false, PARAM_ALPHA);

// Page values.
$url = new moodle_url('/mod/pulse/automation/templates/edit.php', ['id' => $id, 'sesskey' => sesskey()]);

// Setup page values.
$PAGE->set_url($url);
$PAGE->set_context($context);

$PAGE->add_body_class('mod-pulse-automation-filter');

if ($action !== null && confirm_sesskey()) {
    // Every action is based on a template, thus the template ID param has to exist.
    $templateid = optional_param('id', null, PARAM_INT);
    $courseid = optional_param('courseid', null, PARAM_INT);

    // Instance management.
    $manage = mod_pulse\automation\manage::create($templateid, $courseid);

    $redirectback = false;
    $redirectmessage = false;
    // Perform the requested action.
    switch ($action) {

        case 'delete':
            $redirectback = $manage->delete_course_instance();
            // Notification to user for instance deleted success.
            $redirectmessage = \core\notification::success(get_string('templatedeleted', 'pulse'));
            break;

        case 'add':
            $redirectback = $manage->add_course_instance();
            // Show the inserted success notification.
            $redirectmessage = \core\notification::success(get_string('templateinsertsuccess', 'pulse'));
            break;

        case 'disable':
            // Disable the instances visibility in the course.
            $redirectback = $manage->update_instance_status(false);
            break;

        case 'enable':
            // Enable the instance visibility in the course.
            $redirectback = $manage->update_instance_status(true);
            break;
    }

    if ($redirectback) {
        if ($redirectmessage) {
            redirect($PAGE->url, $redirectmessage, 5);
        } else {
            redirect($PAGE->url);
        }
    }

}

if (is_siteadmin()) {
    // Extend the features of admin settings.
    admin_externalpage_setup('pulseautomation');
    $PAGE->set_heading(get_string('autotemplates', 'pulse'));
    $moduleurl = new moodle_url('/admin/category.php?category=modsettings');
    $PAGE->navbar->add(get_string('activity', 'core'), $moduleurl);
} else {
    $moduleurl = new moodle_url('/user/profile.php');
    $PAGE->navbar->add(get_string('profile', 'core'), $moduleurl);
}

// Build instance management table filter.
$filterset = new mod_pulse\table\manage_instance_filterset;

$category = new \core_table\local\filter\integer_filter('category');
if ($categoryid = optional_param('category', null, PARAM_INT)) {
    $category->add_filter_value($categoryid);
    $filtered = true;
}
$filterset->add_filter($category);

$course = new \core_table\local\filter\integer_filter('course');
if ($courseid = optional_param('course', null, PARAM_INT)) {
    $course->add_filter_value($courseid);
    $filtered = true;
}
$filterset->add_filter($course);

// Number of instances filter for instance management table.
$instancenumber = new \core_table\local\filter\integer_filter('numberofinstance');
$numberofinstance = optional_param('numberofinstance', null, PARAM_INT);
$submitted = data_submitted();
// Verify the filter is empty or requested with 0.
if ($numberofinstance || (isset($submitted->numberofinstance) && $submitted->numberofinstance !== '')) {
    $instancenumber->add_filter_value($numberofinstance);
    $filtered = true;
}
$filterset->add_filter($instancenumber);

// Number of overrides.
$overrides = new \core_table\local\filter\integer_filter('numberofoverrides');
$numberofoverrides = optional_param('numberofoverrides', null, PARAM_INT);

// Verify the filter is empty or requested with 0.
if ($numberofoverrides || (isset($submitted->numberofoverrides) && $submitted->numberofoverrides !== '')) {
    $overrides->add_filter_value($numberofoverrides);
    $filtered = true;
}
$filterset->add_filter($overrides);

// PAGE breadcrumbs.
$PAGE->navbar->add(get_string('autotemplates', 'pulse'), new moodle_url('/mod/pulse/automation/templates/list.php'));
$PAGE->navbar->add(get_string('edit'));

$manageinstancetable = new \mod_pulse\table\manage_instance($id);
$manageinstancetable->set_filterset($filterset);

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

$tabs = [
    ['name' => 'autotemplate-general', 'title' => get_string('tabgeneral', 'pulse'), 'active' => 'active'],
    ['name' => 'pulse-condition-tab', 'title' => get_string('tabcondition', 'pulse')],
];

$context = \context_system::instance();

// Load all actions forms.
// Define the lang key "formtab" in the action component it automatically includes it.
foreach (helper::get_actions() as $key => $action) {
    $tabs[] = ['name' => 'pulse-action-'.$key, 'title' => get_string('formtab', 'pulseaction_'.$key)];
}

// Instance management tab.
if ($templateid = $templatesform->get_customdata('id') && has_capability('mod/pulse:manageinstance', $context)) {
    $tabs[] = ['name' => 'manage-instance-tab', 'title' => get_string('tabmanageinstance', 'pulse')];
}

echo $OUTPUT->render_from_template('mod_pulse/automation_tabs', ['tabs' => $tabs]);

echo '<div class="tab-content" id="pulsetemplates-tab-content">';

// Display the templates form for create or edit.
echo $templatesform->display();

if ($templateid = $templatesform->get_customdata('id') && has_capability('mod/pulse:manageinstance', $context)) {
    // Template Manage instance.
    echo $templatesform->load_template_manageinstance($manageinstancetable);
}

echo html_writer::end_div();

$PAGE->requires->js_amd_inline("require(['jquery'], function() {
        // Filter form display.
        var filterIcon = document.querySelector('#pulse-manageinstance-filter');
        var filterForm = document.querySelector('#pulse-automation-filterform');
        filterIcon.onclick = (e) => filterForm.classList.toggle('hide');
    })"
);
// Footer.
echo $OUTPUT->footer();
