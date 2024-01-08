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
 * Notification pulse action - Automation lib.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $templates = [0 => ''] + mod_pulse\automation\helper::get_templates_forinstance($courseid);
        if (!empty($templates)) {
            $mform->addElement('autocomplete', 'templateid', '', $templates);
        }

        $this->add_action_buttons(true, get_string('addtemplatebtn', 'pulse'));
    }

    /**
     * validation
     *
     * @param  mixed $data
     * @param  mixed $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['templateid'])) {
            $errors['templateid'] = get_string('required');
        }

        return $errors;

    }
}

/**
 * Filter form for the templates table.
 */
class template_table_filter extends \moodleform {

    /**
     * Filter form elements defined.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', html_writer::tag('h3', get_string('filter')));
        $list = [0 => get_string('all')] + core_course_category::make_categories_list();
        $mform->addElement('autocomplete', 'category', get_string('category'), $list);

        $this->add_action_buttons(false, get_string('filter'));
    }
}

/**
 * Course context class to create a context_course instance from record.
 */
class mod_pulse_context_course extends \context_course {

    /**
     * Convert the record of context into course_context object.
     *
     * @param stdclass $data
     * @return void
     */
    public static function create_instance_fromrecord($data) {
        return \context::create_instance_from_record($data);
    }
}
