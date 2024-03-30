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
 * Mod pulse - List the available template and manage the template Create, Update, Delete actions, sort the order of templates.
 *
 * @package    mod_pulse
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pulse\automation\helper;

// Require config.
require(__DIR__.'/../../../../config.php');

// Login check after config inlcusion.
require_login();

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// Get parameters.
$action = optional_param('action', null, PARAM_ALPHAEXT);
$templateid = optional_param('id', null, PARAM_INT);
$instance = optional_param('instance', false, PARAM_BOOL);

// Page values.
$context = \context_system::instance();

if (is_siteadmin()) {
    // Setup breadcrumps.
    admin_externalpage_setup('pulseautomation');
}

// Verify the user capability.
require_capability('mod/pulse:viewtemplateslist', $context);

// Prepare the page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/pulse/automation/templates/list.php'));

// Process actions.
if ($action !== null && confirm_sesskey()) {
    // Every action is based on a template, thus the template ID param has to exist.
    $templateid = required_param('id', PARAM_INT);

    // Create template instance. Actions are performed in template instance.
    $template = mod_pulse\automation\templates::create($templateid);

    $transaction = $DB->start_delegated_transaction();

    // Perform the requested action.
    switch ($action) {
        // Triggered action is delete, then init the deletion of template.
        case 'delete':
            // Delete the template.
            if ($template->delete_template()) {
                // Notification to user for template deleted success.
                \core\notification::success(get_string('templatedeleted', 'pulse'));
            }
            break;
        case "hidemenu":
            // Disable the template visibility.
            $template->update_visible(false);
            break;
        case "showmenu":
            // Enable the template visibility.
            $template->update_visible(true);
            break;

        case 'disable':
            // Disable the template visibility.
            $template->update_status(false, $instance);
            break;

        case 'enable':
            // Disable the template visibility.
            $template->update_status(true, $instance);
            break;
    }

    // Allow to update the changes to database.
    $transaction->allow_commit();

    // Redirect to the same page to view the templates list.
    redirect($PAGE->url);
}

$PAGE->add_body_class('mod-pulse-automation-table');
$PAGE->add_body_class('mod-pulse-automation-filter');


// Further prepare the page.
$PAGE->set_heading(get_string('autotemplates', 'pulse'));

// Add breadcrumbs for the non admin users, has the capability to view templates.
if (!is_siteadmin()) {
    // PAGE breadcrumbs.
    $PAGE->navbar->add(get_string('profile', 'core'), new moodle_url('/user/profile.php'));
    $PAGE->navbar->add(get_string('autotemplates', 'pulse'), new moodle_url('/mod/pulse/automation/templates/list.php'));
    $PAGE->navbar->add(get_string('list'));
}

// Build automation templates table.
$filterset = new mod_pulse\table\automation_filterset;

if ($categoryid = optional_param('category', null, PARAM_INT)) {
    $category = new \core_table\local\filter\integer_filter('category');
    $category->add_filter_value($categoryid);
    $filterset->add_filter($category);
    $filtered = true;
}

// Build automation templates table.
$table = new mod_pulse\table\auto_templates($context->id);
$table->define_baseurl($PAGE->url);
$table->set_filterset($filterset);

// Start page output.
echo $OUTPUT->header();
// Display the heading only for admin users.
if (is_siteadmin()) {
    echo $OUTPUT->heading(get_string('autotemplates', 'pulse'));
}

// Show templates description.
echo get_string('autotemplates_desc', 'pulse');

// Prepare 'Create template' button.
$createbutton = $OUTPUT->box_start();
$createbutton .= mod_pulse\automation\helper::template_buttons($filtered ?? false);
$createbutton .= $OUTPUT->box_end();

// If there aren't any templates yet.
$countmenus = $DB->count_records('pulse_autotemplates');
if ($countmenus < 1) {
    // Show the table, which, since it is empty, falls back to the
    // "There aren't any templates created yet. Please create your first template to get things going." notice.
    $table->out(0, true);

    // And then show the button.
    echo $createbutton;

    // Otherwise.
} else {
    // Show the button.
    echo $createbutton;

    // And then show the table.
    $table->out(10, true);

    echo helper::get_templates_tablehelps();
}

$PAGE->requires->js_amd_inline("
require(['jquery', 'core/modal_factory', 'core/str', 'mod_pulse/modal_preset', 'mod_pulse/events', 'mod_pulse/presetmodal'],
    function($, ModalFactory, Str, ModalPreset, PresetEvents, PresetModal) {

    var form = document.querySelectorAll('.updatestatus-switch-form');
    form.forEach((switche) => {
        switche.querySelector('.custom-switch').addEventListener('click', function(e) {
            e.preventDefault();

            var statusElem = e.target.parentNode.querySelector('input[name=action]');
            var instanceElem = e.target.parentNode.querySelector('input[name=instance]');

            var form = e.target.closest('form');
            var checkbox = e.target.closest('.custom-control-input');

            if (checkbox.checked) {
                statusElem.value = 'enable';
            } else {
                statusElem.value = 'disable';
            }

            if (typeof ModalFactory.createFromType === 'function') {
                 var modalFn = ModalFactory.create({
                    type: ModalPreset.TYPE,
                    title: Str.get_string('updatetemplate', 'pulse'),
                    body: Str.get_string('templatestatusudpate', 'pulse'),
                    large: true
                });
            } else {
                var modalFn = PresetModal.create({
                    title: Str.get_string('updatetemplate', 'pulse'),
                    body: Str.get_string('templatestatusudpate', 'pulse'),
                    large: true
                });
            }

            modalFn.then(function(modal) {

                modal.setButtonText('customize', Str.get_string('updateinstance', 'pulse'));
                modal.setButtonText('save', Str.get_string('updatetemplate', 'pulse'));
                modal.show();

                modal.getRoot().on(PresetEvents.customize, (e) => {
                    instanceElem.value = true;
                    form.submit();
                });

                modal.getRoot().on(PresetEvents.save, (e) => {
                    instanceElem.value = false;
                    form.submit();
                });
            })
        })
    });

    // Filter form display.
    var filterIcon = document.querySelector('#pulse-automation-filter');
    var filterForm = document.querySelector('#pulse-automation-filterform');
    filterIcon.onclick = (e) => filterForm.classList.toggle('hide');

})");

// Finish page output.
echo $OUTPUT->footer();
