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
 * Automation template form for the pulse 2.0.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\forms;

defined('MOODLE_INTERNAL') || die('No direct access!');

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');

use moodleform;
use html_writer;
use mod_pulse\automation\helper;
use mod_pulse\automation\templates;

/**
 * Define the automation template form.
 */
class automation_template_form extends moodleform {

    /**
     * Define the form elements inside the definition function.
     *
     * @return void
     */
    public function definition() {
        global $PAGE, $OUTPUT;

        $mform = $this->_form; // Get the form instance.
        $mform->updateAttributes(['id' => 'pulse-automation-template' ]);
        $tabs = [
            ['name' => 'autotemplate-general', 'title' => get_string('tabgeneral', 'pulse'), 'active' => 'active'],
            ['name' => 'pulse-condition-tab', 'title' => get_string('tabcondition', 'pulse')]
        ];
        // Load all actions forms.
        // Define the lang key "formtab" in the action component it automatically includes it.
        foreach (helper::get_actions() as $key => $action) {
            $tabs[] = ['name' => 'pulse-action-'.$key, 'title' => get_string('formtab', 'pulseaction_'.$key)];
        }

        $tab = $OUTPUT->render_from_template('mod_pulse/automation_tabs', ['tabs' => $tabs]);
        $mform->addElement('html', $tab);

        // Set the id of template.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Template options.
        $this->load_template_options($mform);

        // Template conditions.
        $this->load_template_conditions($mform);

        // Load template actions.
        $this->load_template_actions($mform);

        if ($templateid = $this->get_customdata('id')) {
            list($overcount, $overinstances) = templates::create($templateid)->get_instances();

            foreach ($mform->_elements as $key => $element) {

                $elementname = $element->getName();
                if (!isset($overcount[$elementname])) {
                    continue;
                }
                $count = html_writer::tag('span', $overcount[$elementname].' '.get_string('overrides', 'pulse'), [
                    'data-target' => "overridemodal",
                    'data-templateid' => $templateid,
                    'data-element' => $elementname,
                    'class' => 'override-count-element badge mt-1',
                    'id' => "override_$elementname"
                ]);
                $overrideelement = $mform->createElement('html', $count);
                $mform->insertElementBefore($overrideelement, $elementname);

                $mform->addElement('hidden', "overinstance_$elementname", json_encode($overinstances[$elementname]));
                $mform->setType("overinstance_$elementname", PARAM_RAW);
            }
        }

        // Show the list of overriden content.
        $PAGE->requires->js_call_amd('mod_pulse/automation', 'init');

        // Add standard form buttons.
        $this->add_action_buttons(true);
    }

    /**
     * Load the templates general elements to the form
     *
     * @return void
     */
    protected function load_template_options() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="tab-content" id="pulsetemplates-tab-content">
                            <div class="tab-pane fade show active" id="autotemplate-general">');

