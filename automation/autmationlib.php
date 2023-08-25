<?php

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Template add instance form.
 */
class template_addinstance_form extends \moodleform {

    /**
     * Definition of the form elements.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->updateAttributes(['class' => 'form-inline']);

        // Current course id.
        $courseid = $this->_customdata['courseid'] ?? 0;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // List of templates to create instance.
        $templates = mod_pulse\automation\helper::get_templates_forinstance();
        $mform->addElement('select', 'templateid', '', $templates);

        $this->add_action_buttons(false, get_string('addtemplatebtn', 'pulse'));
    }
}


class mod_pulse_context_course extends \context_course {

    public static function create_instance_fromrecord($data) {
        return new self($data);
    }
}
