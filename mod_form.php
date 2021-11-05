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

/**
 * Pulse module form.
 */
class mod_pulse_mod_form extends moodleform_mod {

    /**
     *
     * @var MoodleQuickForm quickform object definition
     */
    public $_form;

    /**
     * Pulse module add/update form fields are defined here.
     * Basic form fields added and extended the module standard default fields.
     *
     * @return void
     */
    public function definition() {
        global $DB, $PAGE, $CFG, $OUTPUT;

        $mform = $this->_form;
        $mform->updateAttributes(['id' => 'mod-pulse-form']);
        if (!isset($this->current->instance) || $this->current->instance == '') {
            // Presets header.
            $mform->addElement('header', 'presets_header', get_string('presets', 'pulse'));
            $loader = $OUTPUT->pix_icon('i/loading', 'loading', 'moodle', array('class' => 'spinner'));
            $mform->addElement('html', '<div id="pulse-presets-data" data-listloaded="false">'.$loader.'</div>');
        }
        // General section.
        $mform->addElement('header', 'general', get_string('general') );

        $mform->addElement('text', 'name', get_string('title', 'pulse'), array('size' => '64'));
        $mform->addRule('name', get_string('error'), 'required', '', 'client');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addHelpButton('name', 'title', 'mod_pulse');

        $this->standard_intro_elements(get_string('content', 'pulse'));
        $mform->addRule('introeditor', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('introeditor', 'content', 'mod_pulse');

        // Extend the reaction sections.
        mod_pulse_extend_form($mform, $this, 'reaction');

        $mform->addElement('header', 'invitation', get_string('invitation', 'mod_pulse'));

        // Pulse enable / disable option.
        $mform->addElement('advcheckbox', 'pulse', get_string('sendnotificaton', 'pulse'),
        get_string('enable:disable', 'pulse'));
        $mform->setType('pulse', PARAM_INT);
        $mform->addHelpButton('pulse', 'sendnotificaton', 'mod_pulse');

        // Use Differnet pulse content for pulse.
        $resend = $mform->addElement('submit', 'resend_pulse', get_string('resendnotification', 'pulse'));
        $mform->addHelpButton('resend_pulse', 'resendnotification', 'mod_pulse');

        // Use Different notification content.
        $mform->addElement('advcheckbox', 'diff_pulse', get_string('diffnotification', 'pulse'),
        get_string('enable:disable', 'pulse'));
        $mform->setType('diff_pulse', PARAM_INT);
        $mform->addHelpButton('diff_pulse', 'diffnotification', 'mod_pulse');

        // First reminder subject.
        $elem = $mform->addElement('text', 'pulse_subject', get_string('invitationsubject', 'pulse'), array('size' => '64'));
        $mform->setType('pulse_subject', PARAM_RAW);
        $mform->addHelpButton('pulse_subject', 'invitationsubject', 'mod_pulse');

        // Pulse content editor.
        $editoroptions  = pulse_get_editor_options();
        $mform->addElement('editor', 'pulse_content_editor', get_string('remindercontent', 'pulse'),
            ['class' => 'fitem_id_templatevars_editor'], $editoroptions);
        $mform->setType('pulse_content_editor', PARAM_RAW);
        $mform->addHelpButton('pulse_content_editor', 'remindercontent', 'mod_pulse');

        // Email tempalte placholders.
        $PAGE->requires->js_call_amd('mod_pulse/module', 'init');

        // Presets - JS.
        $section = optional_param('section', 0, PARAM_INT);
        $PAGE->requires->js_call_amd('mod_pulse/preset', 'init', [$this->context->id, $PAGE->course->id, $section]);

        $this->pulse_email_placeholders($mform);
        // Show intro on course page always.
        $mform->addElement('hidden', 'showdescription', 1);
        $mform->setType('showdescription', PARAM_INT);

        mod_pulse_extend_form($mform, $this);

        $mform->addElement('header', 'appearance', get_string('appearance', 'core'));

        $mform->addElement('text', 'cssclass', get_string('cssclass', 'pulse'));
        $mform->setType('cssclass', PARAM_ALPHAEXT);

        $modes = [0 => get_string('normal', 'pulse'), 1 => get_string('box', 'pulse')];
        $mform->addElement('select', 'displaymode', get_string('displaymode', 'pulse'), $modes);
        $mform->setType('displaymode', PARAM_TEXT);

        $boxtypes = [
            'primary' => get_string('primary', 'pulse'),
            'secondary' => get_string('secondary', 'pulse'),
            'danger' => get_string('danger', 'pulse'),
            'warning' => get_string('warning', 'pulse'),
            'light ' => get_string('light', 'pulse'),
            'dark ' => get_string('dark', 'pulse'),
            'success ' => get_string('success', 'pulse'),
        ];
        $mform->addElement('select', 'boxtype', get_string('boxtype', 'pulse'), $boxtypes);
        $mform->setType('boxtype', PARAM_TEXT);
        $mform->hideIf('boxtype', 'displaymode', 'neq', 1);

        // Preset Icon.
        $theme = \theme_config::load($PAGE->theme->name);
        $faiconsystem = \core\output\icon_system_fontawesome::instance($theme->get_icon_system());
        $iconlist = $faiconsystem->get_core_icon_map();
        array_unshift($iconlist, '');
        $mform->addElement('autocomplete', 'boxicon', get_string('boxicon', 'pulse'), $iconlist);
        $mform->setType('boxicon', PARAM_TEXT);
        $mform->hideIf('boxicon', 'displaymode', 'neq', 1);

        $this->standard_coursemodule_elements();
        // Form submit and cancek buttons.
        $this->add_action_buttons(true, false, null);
    }

    /**
     * Add email placeholder fields in form fields.
     *
     * @param  mixed $mform
     * @return void
     */
    public function pulse_email_placeholders(&$mform) {
        $vars = \EmailVars::vars();
        $mform->addElement('html', "<div class='form-group row fitem'> <div class='col-md-3'></div>
        <div class='col-md-9'><div class='emailvars'>");
        $optioncount = 0;
        foreach ($vars as $option) {
            $mform->addElement('html', "<a href='#' data-text='$option' class='clickforword'><span>$option</span></a>");
            $optioncount++;
        }
        $mform->addElement('html', "</div></div></div>");
    }

    /**
     * Custom completion rules definition.
     *
     * @return void
     */
    public function add_completion_rules() {

        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionwhenavailable', get_string('completewhenavaialble', 'pulse') );
        $mform->setDefault('completionwhenavailable', 0);
        $mform->addHelpButton('completionwhenavailable', 'completewhenavaialble', 'mod_pulse');

        $mform->addElement('checkbox', 'completionself', get_string('completionself', 'pulse') );
        $mform->setDefault('completionself', 0);
        $mform->addHelpButton('completionself', 'completionself', 'mod_pulse');

        $group = array();
        $group[] = $mform->createElement('checkbox', 'completionapproval', '',
                    get_string('completionrequireapproval', 'pulse'));
        $roles = $this->course_roles();
        $select = $mform->createElement('autocomplete', 'completionapprovalroles',
        get_string('completionapproverules', 'pulse'), $roles);
        $select->setMultiple(true);
        $group[] = $select;

        $mform->addGroup($group, 'completionrequireapproval', '', [''], false );
        $mform->addHelpButton('completionrequireapproval', 'completionrequireapproval', 'mod_pulse');

        return ['completionwhenavailable', 'completionrequireapproval', 'completionself'];
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

    /**
     * Validate the form to check custom completion has selected conditions.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionwhenavailable'])
                || !empty($data['completionapproval']) || !empty($data['completionself']) );
    }

    /**
     * Prepare the data after form was submited.
     *
     * @param  mixed $data submitted data
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (isset($data->pulse_content_editor)) {
            $data->pulse_contentformat = $data->pulse_content_editor['format'];
            $data->pulse_content = $data->pulse_content_editor['text'];
        }
        $data->completionavailable = isset($data->completionwhenavailable) ? 1 : 0;
        $data->completionself = isset($data->completionself) ? 1 : 0;
        $data->completionapproval = isset($data->completionapproval) ? 1 : 0;

        if (isset($data->completionapprovalroles)) {
            $data->completionapprovalroles = json_encode($data->completionapprovalroles);
        }
        pulse_extend_postprocessing($data);
    }

    /**
     * Process the pulse module data before set the default.
     *
     * @param  mixed $defaultvalues default values
     * @return void
     */
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
            $defaultvalues['pulse_content_editor']['format'] = editors_get_preferred_format();
            $defaultvalues['pulse_content_editor']['itemid'] = $draftitemid;
        }

        // Set up the completion checkbox which is not part of standard data.
        $defaultvalues['completionwhenavailable'] =
            !empty($defaultvalues['completionavailable']) ? 1 : 0;

        if (isset($defaultvalues['completionapprovalroles'])) {
            $defaultvalues['completionapprovalroles'] = json_decode($defaultvalues['completionapprovalroles']);
        }

        $defaultvalues['resend_pulse'] = get_string('resendnotification', 'pulse');
        // Pre pocessing extend.
        pulse_extend_preprocessing($defaultvalues, $this->current->instance, $this->context);
    }

    /**
     * Validate the submited form data.
     *
     * @param  mixed $data submitted form data.
     * @param  mixed $files submitted editor files.
     * @return array $erros List of errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['diff_pulse']) {
            if (empty($data['pulse_subject'])) {
                $errors['pulse_subject'] = get_string('required');
            }

            if (empty($data['pulse_content_editor']['text'])) {
                $errors['pulse_content_editor'] = get_string('required');
            }
        }

        $extenderrors = mod_pulse_extend_formvalidation($data, $files);
        if (is_array($extenderrors)) {
            $errors = array_merge($errors, $extenderrors);
        }
        return $errors;
    }
}