        // Add the Title element.
        $mform->addElement('text', 'title', get_string('title', 'pulse'), ['size' => '50']);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'pulse');

        // Add the Reference element.
        $mform->addElement('text', 'reference', get_string('reference', 'pulse'), ['size' => '50']);
        $mform->addRule('reference', null, 'required', null, 'client');
        $mform->setType('reference', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('reference', 'reference', 'pulse');

        // Add the Visibility element.
        $visibilityoptions = [
            templates::VISIBILITY_SHOW => get_string('show', 'pulse'),
            templates::VISIBILITY_HIDDEN => get_string('hidden', 'pulse'),
        ];
        $mform->addElement('select', 'visible', get_string('visibility', 'pulse'), $visibilityoptions);
        $mform->addHelpButton('visible', 'visibility', 'pulse');

        // Add the Internal Notes element.
        $mform->addElement('textarea', 'notes', get_string('internalnotes', 'pulse'), ['size' => '50']);
        $mform->setType('notes', PARAM_TEXT);
        $mform->addHelpButton('notes', 'internalnotes', 'pulse');

        // Add the Status element.
        $statusoptions = [
            templates::STATUS_ENABLE => get_string('enabled', 'pulse'),
            templates::STATUS_DISABLE => get_string('disabled', 'pulse'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'pulse'), $statusoptions);
        $mform->addHelpButton('status', 'status', 'pulse');

        // Add the Tags element.
        $tagsoptions = templates::get_tag_options(); // Populate with the available tags for administrative purposes.
        $instanceid = $this->get_customdata('instanceid');

        // Use the instance related tags options in the instance form.
        if ($instanceid && $DB->get_field('pulse_autotemplates_ins', 'tags', ['instanceid' => $instanceid])) {
            $tagsoptions = templates::get_tag_instance_options();
        }
        $mform->addElement('tags', 'tags', get_string('tags', 'pulse'), $tagsoptions, ['multiple' => 'multiple']);
        $mform->addHelpButton('tags', 'tags', 'pulse');

        // Add the Available for Tenants element.
        $tenantsoptions = []; // Populate with the available tenants for Moodle Workplace.
        $mform->addElement('autocomplete', 'tenants', get_string('availablefortenants', 'pulse'), $tenantsoptions,
                ['multiple' => 'multiple']);
        $mform->addHelpButton('tenants', 'availablefortenants', 'pulse');

        // Add the Available in Course Categories element.
        $categories = \core_course_category::make_categories_list();
        $cate = $mform->addElement('autocomplete', 'categories', get_string('availableincoursecategories', 'pulse'),
                $categories);
        $cate->setMultiple(true);
        $mform->addHelpButton('categories', 'availableincoursecategories', 'pulse');

        $mform->addElement('html', html_writer::end_div());
    }

    /**
     * Includ the template action trigger element to the templates form.
     *
     * @return void
     */
    protected function load_template_conditions() {

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="tab-pane fade" id="pulse-condition-tab"> ');

        $conditionplugins = new \mod_pulse\plugininfo\pulsecondition();
        $plugins = $conditionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->include_condition($option);
        }

        $condition = $mform->addElement('autocomplete', 'triggerconditions', get_string('conditiontrigger', 'pulse'), $option);
        $condition->setMultiple(true);
        $mform->addHelpButton('triggerconditions', 'conditiontrigger', 'pulse');

        // Operator element.
        $operators = [
            \mod_pulse\automation\action_base::OPERATOR_ALL => get_string('all', 'pulse'),
            \mod_pulse\automation\action_base::OPERATOR_ANY => get_string('any', 'pulse'),
        ];
        $mform->addElement('select', 'triggeroperator', get_string('triggeroperator', 'pulse'), $operators);
        $mform->addHelpButton('triggeroperator', 'triggeroperator', 'pulse');

        $mform->addElement('html', html_writer::end_div()); // E.o of actions triggere tab.
    }

    /**
     * Load template actions.
     *
     * @return void
     */
    protected function load_template_actions() {

        $mform =& $this->_form;
        $actionplugins = new \mod_pulse\plugininfo\pulseaction();
        $plugins = $actionplugins->get_plugins_base();

        foreach ($plugins as $name => $plugin) {
            // Define the form elements inside the definition function.
            $mform->addElement('html', '<div class="tab-pane fcontainer fade" id="pulse-action-'.$name.'"> ');
            $mform->addElement('html', '<h4>'.get_string('pluginname', 'pulseaction_'.$name).'</h4>');
            // Load the instance elements for this action.
            $plugin->load_global_form($mform, $this);
            $mform->addElement('html', html_writer::end_div()); // E.o of actions triggere tab.
        }
    }

    /**
     * Add email placeholder fields in form fields.
     *
     * @param  mixed $mform
     * @return void
     */
    public function pulse_email_placeholders(&$mform) {
        $vars = \pulse_email_vars::vars();

        static $htmlvars;
        if ($htmlvars === null) {
            $htmlvars = html_writer::start_tag('div', ['class' => 'form-group row fitem']);
            $htmlvars .= html_writer::div('', 'col-md-3');
            $htmlvars .= html_writer::start_div('col-md-9');
            $htmlvars .= html_writer::start_div('emailvars');

            $optioncount = 0;
            foreach ($vars as $option) {
                $htmlvars .= "<a href='#' data-text='$option' class='clickforword'><span>$option</span></a>";
                $optioncount++;
            }

            $htmlvars .= html_writer::end_div();
            $htmlvars .= html_writer::end_div();
            $htmlvars .= html_writer::end_div();
        }
        $mform->addElement('html', $htmlvars);
    }

    /**
     * Process the pulse module data before set the default.
     *
     * @param  mixed $defaultvalues default values
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        $context = \context_system::instance();
        helper::prepare_editor_draftfiles($defaultvalues, $context);
    }

    /**
     * Get custom data associated with a specific key.
     *
     * @param string $key The key to retrieve custom data.
     * @return mixed The custom data if found, otherwise an empty string.
     */
    public function get_customdata($key) {
        return $this->_customdata[$key] ?? '';
    }

    /**
     * Load in existing data as form defaults. Usually new entry defaults are stored directly in
     * form definition (new entry form); this function is used to load in data where values
     * already exist and data is being edited (edit entry form).
     *
     * note: $slashed param removed
     *
     * @param stdClass|array $defaultvalues object or array of default values
     */
    public function set_data($defaultvalues) {

        $this->data_preprocessing($defaultvalues); // Include to store the files.
        if (is_object($defaultvalues)) {
            $defaultvalues = (array)$defaultvalues;
        }
        $this->_form->setDefaults($defaultvalues);
    }

    /**
     * Get list of all course and user context roles.
     *
     * @return void
     */
    public function course_roles() {
        global $DB;

        list($insql, $inparam) = $DB->get_in_or_equal([CONTEXT_COURSE, CONTEXT_USER]);
        $sql = "SELECT lvl.id, lvl.roleid, rle.name, rle.shortname FROM {role_context_levels} lvl
        JOIN {role} AS rle ON rle.id = lvl.roleid
        WHERE contextlevel $insql ";

        $result = $DB->get_records_sql($sql, $inparam);
        $result = role_fix_names($result);
        $roles = [];
        // Generate options list for select mform element.
        foreach ($result as $key => $role) {
            $roles[$role->roleid] = $role->localname; // Role fullname.
        }
        return $roles;
    }
}
