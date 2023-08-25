<?php


namespace mod_pulse\forms;

defined('MOODLE_INTERNAL') || die('No direct access!');

require_once($CFG->dirroot.'/lib/formslib.php');

use html_writer;
use mod_pulse\automation\templates;

// Define the automation template form class by extending moodleform.
class automation_instance_form extends automation_template_form {


    public function after_definition() {
        global $PAGE;

        // parent::definition();

        $mform =& $this->_form;

        $course = $this->_customdata['courseid'] ?? '';
        $mform->addElement('hidden', 'courseid', $course);
        $mform->setType('courseid', PARAM_INT);

        $templateid = $this->_customdata['templateid'] ?? '';
        $mform->addElement('hidden', 'templateid', $templateid);
        $mform->setType('templateid', PARAM_INT);

        $templateid = $this->_customdata['instanceid'] ?? '';
        $mform->addElement('hidden', 'instanceid', $templateid);
        $mform->setType('instanceid', PARAM_INT);


        // Get the list of elments add in this form. create override button for all elements expect the hidden elements.
        $elements = $mform->_elements;

        if (!empty($elements)) {
            // List of element type don't need to add the override option.
            $dontoverride = ['html', 'header', 'hidden', 'button'];
            foreach ($elements as $element) {
                // $nextelement = next($elements);

                if (!in_array($element->getType(), $dontoverride) && $element->getName() !== 'buttonar') {
                    $this->add_override_element($element);
                }
            }
        }

    }

    protected function add_override_element($element) {
        $mform =& $this->_form;

        $elementname = $element->getName();
        $orgelementname = $elementname;

        /* // Rename the editor elements.
        if (str_ends_with($elementname, '_editor')) {
            $elementname = str_replace('_editor','', $elementname);
        } */

        if (stripos($elementname, "[") !== false) {
            $name = str_replace("]", "", str_replace("[", "_", $elementname));
            $name = 'override[' . $name .']';
        } else {
            $name = 'override[' . $elementname .']';
        }

        $overrideelement = $mform->createElement('advcheckbox', $name, '', 'Override', array('group' => 1, 'class' => 'wrap'), array(0, 1));

        // Insert the override checkbox before the element.
        if (isset($mform->_elementIndex[$orgelementname]) && $mform->_elementIndex[$orgelementname]) {
            $mform->insertElementBefore($overrideelement, $orgelementname);
        }

        // Disable the form fields by default, only enable whens its enabled for overriddden.
        if (!isset($mform->_rules[$element->getName()]) || empty($mform->_rules[$element->getName()])) {
            $mform->disabledIf($orgelementname, $name, 'notchecked');
        }
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

        $mform->addElement('html', '<h3>'.get_string('general').'</h3>');

        // Operator element.
        $operators = [
            \mod_pulse\automation\action_base::OPERATOR_ALL => get_string('all', 'pulse'),
            \mod_pulse\automation\action_base::OPERATOR_ANY => get_string('any', 'pulse'),
        ];
        $mform->addElement('select', 'triggeroperator', get_string('triggeroperator', 'pulse'), $operators);

        $conditionplugins = new \mod_pulse\plugininfo\pulsecondition();
        $plugins = $conditionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->load_instance_form($mform, $this);
        }

        $mform->addElement('html', html_writer::end_div()); // E.o of actions triggere tab.

    }


    /**
     * Load template actions.
     *
     * @param [type] $mform
     * @return void
     */
    protected function load_template_actions(&$mform) {

        // $mform->addElement('html', '<div class="tab-pane fade" id="pulse-action-tab"> ');


        $actionplugins = new \mod_pulse\plugininfo\pulseaction();
        $plugins = $actionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->load_instance_form($mform, $this);
        }

        // $mform->addElement('html', html_writer::end_div()); // E.o of actions triggere tab.

    }

    public function get_customdata($key) {
        return $this->_customdata[$key] ?? '';
    }

    public function validation($data, $files) {

        /*
        $data.
        print_object($data);
        exit;
        */
    }

    /**
     * Load template actions.
     *
     * @param [type] $mform
     * @return void
     */
    /* protected function load_template_actions(&$mform) {

        $actionplugins = new \mod_pulse\plugininfo\pulseaction();
        $plugins = $actionplugins->get_plugins_base();

        $option = [];
        foreach ($plugins as $name => $plugin) {
            $plugin->load_global_form($mform, $this);
        }
    } */

}
