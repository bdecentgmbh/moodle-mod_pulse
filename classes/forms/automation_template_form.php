<?php


namespace mod_pulse\forms;

defined('MOODLE_INTERNAL') || die('No direct access!');

require_once($CFG->dirroot.'/lib/formslib.php');

use Google_Service_Compute_AutoscalingPolicyCustomMetricUtilization;
use html_writer;
use mod_pulse\automation\helper;
use moodleform;
use mod_pulse\automation\templates;

// Define the automation template form class by extending moodleform.
class automation_template_form extends moodleform {

    // Define the form elements inside the definition function.
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Get the form instance.

        $mform->addElement('html', '<ul class="nav nav-tabs mb-3" id="registration-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tool-details-tab" data-toggle="tab" href="#autotemplate-general" role="tab" aria-controls="tooldetails">
                    General
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pulse-action-tab2" data-toggle="tab" href="#pulse-condition-tab" role="tab" aria-controls="pulse-condition-tab" >
                    Condition
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pulse-notification-tab" data-toggle="tab" href="#pulse-notification-content" role="tab" aria-controls="pulse-notification-tab" >
                    Notifications
                </a>
            </li>
        </ul>');

        // Set the id of template.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Template options.
        $this->load_template_options($mform);

        // Template conditions.
        $this->load_template_conditions($mform);

        // Load template actions.
        $this->load_template_actions($mform);

        // Add standard form buttons.
        $this->add_action_buttons(true);
    }

    protected function load_template_options() {

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="tab-content" id="pulsetemplates-tab-content">  <div class="tab-pane fade show active" id="autotemplate-general">');

        // Add the Title element.
        $mform->addElement('text', 'title', get_string('title', 'pulse'), ['size' => '50']);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);

        // Add the Reference element.
        $mform->addElement('text', 'reference', get_string('reference', 'pulse'), ['size' => '50']);
        $mform->addRule('reference', null, 'required', null, 'client');
        $mform->setType('reference', PARAM_TEXT);

        // Add the Visibility element.
        $visibility_options = [
            templates::VISIBILITY_SHOW => get_string('show', 'pulse'),
            templates::VISIBILITY_HIDDEN => get_string('hidden', 'pulse'),
        ];
        $mform->addElement('select', 'visible', get_string('visibility', 'pulse'), $visibility_options);

        // Add the Internal Notes element.
        $mform->addElement('text', 'notes', get_string('internalnotes', 'pulse'), ['size' => '50']);
        $mform->setType('notes', PARAM_TEXT);

        // Add the Status element.
        $status_options = [
            templates::STATUS_ENABLE => get_string('enabled', 'pulse'),
            templates::STATUS_DISABLE => get_string('disabled', 'pulse'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'pulse'), $status_options);


        // Add the Tags element.
        $tags_options = [
            'itemtype' => 'automation_templates',
            'component' => 'mod_pulse'
        ]; // Populate with the available tags for administrative purposes.
        $mform->addElement('tags', 'tags', get_string('tags', 'pulse'), $tags_options, ['multiple' => 'multiple']);

        // Add the Available for Tenants element.
        $tenants_options = []; // Populate with the available tenants for Moodle Workplace.
        $mform->addElement('autocomplete', 'tenants', get_string('availablefortenants', 'pulse'), $tenants_options, ['multiple' => 'multiple']);

        // Add the Available in Course Categories element.
        $categories = \core_course_category::make_categories_list();
        $cate = $mform->addElement('autocomplete', 'categories', get_string('availableincoursecategories', 'pulse'), $categories);
        $mform->setType('category', PARAM_INT);
        $cate->setMultiple(true);

        $mform->addElement('html', html_writer::end_div());
    }

    /**
     * Includ the template action trigger element to the templates form.
     *
     * @param [type] $mform
     * @return void
     */
    protected function load_template_conditions() {

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="tab-pane fade" id="pulse-condition-tab"> ');

        $conditionplugins = new \mod_pulse\plugininfo\pulsecondition();
        $plugins = $conditionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->include_action($option);
        }

        $condition = $mform->addElement('autocomplete', 'triggerconditions', get_string('conditiontrigger', 'pulse'), $option);
        $condition->setMultiple(true);

        // Operator element.
        $operators = [
            \mod_pulse\automation\action_base::OPERATOR_ALL => get_string('all', 'pulse'),
            \mod_pulse\automation\action_base::OPERATOR_ANY => get_string('any', 'pulse'),
        ];
        $mform->addElement('select', 'triggeroperator', get_string('triggeroperator', 'pulse'), $operators);

        $mform->addElement('html', html_writer::end_div()); // E.o of actions triggere tab.
    }

    /**
     * Process the pulse module data before set the default.
     *
     * @param  mixed $defaultvalues default values
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        $context = \context_system::instance();

        if (isset($this->_customdata['courseid'])) {
            $courseid = $this->_customdata['courseid'];
            $context = \context_course::instance($courseid);
        }
        // Prepare the editor to support files.
        helper::prepare_editor_draftfiles($defaultvalues, $context);
    }

    /**
     * Prepare the data after form was submited.
     *
     * @param  mixed $data submitted data
     * @return void
     */
    /* public function data_postprocessing($data) {
        print_object($data);
        exit;
        $context = \context_system::instance();
        // Prepare the editor to support files.
        helper::postupdate_editor_draftfiles($data, $context);
    }
 */
    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * Do not override this method, override data_postprocessing() instead.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
/*     public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $this->data_postprocessing($data);
        }
        return $data;
    } */

    /**
     * Load in existing data as form defaults. Usually new entry defaults are stored directly in
     * form definition (new entry form); this function is used to load in data where values
     * already exist and data is being edited (edit entry form).
     *
     * note: $slashed param removed
     *
     * @param stdClass|array $default_values object or array of default values
     */
    function set_data($default_values) {

        $this->data_preprocessing($default_values); // Include to store the files.

        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        $this->_form->setDefaults($default_values);
    }

    /**
     * Load template actions.
     *
     * @param [type] $mform
     * @return void
     */
    protected function load_template_actions(&$mform) {

        $actionplugins = new \mod_pulse\plugininfo\pulseaction();
        $plugins = $actionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->load_global_form($mform, $this);
        }
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
