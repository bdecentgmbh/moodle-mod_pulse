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

require_once($CFG->dirroot . '/course/moodleform_mod.php');

require_once($CFG->dirroot . '/mod/pulse/lib.php');

/**
 * Pulse module form.
 */
class mod_pulse_mod_form extends moodleform_mod {
    /**
     *
     * @var MoodleQuickForm quickform object definition
     */
    // phpcs:ignore
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
            $loader = $OUTPUT->pix_icon('i/loading', 'loading', 'moodle', ['class' => 'spinner']);
            $mform->addElement('html', '<div id="pulse-presets-data" data-listloaded="false">' . $loader . '</div>');
        }
        // General section.
        $mform->addElement('header', 'general', get_string('general'));

        $mform->addElement('text', 'name', get_string('title', 'pulse'), ['size' => '64']);
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
        \mod_pulse\extendpro::pulse_extend_form($mform, $this, 'fields_before_invitation');

        $mform->addElement('header', 'invitation', get_string('invitation', 'mod_pulse'));

        // Pulse enable / disable option.
        $mform->addElement(
            'advcheckbox',
            'pulse',
            get_string('sendnotificaton', 'pulse'),
            get_string('enable:disable', 'pulse')
        );
        $mform->setType('pulse', PARAM_INT);
        $mform->addHelpButton('pulse', 'sendnotificaton', 'mod_pulse');

        // Use Differnet pulse content for pulse.
        $mform->addElement('submit', 'resend_pulse', get_string('resendnotification', 'pulse'));

        // Use Different notification content.
        $mform->addElement(
            'advcheckbox',
            'diff_pulse',
            get_string('diffnotification', 'pulse'),
            get_string('enable:disable', 'pulse')
        );
        $mform->setType('diff_pulse', PARAM_INT);
        $mform->addHelpButton('diff_pulse', 'diffnotification', 'mod_pulse');

        // First reminder subject.
        $elem = $mform->addElement('text', 'pulse_subject', get_string('invitationsubject', 'pulse'), ['size' => '64']);
        $mform->setType('pulse_subject', PARAM_RAW);
        $mform->addHelpButton('pulse_subject', 'invitationsubject', 'mod_pulse');

        // Pulse content editor.
        $editoroptions = \mod_pulse\helper::get_editor_options();
        $mform->addElement(
            'editor',
            'pulse_content_editor',
            get_string('remindercontent', 'pulse'),
            ['class' => 'fitem_id_templatevars_editor'],
            $editoroptions
        );
        $mform->setType('pulse_content_editor', PARAM_RAW);
        $mform->addHelpButton('pulse_content_editor', 'remindercontent', 'mod_pulse');

        // Email tempalte placholders.
        $PAGE->requires->js_call_amd('mod_pulse/module', 'init', [$CFG->branch]);

        // Presets - JS.
        $section = optional_param('section', 0, PARAM_INT);
        $PAGE->requires->js_call_amd('mod_pulse/preset', 'init', [$this->context->id, $PAGE->course->id, $section]);

        $placeholders = pulse_email_placeholders('content', false);
        $mform->addElement('html', $placeholders);
        // Show intro on course page always.
        $mform->addElement('hidden', 'showdescription', 1);
        $mform->setType('showdescription', PARAM_INT);

        // Include the fields from sub plugins.
        \mod_pulse\extendpro::pulse_extend_form($mform, $this, 'fields_before_appearance');

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

        // Mark as complete button options.
        $this->definition_completionbuttonoption($mform);

        $this->standard_coursemodule_elements();

        // Email placeholders.
        $PAGE->requires->js_call_amd('mod_pulse/vars', 'init');

        // Form submit and cancek buttons.
        $this->add_action_buttons(true, false, null);
    }

    /**
     * Custom completion rules definition.
     *
     * @return void
     */
    public function add_completion_rules() {

        $mform = $this->_form;

        $suffix = $this->get_suffix();

        $mform->addElement('checkbox', 'completionwhenavailable' . $suffix, get_string('completewhenavaialble', 'pulse'));
        $mform->setDefault('completionwhenavailable'  . $suffix, 0);
        $mform->addHelpButton('completionwhenavailable'  . $suffix, 'completewhenavaialble', 'mod_pulse');

        $mform->addElement('checkbox', 'completionself' . $suffix, get_string('completionself', 'pulse'));
        $mform->setDefault('completionself' . $suffix, 0);
        $mform->addHelpButton('completionself' . $suffix, 'completionself', 'mod_pulse');

        $group = [];
        $group[] = $mform->createElement(
            'checkbox',
            'completionapproval' . $suffix,
            '',
            get_string('completionrequireapproval', 'pulse')
        );
        $roles = $this->course_roles();
        $select = $mform->createElement(
            'autocomplete',
            'completionapprovalroles' . $suffix,
            get_string('completionapproverules', 'pulse'),
            $roles
        );
        $select->setMultiple(true);
        $group[] = $select;

        $mform->addGroup($group, 'completionrequireapproval' . $suffix, '', [''], false);
        $mform->addHelpButton('completionrequireapproval' . $suffix, 'completionrequireapproval', 'mod_pulse');

        return ['completionwhenavailable' . $suffix, 'completionrequireapproval' . $suffix, 'completionself' . $suffix];
    }

    /**
     * Get suffix for completion rule form field names.
     * This method is required for proper completion rule handling.
     * Only needed for Moodle versions before 4.3 (branch 403).
     *
     * @return string The suffix to be appended to form field names
     */
    public function get_suffix(): string {
        global $CFG;
        if ($CFG->branch >= 403) {
            // For Moodle 4.3+, check if parent has the method, otherwise return empty string.
            if (method_exists(get_parent_class($this), 'get_suffix')) {
                return parent::get_suffix();
            }
        }
        return '';
    }

    /**
     * Get list of all course and user context roles.
     *
     * @return array $roles list of course roles.
     */
    public function course_roles() {
        global $DB;

        [$insql, $inparam] = $DB->get_in_or_equal([CONTEXT_COURSE, CONTEXT_USER]);
        $sql = "SELECT lvl.id, lvl.roleid, rle.name, rle.shortname
                FROM {role_context_levels} lvl
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
        $suffix = $this->get_suffix();

        return (!empty($data['completionwhenavailable' . $suffix])
                || !empty($data['completionapproval' . $suffix]) || !empty($data['completionself' . $suffix]) );
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

        $suffix = $this->get_suffix();
        $data->completionavailable = isset($data->{'completionwhenavailable' . $suffix}) ? 1 : 0;
        $data->completionself = (isset($data->{'completionself' . $suffix}) && $data->{'completionself' . $suffix} !== '0') ? 1 : 0;
        $data->completionapproval = isset($data->{'completionapproval' . $suffix}) ? 1 : 0;

        if (isset($data->completionapprovalroles)) {
            $data->completionapprovalroles = json_encode($data->completionapprovalroles);
        }

        $data->completionbtnconfirmation = isset($data->completionbtnconfirmation) ? 1 : 0;
        if (isset($data->completionbtn_content_editor)) {
            $data->completionbtn_contentformat = $data->completionbtn_content_editor['format'];
            $data->completionbtn_content = $data->completionbtn_content_editor['text'];
        }

        \mod_pulse\extendpro::pulse_extend_postprocessing($data);
    }

    /**
     * Process the pulse module data before set the default.
     *
     * @param  mixed $defaultvalues default values
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {

        $editoroptions = \mod_pulse\helper::get_editor_options();

        if ($this->current->instance) {
            // Prepare draft item id to store the files.
            $draftitemid = file_get_submitted_draft_itemid('pulse_content');
            $pulsecontent = $defaultvalues['pulse_content'] ?? '';
            $pulsecontentformat = $defaultvalues['pulse_contentformat'] ?? 0;
            $defaultvalues['pulse_content_editor']['text'] =
                                    file_prepare_draft_area(
                                        $draftitemid,
                                        $this->context->id,
                                        'mod_pulse',
                                        'pulse_content',
                                        false,
                                        $editoroptions,
                                        $pulsecontent
                                    );

            $defaultvalues['pulse_content_editor']['format'] = $pulsecontentformat;
            $defaultvalues['pulse_content_editor']['itemid'] = $draftitemid;

            $contentdraftitemid = file_get_submitted_draft_itemid('completionbtn_content');
            $content = $defaultvalues['completionbtn_content'] ?? '';
            $contentformat = $defaultvalues['completionbtn_contentformat'] ?? 0;
            $defaultvalues['completionbtn_content_editor']['text'] =
                                    file_prepare_draft_area(
                                        $contentdraftitemid,
                                        $this->context->id,
                                        'mod_pulse',
                                        'completionbtn_content',
                                        false,
                                        $editoroptions,
                                        $content
                                    );
            $defaultvalues['completionbtn_content_editor']['format'] = $contentformat;
            $defaultvalues['completionbtn_content_editor']['itemid'] = $contentdraftitemid;
        } else {
            $draftitemid = file_get_submitted_draft_itemid('pulse_content_editor');
            file_prepare_draft_area($draftitemid, null, 'mod_pulse', 'pulse_content', false);
            $defaultvalues['pulse_content_editor']['format'] = editors_get_preferred_format();
            $defaultvalues['pulse_content_editor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('completionbtn_content_editor');
            file_prepare_draft_area($draftitemid, null, 'mod_pulse', 'completionbtn_content', false);
            $defaultvalues['completionbtn_content_editor']['format'] = editors_get_preferred_format();
            $defaultvalues['completionbtn_content_editor']['itemid'] = $draftitemid;
        }

        // Set up the completion checkbox which is not part of standard data.
        $suffix = $this->get_suffix();
        $defaultvalues['completionwhenavailable' . $suffix] = !empty($defaultvalues['completionavailable']) ? 1 : 0;
        if (isset($defaultvalues['completionapprovalroles'])) {
            $defaultvalues['completionapprovalroles' . $suffix] = is_array($defaultvalues['completionapprovalroles'])
                ? $defaultvalues['completionapprovalroles'] : json_decode($defaultvalues['completionapprovalroles']);
        }
        if (isset($defaultvalues['completionself'])) {
            $defaultvalues['completionself' . $suffix] = $defaultvalues['completionself'];
        }

        $defaultvalues['resend_pulse'] = get_string('resendnotification', 'pulse');

        if (!empty($defaultvalues['id'])) {
            $defaultvalues['options'] = mod_pulse\options::init($defaultvalues['id'])->get_options();
        }
        // Pre pocessing extend.
        \mod_pulse\extendpro::pulse_extend_preprocessing($defaultvalues, $this->current->instance, $this->context);
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

        $extenderrors = \mod_pulse\extendpro::pulse_extend_form($this->_form, $this, 'validation', [$data, $files]);
        if (is_array($extenderrors)) {
            $errors = array_merge($errors, $extenderrors);
        }
        return $errors;
    }

    /**
     * Mark as completion option form fields.
     *
     * @param moodle_form $mform
     * @return void
     */
    public function definition_completionbuttonoption(&$mform) {
        // Head: Mark as complete options.
        $mform->addElement('header', 'markcompleteoption', get_string('markcompleteoptionheader', 'pulse'));

        // Global config values.
        $context = \context_system::instance();
        $completebtnconfirmation = get_config('mod_pulse', 'completionbtnconfirmation');
        $completionbtntext = get_config('mod_pulse', 'completionbtntext');
        $completionbtncontent = get_config('mod_pulse', 'completionbtn_content');

        $btncontenthtml = file_rewrite_pluginfile_urls(
            $completionbtncontent,
            'pluginfile.php',
            $context->id,
            'mod_pulse',
            'completionbtn_content',
            0
        );
        $btncontenthtml = format_text($btncontenthtml, FORMAT_HTML, ['trusted' => true, 'noclean' => true]);

        // Require confirmation.
        $mform->addElement('checkbox', 'completionbtnconfirmation', get_string('requireconfirm', 'pulse'));
        $mform->addHelpButton('completionbtnconfirmation', 'requireconfirm', 'mod_pulse');
        $mform->setDefault('completionbtnconfirmation', $completebtnconfirmation ?: false);

        // Marke as complete button text.
        $btntexts = [
            BUTTON_TEXT_DEFAULT => get_string('markcompletebtnstring_default', 'pulse'),
            BUTTON_TEXT_ACKNOWLEDGE => get_string('markcompletebtnstring_custom1', 'pulse'),
            BUTTON_TEXT_CONFIRM => get_string('markcompletebtnstring_custom2', 'pulse'),
            BUTTON_TEXT_CHOOSE => get_string('markcompletebtnstring_custom3', 'pulse'),
            BUTTON_TEXT_APPROVE => get_string('markcompletebtnstring_custom4', 'pulse'),
        ];
        $mform->addElement('select', 'completionbtntext', get_string('btntext', 'pulse'), $btntexts);
        $mform->setType('completionbtntext', PARAM_TEXT);
        $mform->addHelpButton('completionbtntext', 'btntext', 'mod_pulse');
        $mform->setDefault('completionbtntext', $completionbtntext ?: BUTTON_TEXT_DEFAULT);

        // Confirmation modal text.
        $editoroptions = \mod_pulse\helper::get_editor_options();
        $content = $mform->addElement(
            'editor',
            'completionbtn_content_editor',
            get_string('confirmtext', 'pulse'),
            ['class' => 'fitem_id_templatevars_editor'],
            $editoroptions
        );
        $mform->setType('completionbtn_content_editor', PARAM_RAW);
        $mform->addHelpButton('completionbtn_content_editor', 'confirmtext', 'mod_pulse');
        $content->setValue(['text' => $btncontenthtml ?? '', 'format' => 1]);
    }
}
