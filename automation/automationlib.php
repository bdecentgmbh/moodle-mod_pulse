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
        $templates = mod_pulse\automation\helper::get_templates_forinstance($courseid);
        if (!empty($templates)) {
            $mform->addElement('select', 'templateid', '', $templates);
        }

        $this->add_action_buttons(false, get_string('addtemplatebtn', 'pulse'));
    }
}

/**
 * Course context class to create a context_course instance from record
 */
class mod_pulse_context_course extends \context_course {

    public static function create_instance_fromrecord($data) {
        return \context::create_instance_from_record($data);
    }
}
