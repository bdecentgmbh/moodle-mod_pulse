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
 * Pulse module form definition.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');

class mod_pulse_mod_form extends moodleform_mod {

    public function definition() {
        global $DB, $PAGE;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general') );
        $this->standard_intro_elements(get_string('content', 'pulse'));
        // Pulse enable / disable option.
        $mform->addElement('advcheckbox', 'pulse', get_string('sendnotificaton', 'pulse'),
        get_string('enable:disable', 'pulse') );
        $mform->setType('pulse', PARAM_INT);
        // Use Differnet pulse content for pulse.
        $mform->addElement('advcheckbox', 'resend_pulse', get_string('resendnotification', 'pulse'),
        get_string('enable:disable', 'pulse'));
        $mform->setType('resend_pulse', PARAM_INT);
        $mform->hideIf('resend_pulse', 'pulse', 'notchecked');

        $mform->addElement('advcheckbox', 'diff_pulse', get_string('diffnotification', 'pulse'),
        get_string('enable:disable', 'pulse'));
        $mform->setType('diff_pulse', PARAM_INT);
        $mform->hideIf('diff_pulse', 'pulse', 'notchecked');
        // Pulse content editor.
        $editoroptions  = pulse_get_editor_options();
        $mform->addElement('editor', 'pulse_content_editor', get_string('pulsenotification', 'pulse'),
        ['class' => 'fitem_id_templatevars_editor'], $editoroptions);
        $mform->setType('pulse_content_editor', PARAM_RAW);
        // Email tempalte placholders.
        $PAGE->requires->jquery();
        $PAGE->requires->js('/mod/pulse/module.js');
        $vars = \EmailVars::vars();
        $mform->addElement('html', "<div class='form-group row  fitem'> <div class='col-md-3'></div>
        <div class='col-md-9'><div class='emailvars '><div class=''>");
        $optioncount = 0;
        foreach ($vars as $option) {
            $mform->addElement('html', "<a href='#' data-text='$option' class='clickforword'><span>$option</span></a>");
            $optioncount++;
        }
        $mform->addElement('html', "</div></div></div>");

        // Show intro on course page always.
        $mform->addElement('hidden', 'showdescription', 1);
        $mform->setType('showdescription', PARAM_INT);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }

    public function add_completion_rules() {

        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionwhenavailable', get_string('completewhenavaialble', 'pulse') );
        $mform->setDefault('completionwhenavailable', 0);

        $mform->addElement('checkbox', 'completionself', get_string('completionself', 'pulse') );
        $mform->setDefault('completionself', 0);

        $group = array();
        $group[] = $mform->createElement('checkbox', 'completionapproval', '', get_string('completionrequireapproval', 'pulse'));
      
        $roles = $this->course_roles();
        $select = $mform->createElement('select', 'completionapprovalroles', get_string('completionapproverules', 'pulse'), $roles);
        $select->setMultiple(true);
        $group[] = $select;

        $mform->addGroup($group, 'completionrequireapproval', get_string('completionrequireapprovalgroup', 'pulse'), [''], false );

        return ['completionwhenavailable', 'completionrequireapproval', 'completionself'];
    }

    public function course_roles() {
        global $DB;
        list($insql, $inparam) = $DB->get_in_or_equal([CONTEXT_COURSE, CONTEXT_USER]);
        $sql = "SELECT lvl.roleid, rle.shortname FROM {role_context_levels} lvl
        JOIN {role} AS rle ON rle.id = lvl.roleid
        WHERE contextlevel $insql ";
        return $DB->get_records_sql_menu($sql, $inparam);        
    }

    /**
     * Validate the form to check custom completion has selected conditions.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionwhenavailable']) || !empty($data['completionapproval']) || !empty($data['completionself']) );
    }



    /**
     * Prepare the data after form was submited.
     *
     * @param  mixed $data
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (isset($data->pulse_content_editor)) {
            $data->pulse_contentformat = $data->pulse_content_editor['format'];
            $data->pulse_content = $data->pulse_content_editor['text'];
        }
        $data->completionavailable = isset($data->completionwhenavailable) ? 1 : 0;
        if (isset($data->completionapprovalroles)) {
            $data->completionapprovalroles = json_encode($data->completionapprovalroles);
        }
    }

    public function data_preprocessing(&$defaultvalues) {

        $editoroptions = pulse_get_editor_options();

        if ($this->current->instance) {
            // Prepare draft item id to store the files.
            $draftitemid = file_get_submitted_draft_itemid('pulse_content');
            $defaultvalues['pulse_content_editor']['text'] =
                                    file_prepare_draft_area($draftitemid, $this->context->id,
                                    'mod_pulse', 'pulse_content', false,
                                    $editoroptions,
                                    $defaultvalues['pulse_content']);

            $defaultvalues['pulse_content_editor']['format'] = $defaultvalues['pulse_contentformat'];
            $defaultvalues['pulse_content_editor']['itemid'] = $draftitemid;
        } else {
            $draftitemid = file_get_submitted_draft_itemid('pulse_content_editor');
            file_prepare_draft_area($draftitemid, null, 'mod_pulse', 'pulse_content', false);
            $defaultvalues['pulse_content_editor']['text'] = '';
            $defaultvalues['pulse_content_editor']['format'] = editors_get_preferred_format();
            $defaultvalues['pulse_content_editor']['itemid'] = $draftitemid;
        }

        // Set up the completion checkbox which is not part of standard data.
        $defaultvalues['completionwhenavailable'] =
            !empty($defaultvalues['completionavailable']) ? 1 : 0;

        $defaultvalues['completionapprovalroles'] = (isset($defaultvalues['completionapprovalroles'])) ? json_decode($defaultvalues['completionapprovalroles']) : '';
    }
}