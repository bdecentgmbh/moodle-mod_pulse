<?php


namespace mod_pulse\table;

require_once($CFG->dirroot.'/lib/tablelib.php');

use table_sql;
use moodle_url;
use html_writer;
use mod_pulse\automation\helper;

class auto_templates extends table_sql {

/**
     * Setup and Render the menus table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        // Define table headers and columns.
        $columns = ['title', 'templateaction', 'reference', 'actions'];
        $headers = ["", "", "", ""];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Remove sorting for some fields.
        $this->sortable(true, 'sortorder', SORT_ASC);

        $this->no_sorting('title');
        $this->no_sorting('actions');

        $this->set_attribute('id', 'pulse_automation_template');

        $this->guess_base_url();

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Guess the base url for the automation table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/mod/pulse/automation/templates/list.php');
    }

    /**
     * Set the sql query to fetch smart menus list.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param boolean $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        // Fetch all avialable records from smart menu table.
        $this->set_sql('*', '{pulse_autotemplates}', '1=1');

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Show the menu title in the list, render the description based on the show description value "Above, below and help".
     * Default help_icon method only works with string identifiers, rendered the help icon from template directly.
     *
     * @param object $row
     * @return void
     */
    public function col_title($row) {
        global $OUTPUT;

        // TODO: Editable.
        $editable =  true; // has_capability('tool/mytest:update', context_system::instance());
        $title = new \core\output\inplace_editable(
            'mod_pulse', 'templatetitle', $row->id, $editable, format_string($row->title),
            $row->title, 'Edit template title',  'New value for ' . format_string($row->title)
        );

        $actions = \mod_pulse\plugininfo\pulseaction::instance()->get_plugins_base();
        array_walk($actions, function(&$value) {
            $result['badge'] = html_writer::tag('span', get_string('formtab', 'pulseaction_'.$value->get_component()), ['class' => 'badge badge-primary']);
            $result['icon'] = html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon']);
            $value = $result;
        });

        return implode(' ', array_column($actions, 'icon')) . $OUTPUT->render($title) . implode(' ', array_column($actions, 'badge'));
    }

    public function col_templateaction() {

    }

    public function col_reference($row) {
        $title = html_writer::tag('h5', format_string($row->reference), ['class' => 'template-reference']);
        return $title;
    }

    public function col_actions($row) {
        global $OUTPUT;

        $baseurl = new \moodle_url('/mod/pulse/automation/templates/edit.php', [
            'id' => $row->id,
            'sesskey' => \sesskey()
        ]);
        $actions = array();


        // Edit.
        $actions[] = array(
            'url' => $baseurl,
            'icon' => new \pix_icon('t/edit', \get_string('edit')),
            'attributes' => array('class' => 'action-edit')
        );

        // Make the menu duplicate.
       /*  $actions[] = array(
            'url' => new \moodle_url($baseurl, ['action' => 'copy']),
            'icon' => new \pix_icon('t/copy', \get_string('smartmenuscopymenu', 'theme_boost_union')),
            'attributes' => array('class' => 'action-copy')
        ); */
        list($totalcount, $disabledcount) = $this->get_instance_count($row);
        $actions[] = html_writer::tag('label', $totalcount . "(" . $disabledcount . ")", ['class' => 'overrides badge badge-secondary pl-10']);

        $listurl = new \moodle_url('/mod/pulse/automation/templates/list.php', [
            'id' => $row->id,
            'sesskey' => \sesskey()
        ]);
        // Show/Hide.
        if ($row->visible) {
            $actions[] = array(
                'url' => new \moodle_url($listurl, array('action' => 'hidemenu')),
                'icon' => new \pix_icon('t/hide', \get_string('hide')),
                'attributes' => array('data-action' => 'hide', 'class' => 'action-hide')
            );
        } else {
            $actions[] = array(
                'url' => new \moodle_url($listurl, array('action' => 'showmenu')),
                'icon' => new \pix_icon('t/show', \get_string('show')),
                'attributes' => array('data-action' => 'show', 'class' => 'action-show')
            );
        }

        // List of items.
        // $itemsurl = new \moodle_url('/mod/pulse/automation/templates/edit.php', ['menu' => $row->id]);
        $actions[] = $this->edit_switch($row);

        // Delete.
        /* $actions[] = array(
            'url' => new \moodle_url($baseurl, array('action' => 'delete')),
            'icon' => new \pix_icon('t/delete', \get_string('delete')),
            'attributes' => array('class' => 'action-delete'),
            'action' => new \confirm_action(get_string('deletetemplate', 'pulse'))
        ); */

        /* // Move up/down.
        $actions[] = array(
            'url' => new \moodle_url($baseurl, array('action' => 'moveup')),
            'icon' => new \pix_icon('t/up', \get_string('moveup')),
            'attributes' => array('data-action' => 'moveup', 'class' => 'action-moveup')
        );
        $actions[] = array(
            'url' => new \moodle_url($baseurl, array('action' => 'movedown')),
            'icon' => new \pix_icon('t/down', \get_string('movedown')),
            'attributes' => array('data-action' => 'movedown', 'class' => 'action-movedown')
        ); */

        $actionshtml = array();
        foreach ($actions as $action) {
            if (!is_array($action)) {
                $actionshtml[] = $action;
                continue;
            }
            $action['attributes']['role'] = 'button';
            $actionshtml[] = $OUTPUT->action_icon(
                $action['url'],
                $action['icon'],
                ($action['action'] ?? null),
                $action['attributes']
            );
        }
        return html_writer::div(join('', $actionshtml), 'menu-item-actions item-actions mr-0');
    }


    /**
     * Create a navbar switch for toggling editing mode.
     *
     * @return string Html containing the edit switch
     */
    public function edit_switch($row) {
        global $PAGE, $OUTPUT;

        if ($PAGE->user_allowed_editing()) {

            $temp = (object) [
                'legacyseturl' => (new moodle_url('/mod/pulse/automation/templates/list.php', ['id' => $row->id, 'sesskey' => sesskey()]))->out(false),
                'pagecontextid' => $PAGE->context->id,
                'pageurl' => $PAGE->url,
                'sesskey' => sesskey(),
                'checked' => $row->status,
                'id' => $row->id
            ];

            return $OUTPUT->render_from_template('pulse/status_switch', $temp);
        }
    }

    protected function get_instance_count($row) {
        global $DB;

        $totalcount = $DB->count_records('pulse_autoinstances', ['templateid' => $row->id]);
        $disabledcount = $DB->count_records('pulse_autoinstances', ['templateid' => $row->id, 'status' => 0]);
        return [$totalcount, $disabledcount];
    }
}
