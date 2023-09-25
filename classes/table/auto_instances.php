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
 * Automation instance - Table list for the automation instance in the pulse 2.0 plugin.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/tablelib.php');

use table_sql;
use moodle_url;
use html_writer;

/**
 * Automation instances table handler.
 */
class auto_instances extends table_sql {

    /**
     * Setup and Render the menus table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        // Define table headers and columns.
        $columns = ['title', 'idnumber', 'actions'];
        $headers = ["", "idnumber", ""];

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
        global $PAGE;
        $this->baseurl = $PAGE->url;
    }

    /**
     * Set the sql query to fetch smart menus list.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $PAGE;

        $courseid = $PAGE->course->id;
        // Fetch all avialable records from smart menu table.
        $this->set_sql(
            "ai.*, ati.*, ai.id as id, ati.insreference as idnumber",
            '{pulse_autoinstances} ai
            JOIN {pulse_autotemplates_ins} ati ON ati.instanceid=ai.id
            JOIN {pulse_autotemplates} pat ON pat.id=ai.templateid',
            'courseid=:courseid', ['courseid' => $courseid]
        );

        parent::query_db($pagesize, $useinitialsbar);

        $rawdata = $this->rawdata;
        // Collects all the templates id in a array.
        $templates = array_column($rawdata, 'templateid');
        // Fetch the templates data with its actions for all the instances.
        $templatedata = \mod_pulse\automation\templates::get_templates_record($templates);
        // Merge each instances with its templatedata, it will assign the template data for non overridden fields for instance.

        foreach ($rawdata as $key => $data) {
            $templateid = $data->templateid;
            if (isset($templatedata[$templateid])) {
                // Merge the override instance data with its related template data.
                $this->rawdata[$key] = \mod_pulse\automation\helper::merge_instance_overrides($data, $templatedata[$templateid]);
            }
        }

        $this->rawdata = array_filter($this->rawdata);

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
        $editable = true;
        $title = new \core\output\inplace_editable(
            'mod_pulse', 'instancetitle', $row->id, $editable, format_string($row->title),
            $row->title, 'Edit template title',  'New value for ' . format_string($row->title)
        );

        $actions = \mod_pulse\plugininfo\pulseaction::instance()->get_plugins_base();
        array_walk($actions, function(&$value) {
            $classname = 'pulseaction_'.$value->get_component();
            $result['badge'] = html_writer::tag('span', get_string('formtab', 'pulseaction_'.$value->get_component()),
                                ['class' => 'badge badge-primary '.$classname]);
            $result['icon'] = html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon '. $classname]);
            $value = $result;
        });

        $collapseicon = ($row->notes) ? html_writer::tag('span', '<i class="icon fa-fw fa fa-angle-right"></i>' , [
            'data-target' => 'notes-collapse',
            'data-notes' => html_writer::tag('p', format_string($row->notes)),
            'data-collapse' => 1,
            'data-instance' => $row->id
        ]) : '';

        return $collapseicon . implode(' ', array_column($actions, 'icon')) . $OUTPUT->render($title) .
            implode(' ', array_column($actions, 'badge'));
    }

    /**
     * Generates the HTML content for the instance reference column in a row.
     *
     * @param object $row The row containing the data.
     * @return string The HTML content for the reference column.
     */
    public function col_idnumber($row) {
        $title = html_writer::tag('h5', format_string($row->reference . ($row->idnumber ?? '')),
                ['class' => 'template-reference']);
        return $title;
    }

    /**
     * Generates the actions links for the instance.
     *
     * @param object $row The row containing the data.
     * @return string
     */
    public function col_actions($row) {
        global $OUTPUT;

        $baseurl = new \moodle_url('/mod/pulse/automation/instances/edit.php', [
            'instanceid' => $row->instanceid,
            'sesskey' => \sesskey()
        ]);
        $actions = array();

        // Edit.
        $actions[] = array(
            'url' => $baseurl,
            'icon' => new \pix_icon('t/edit', \get_string('edit')),
            'attributes' => array('class' => 'action-edit')
        );

        $listurl = new \moodle_url('/mod/pulse/automation/instances/list.php', [
            'instanceid' => $row->instanceid,
            'sesskey' => \sesskey()
        ]);

        // Make the instance duplicate.
        $actions[] = array(
            'url' => new \moodle_url($listurl, ['action' => 'copy']),
            'icon' => new \pix_icon('t/copy', \get_string('instancecopy', 'pulse')),
            'attributes' => array('class' => 'action-copy')
        );

        // Instance reports builder view.
        $actions[] = array(
            'url' => new \moodle_url($listurl, ['action' => 'report']),
            'icon' => new \pix_icon('i/calendar', \get_string('instancereport', 'pulse')),
            'attributes' => array('class' => 'action-report', 'target' => '_blank')
        );

        // Show/Hide.
        if ($row->status) {
            $actions[] = array(
                'url' => new \moodle_url($listurl, array('action' => 'disable')),
                'icon' => new \pix_icon('t/hide', \get_string('hide')),
                'attributes' => array('data-action' => 'hide', 'class' => 'action-hide')
            );
        } else {
            $actions[] = array(
                'url' => new \moodle_url($listurl, array('action' => 'enable')),
                'icon' => new \pix_icon('t/show', \get_string('show')),
                'attributes' => array('data-action' => 'show', 'class' => 'action-show')
            );
        }

        // Delete.
        $actions[] = array(
            'url' => new \moodle_url($listurl, array('action' => 'delete')),
            'icon' => new \pix_icon('t/delete', \get_string('delete')),
            'attributes' => array('class' => 'action-delete'),
            'action' => new \confirm_action(get_string('deleteinstance', 'pulse'))
        );

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
        return html_writer::span(join('', $actionshtml), 'menu-item-actions item-actions mr-0');
    }

    /**
     * Create a navbar switch for toggling editing mode.
     *
     * @param stdclass $row
     * @return string Html containing the edit switch
     */
    public function edit_switch($row) {
        global $PAGE, $OUTPUT;

        if ($PAGE->user_allowed_editing()) {

            $temp = (object) [
                'legacyseturl' => (new moodle_url('/mod/pulse/automation/templates/list.php',
                    ['id' => $row->id, 'sesskey' => sesskey()]))->out(false),
                'pagecontextid' => $PAGE->context->id,
                'pageurl' => $PAGE->url,
                'sesskey' => sesskey(),
                'checked' => $row->status
            ];
            return $OUTPUT->render_from_template('pulse/status_switch', $temp);
        }
    }
}
