<?php


namespace mod_pulse\automation;

use mod_pulse\plugininfo\pulseaction;
use moodle_url;
use single_button;

class helper {


    public static function get_templates_forinstance($courseid=null) {
        global $DB;

        $course = get_course($courseid);

        // $templates = $DB->get_records_menu('pulse_autotemplates', ['status' => 1, 'visible' => 1]);

        $like = $DB->sql_like('categories', ':value');
        $sql = "SELECT * FROM {pulse_autotemplates} WHERE (categories = '[]' OR categories = '' OR $like) AND status = 1 AND visible = 1";
        $params = ['value' => '%"'.$course->category.'"%'];

        $records = $DB->get_records_sql_menu($sql, $params);

        array_walk($records, function(&$val) {
            $val = format_string($val);
        });

        return $records;
    }

    public static function merge_instance_overrides($overridedata, $templatedata) {
        // Filter the empty values.
        $filtered = array_filter((array) $overridedata, function($value) {
            return $value !== null;
        });
        // Merge the templatedata with filterdata.
        $filtered = array_merge((array) $templatedata, $filtered);

        return $filtered;
    }

    public static function filter_record_byprefix($record, $prefix) {

        // Filter the data based on the shortname.
        $filtered = array_filter((array) $record, function($key) use ($prefix) {
            return strpos($key, $prefix.'_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        // Remove the prefix from the keys.
        $removedprefix = array_map(function($key) use ($prefix) {
            return str_replace($prefix."_", '', $key);
        }, array_keys($filtered));

        // Combine the filtered values with prefix removed keys.
        $final = array_combine(array_values($removedprefix), array_values($filtered));

        return $final;
    }

    public static function get_actions() {
        return \mod_pulse\plugininfo\pulseaction::get_list();
    }

    public static function get_conditions() {
        return \mod_pulse\plugininfo\pulsecondition::get_list();
    }

    public static function prepare_editor_draftfiles(&$data, $context) {

        $actions = self::get_actions();
        foreach ($actions as $key => $action) {
            $action->prepare_editor_fileareas($data, $context);
        }
    }

    public static function postupdate_editor_draftfiles(&$data, $context) {

        $actions = self::get_actions();
        foreach ($actions as $key => $action) {
            $action->postupdate_editor_fileareas($data, $context);
        }
    }

    public static function get_course_instances($courseid) {
        global $DB;

        $list = $DB->get_records('pulse_autoinstances', ['courseid' => $courseid]);

        return $list;
    }

    /**
     * Insert the additional module fields data to the table.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param string $name Field name.
     * @param mixed $value value of the field.
     * @return void
     */
    public static function update_instance_option($tablename, int $instanceid,  $options) {
        global $DB;

        $records = [];

        foreach ($options as $name => $value) {

            if ($DB->record_exists($tablename, ['instanceid' => $instanceid, 'name' => $name])) {
                $DB->set_field($tablename, 'value', $value, ['instanceid' => $instanceid, 'name' => $name]);
            } else {
                $record = new \stdClass;
                $record->instanceid = $instanceid;
                $record->name = $name;
                $record->value = $value ?: '';
                $record->isoverridden = true; // Update overridden.
                // Store to the list then insert at once after all the creations.
                $records[$name] = $record;
            }
        }

        if (isset($records) && !empty($records)) {
            $DB->insert_records($tablename, $records);
        }
    }

    /**
     * Generate the button which is displayed on top of the templates table. Helps to create templates.
     *
     * @return string The HTML contents to display the create templates button.
     */
    public static function template_buttons($filtered=false) {
        global $OUTPUT, $DB, $CFG;

        require_once($CFG->dirroot. '/mod/pulse/automation/automationlib.php');


        // Setup create template button on page.
        $caption = get_string('templatecreatenew', 'pulse');
        $editurl = new moodle_url('/mod/pulse/automation/templates/edit.php', ['sesskey' => sesskey()]);

        // IN Moodle 4.2, primary button param depreceted.
        $primary = defined('single_button::BUTTON_PRIMARY') ? single_button::BUTTON_PRIMARY : true;
        $button = new single_button($editurl, $caption, 'get', $primary);
        $button = $OUTPUT->render($button);

        // Filter form.
        $button .= \html_writer::start_div('filter-form-container');
        $button .= \html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('i/filter', 'Filter'), [
            'id' => 'pulse-automation-filter',
            'class' => 'sort-autotemplates btn btn-primary ml-2 ' . ($filtered ? 'filtered' : '')
        ]);
        $filter = new \template_table_filter();
        $button .= \html_writer::tag('div', $filter->render(), ['id' => 'pulse-automation-filterform', 'class' => 'hide']);
        $button .= \html_writer::end_div();

        // Sort button for the table. Sort y the reference.
        $tdir = optional_param('tdir', null, PARAM_INT);
        $tdir = ($tdir == SORT_ASC) ? SORT_DESC : SORT_ASC;
        $dirimage = ($tdir == SORT_ASC) ? '<i class="fa fa-sort-amount-up"></i>' : $OUTPUT->pix_icon('t/sort_by', 'Sortby');

        $manageurl = new moodle_url('/mod/pulse/automation/templates/list.php', [
            'tsort' => 'reference', 'tdir' => $tdir
        ]);
        $tempcount = $DB->count_records('pulse_autotemplates');
        if (!empty($tempcount)) {
            $button .= \html_writer::link($manageurl->out(false), $dirimage.get_string('sort'), [
                'class' => 'sort-autotemplates btn btn-primary ml-2'
            ]);
        }



        return $button;
    }

    /**
     * Create instance from templates.
     *
     * @param int $courseid
     * @return string Form with templates list and manage templates button.
     */
    public static function get_addtemplate_instance($courseid) {
        global $OUTPUT, $CFG;

        require_once($CFG->dirroot. '/mod/pulse/automation/automationlib.php');

        // Form to add automation template as instance for the course.
        $url = (new moodle_url('/mod/pulse/automation/instances/edit.php', ['course' => $courseid]))->out(false);
        $form = new \template_addinstance_form($url, ['courseid' => $courseid], 'get');

        $html = \html_writer::start_tag('div', ['class' => 'template-add-form']);
        $templates = \mod_pulse\automation\helper::get_templates_forinstance($courseid);
        if (!empty($templates)) {
            $html .= $form->render();
        }

        // Button to access the manage the automation templates list.
        $manageurl = new moodle_url('/mod/pulse/automation/templates/list.php');
        $html .= \html_writer::link($manageurl->out(true), get_string('managetemplate', 'pulse'), ['class' => 'btn btn-primary', 'target' => '_blank']);

        $tdir = optional_param('tdir', null, PARAM_INT);
        $tdir = ($tdir == SORT_ASC) ? SORT_DESC : SORT_ASC;
        $dirimage = ($tdir == SORT_ASC) ? '<i class="fa fa-sort-amount-up"></i>' : $OUTPUT->pix_icon('t/sort_by', 'Sortby');

        $manageurl = new moodle_url('/mod/pulse/automation/instances/list.php', [
            'courseid' => $courseid, 'tsort' => 'idnumber', 'tdir' => $tdir
        ]);
        if (!empty($templates)) {
            $html .= \html_writer::link($manageurl->out(false), $dirimage.get_string('sort'), ['class' => 'sort-autotemplates btn btn-primary ml-2']);
        }
        $html .= \html_writer::end_tag('div');

        return $html;
    }

    /**
     * Templates table helps content.
     *
     * @return string
     */
    public static function get_templates_tablehelps() {
        global $OUTPUT;

        $actions = \mod_pulse\plugininfo\pulseaction::instance()->get_plugins_base();
        array_walk($actions, function(&$value) {
            $result['badge'] = \html_writer::tag('span', get_string('formtab', 'pulseaction_'.$value->get_component()), ['class' => 'badge badge-primary pulseaction_notification']);
            $result['icon'] = \html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon pulseaction_notification']);
            $value = $result;
        });

        $templatehelp = [
            'help1' => implode(',', array_column($actions, 'icon')),
            'help2' => '<b> Welcome message </b>',
            'help3' => implode(',', array_column($actions, 'badge')),
            'help4' => '<h5 class="template-reference">WELCOME_MESSAGE</h5>',
            'help5' => $OUTPUT->pix_icon('t/edit', \get_string('edit')),
            'help6' => $OUTPUT->pix_icon('t/hide', \get_string('hide')),
            'help7' => \html_writer::tag('div', '<input type="checkbox" class="custom-control-input" checked>
                <span class="custom-control-label"></span>', ['class' => "custom-control custom-switch"]),
            'help8' => \html_writer::tag('label', "33 (1)", ['class' => 'overrides badge badge-secondary pl-10']),
        ];

        $table = new \html_table();
        $table->id = 'plugins-control-panel';
        $table->head = array('', '');

        foreach ($templatehelp as $help => $result) {
            $row = new \html_table_row(array($result, get_string('automationtemplate_'.$help, 'pulse')));
            $table->data[] = $row;
        }

        $html = \html_writer::tag('h3', get_string('instruction', 'pulse'));
        $html .= \html_writer::table($table);
        return \html_writer::tag('div', $html, ['class' => 'automation-instruction']);
    }

    /**
     * Get instance table instructions helps.
     *
     * @return void
     */
    public static function get_instance_tablehelps() {
        global $OUTPUT;

        $actions = \mod_pulse\plugininfo\pulseaction::instance()->get_plugins_base();
        array_walk($actions, function(&$value) {
            $result['badge'] = \html_writer::tag('span', get_string('formtab', 'pulseaction_'.$value->get_component()), ['class' => 'badge badge-primary']);
            $result['icon'] = \html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon pulseaction_notification']);
            $value = $result;
        });

        $templatehelp = [
            'help1' => implode(',', array_column($actions, 'icon')),
            'help2' => '<b> Welcome message </b>',
            'help3' => implode(',', array_column($actions, 'badge')),
            'help4' => '<h5 class="template-reference">WELCOME_MESSAGE</h5>',
            'help5' => $OUTPUT->pix_icon('t/edit', \get_string('edit')),
            'help6' => $OUTPUT->pix_icon('t/copy', \get_string('copy')),
            'help7' => $OUTPUT->pix_icon('i/calendar', \get_string('copy')),
            'help8' => $OUTPUT->pix_icon('t/hide', \get_string('hide')),
        ];

        $table = new \html_table();
        $table->id = 'plugins-control-panel';
        $table->head = array('', '');

        foreach ($templatehelp as $help => $result) {
            $row = new \html_table_row(array($result, get_string('automationinstance_'.$help, 'pulse')));
            $table->data[] = $row;
        }

        $html = \html_writer::tag('h3', get_string('instruction', 'pulse'));
        $html .= \html_writer::table($table);
        return \html_writer::tag('div', $html, ['class' => 'automation-instruction']);
    }

    /**
     * Generate the tabs for settings to display on the templates edit form.
     *
     * @return void
     */
   /*  public static function get_formtabs() {
        global $OUTPUT;

        $templateid = optional_param('id', 0, PARAM_INT);
        $tab = optional_param('tab', null, PARAM_TEXT);


        $tabs[] = new \tabobject('general', new moodle_url('/mod/pulse/automation/templates/edit.php', ['sesskey' => sesskey(), 'id' => $templateid]), get_string('tabgeneral', 'pulse'));
        $tabs[] = new \tabobject(
            'condition',
            new moodle_url('/mod/pulse/automation/templates/edit.php', ['tab' => 'condition', 'sesskey' => sesskey(), 'id' => $templateid]),
            get_string('tabcondition', 'pulse')
        );

        $actions = (new pulseaction())->get_plugins_list();
        foreach ($actions as $component => $plugindir) {
            $tabs[] = new \tabobject(
                $component,
                new moodle_url('/mod/pulse/automation/templates/edit.php', ['tab' => $component, 'sesskey' => sesskey(), 'id' => $templateid]),
                get_string('formtab', 'pulseaction_'.$component));
        }

        return $OUTPUT->tabtree($tabs, $tab ?: 'general');
    }


    public static function get_instance_formtabs() {
        global $OUTPUT, $PAGE;

        $templateid = optional_param('id', 0, PARAM_INT);
        $tab = optional_param('tab', null, PARAM_TEXT);


        $tabs[] = new \tabobject('general', $PAGE->url, get_string('tabgeneral', 'pulse'));
        $tabs[] = new \tabobject(
            'condition',
            new moodle_url($PAGE->url, ['tab' => 'condition']),
            get_string('tabcondition', 'pulse')
        );

        $actions = (new pulseaction())->get_plugins_list();
        foreach ($actions as $component => $plugindir) {
            $tabs[] = new \tabobject(
                $component,
                new moodle_url($PAGE->url, ['tab' => $component]),
                get_string('formtab', 'pulseaction_'.$component)
            );
        }

        return $OUTPUT->tabtree($tabs, $tab ?: 'general');
    } */
}
