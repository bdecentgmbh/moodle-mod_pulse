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

// Require config.
require(__DIR__.'/../../../../config.php');

// Require plugin libraries.
// require_once($CFG->dirroot. '/theme/boost_union/smartmenus/menulib.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// Get parameters.
$action = optional_param('action', null, PARAM_ALPHAEXT);
$templateid = optional_param('id', null, PARAM_INT);
$instance = optional_param('instance', false, PARAM_BOOL);

// Page values.
$context = \context_system::instance();

admin_externalpage_setup('pulseautomation');

// Verify the user capability.
require_capability('mod/pulse:addtemplate', $context);

// Prepare the page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/pulse/automation/templates/list.php'));

// TODO: Capability checks.

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

// Further prepare the page.
$PAGE->set_heading(get_string('autotemplates', 'pulse'));

// Build automation templates table.
$table = new mod_pulse\table\auto_templates($context->id);
$table->define_baseurl($PAGE->url);

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('autotemplates', 'pulse'));

// Show templates description.
echo get_string('autotemplates_desc', 'pulse');

// Prepare 'Create template' button.
$createbutton = $OUTPUT->box_start();
$createbutton .= mod_pulse\automation\helper::template_buttons();
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
}


$PAGE->requires->js_amd_inline("require(['jquery', 'core/modal_factory', 'core/modal_events', 'core/str'], function($, ModalFactory, ModalEvents, Str) {
    var form = document.querySelectorAll('.updatestatus-switch-form');
    form.forEach((switche) => {
        switche.querySelector('input[type=checkbox]').addEventListener('change', function(e) {
            var statusElem = e.target.parentNode.querySelector('input[name=action]');
            var instanceElem = e.target.parentNode.querySelector('input[name=instance]');
            var form = e.target.closest('form');
            if (e.target.checked) {
                statusElem.value = 'enable';
            } else {
                statusElem.value = 'disable';
            }
            // e.target.closest('form').submit();

            /* var saveBtn = document.creatElement('button');
            saveBtn.setAttribute('type', 'button');
            saveBtn.setAttribute('class' , 'btn btn-primary');
            saveBtn.innerHTML = {{#str}} Update Instances {{/str}} */

            ModalFactory.create({
                title: Str.get_string('updatetemplate', 'pulse'),
                type: ModalFactory.types.SAVE_CANCEL,
                body: Str.get_string('templatestatusudpate', 'pulse')
            }).then(function(modal) {
                modal.setButtonText('save', Str.get_string('updateinstance', 'pulse') );
                modal.setButtonText('cancel', Str.get_string('updatetemplate', 'pulse'));
                modal.show();

                modal.getRoot().on(ModalEvents.save, (e) => {
                    instanceElem.value = true;
                    form.submit();
                });

                modal.getRoot().on(ModalEvents.cancel, (e) => {
                    instanceElem.value = false;
                    form.submit();
                });
            })
        })
    });

})");

// Finish page output.
echo $OUTPUT->footer();


