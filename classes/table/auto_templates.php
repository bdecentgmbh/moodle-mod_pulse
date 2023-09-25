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
 * Automation template - Table list for the automation template in the pulse 2.0 plugin.
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
use mod_pulse\automation\helper;

/**
 * Automation templates table handler.
 */
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
        $columns = ['title', 'reference', 'actions'];
        $headers = ["", "", ""];

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
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        // Fetch all avialable records from smart menu table.
        $condition = '1=1';
        $params = [];
        if ($this->filterset->has_filter('category')) {

            $values = $this->filterset->get_filter('category')->get_filter_values();
            $category = isset($values[0]) ? current($values) : '';
            $condition = $DB->sql_like('categories', ':value');
            $params += ['value' => '%"'.$category.'"%'];
        }
        $this->set_sql('*', '{pulse_autotemplates}', $condition, $params);
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
        $editable = true;
        $title = new \core\output\inplace_editable(
            'mod_pulse', 'templatetitle', $row->id, $editable, format_string($row->title),
            $row->title, 'Edit template title',  'New value for ' . format_string($row->title)
        );

        $actions = \mod_pulse\plugininfo\pulseaction::instance()->get_plugins_base();
        array_walk($actions, function(&$value) {
            $classname = 'pulseaction_'.$value->get_component();
            $result['badge'] = html_writer::tag('span', get_string('formtab', 'pulseaction_'.$value->get_component()),
                                ['class' => 'badge badge-primary '.$classname]);
            $result['icon'] = html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon '.$classname]);
            $value = $result;
        });
        return implode(' ', array_column($actions, 'icon')) . $OUTPUT->render($title) . implode(' ',
                array_column($actions, 'badge'));
    }

    /**
     * Generates the HTML content for the instance reference column in a row.
     *
     * @param object $row The row containing the data.
     * @return string The HTML content for the reference column.
     */
    public function col_reference($row) {
        $title = html_writer::tag('h5', format_string($row->reference), ['class' => 'template-reference']);
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

        list($totalcount, $disabledcount) = $this->get_instance_count($row);
        $actions[] = html_writer::tag('label', $totalcount . "(" . $disabledcount . ")", [
                    'class' => 'overrides badge badge-secondary pl-10']);
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
        $actions[] = $this->edit_switch($row);
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
     * @param stdclass $row
     * @return string Html containing the edit switch
     */
    public function edit_switch($row) {
        global $PAGE, $OUTPUT;

        $temp = (object) [
            'legacyseturl' => (new moodle_url('/mod/pulse/automation/templates/list.php', [
                'id' => $row->id,
                'sesskey' => sesskey()
                ]))->out(false),
            'pagecontextid' => $PAGE->context->id,
            'pageurl' => $PAGE->url,
            'sesskey' => sesskey(),
            'checked' => $row->status,
            'id' => $row->id
        ];
        return $OUTPUT->render_from_template('pulse/status_switch', $temp);
    }

    /**
     * Gets the total count and disabled count of instances associated with a template.
     *
     * @param object $row The template row.
     * @return array An array containing the total count and disabled count.
     */
    protected function get_instance_count($row) {
        global $DB;

        $totalcount = $DB->count_records('pulse_autoinstances', ['templateid' => $row->id]);
        $disabledcount = $DB->count_records('pulse_autoinstances', ['templateid' => $row->id, 'status' => 0]);
        return [$totalcount, $disabledcount];
    }
}
