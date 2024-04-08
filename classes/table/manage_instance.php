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
 * Manage instance - Table list for the manage instance in the pulse 2.0 plugin.
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
use core_table\dynamic as dynamic_table;
use stdClass;
use core_course_category;

/**
 * Manage instances table handler.
 */
class manage_instance extends \table_sql implements dynamic_table {

    /**
     * Template id.
     *
     * @var int $templateid
     */
    public $templateid;

    /**
     * Fetch the template data.
     *
     * @var stdclass
     */
    public $template;

    /**
     * Fetch completions automation template list.
     *
     * @param  mixed $templateid
     * @return void
     */
    public function __construct($templateid) {
        global $DB;

        parent::__construct($templateid);

        // Template ID.
        $this->templateid = $templateid;

        // Automation template data.
        $this->template = $DB->get_record('pulse_autotemplates', ['id' => $templateid]);

    }

    /**
     * Get the table context.
     *
     * @return void
     */
    public function get_context(): \context {
        return \context_system::instance();
    }

    /**
     * Setup and Render the manage instace table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $OUTPUT;

        // Define table headers and columns.
        $columns = ['checkbox', 'category', 'coursename', 'numberofinstance', 'actions'];
        $headers = [
            '',
            get_string('coursecategory', 'pulse'),
            get_string('coursename', 'pulse'),
            get_string('numberofinstance', 'pulse'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->no_sorting('select');

        // Remove sorting for some fields.
        $this->sortable(false);

        $this->set_attribute('id', 'pulse-manage-instance');

        $this->guess_base_url();

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Guess the base url for the manage instance table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/mod/pulse/automation/templates/edit.php', ['id' => $this->templateid]);
    }

    /**
     * Override the table show_hide_link to not show for select column.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        return '';
    }

    /**
     * Set the sql query to fetch manage instance list.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Fetch available records from instance manage table.
        $from = "{course} c
                JOIN {course_categories} cat ON c.category = cat.id
                LEFT JOIN (
                    SELECT courseid, count(*) AS numberofinstance FROM {pulse_autoinstances} WHERE
                    templateid = ".$this->templateid." GROUP BY courseid
                ) pai ON pai.courseid = c.id
                LEFT JOIN (
                    SELECT courseid, count(*) AS enabledinstance FROM {pulse_autoinstances} WHERE
                    templateid =:templateid AND status <> 0 GROUP BY courseid
                ) pad ON pad.courseid = c.id
        ";
        $condition = "c.id <> 1 AND c.visible != 0";
        $inparams = ['templateid' => $this->templateid];

        // When prevent the categories in the automation template, then show only the selected category
        // courses in the instance management table.
        $categories = json_decode($DB->get_field('pulse_autotemplates', 'categories', ['id' => $this->templateid]));
        if (!empty($categories)) {
            list($categoryinsql, $categoryinparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'cate');
            $condition .= " AND c.category $categoryinsql";
            $inparams += $categoryinparams;
        }

        // Filter the category.
        if ($this->filterset->has_filter('category')) {
            $values = $this->filterset->get_filter('category')->get_filter_values();
            $category = isset($values[0]) ? current($values) : '';

            if (!empty($category)) {
                list($insql, $cateinparams) = $DB->get_in_or_equal($category, SQL_PARAMS_NAMED, 'cat');
                $condition .= " AND c.category $insql";
                $inparams += $cateinparams;
            }
        }

        // Filter the course.
        if ($this->filterset->has_filter('course')) {
            $values = $this->filterset->get_filter('course')->get_filter_values();
            $course = isset($values[0]) ? current($values) : '';
            if (!empty($course)) {
                list($insql, $courseinparams) = $DB->get_in_or_equal($course, SQL_PARAMS_NAMED, 'co');
                $condition .= " AND c.id $insql";
                $inparams += $courseinparams;
            }
        }

        // Filter the instance count.
        if ($this->filterset->has_filter('numberofinstance')) {
            $values = $this->filterset->get_filter('numberofinstance')->get_filter_values();
            $numofinstance = isset($values[0]) ? current($values) : '';

            if ($numofinstance !== null && ($numofinstance != '' || $numofinstance === 0)) {
                $condition .= $numofinstance ? " AND pai.numberofinstance = :numberofinstance " :
                " AND pai.numberofinstance IS NULL ";
                $inparams += ['numberofinstance' => $numofinstance ?: ''];
            }
        }

        $this->set_sql('c.*, cat.name AS categoryname, pai.numberofinstance, pad.enabledinstance', $from, $condition, $inparams);

        parent::query_db($pagesize, $useinitialsbar);

        // Filter the records by instance overrides count.
        if ($this->filterset->has_filter('numberofoverrides')) {
            $values = $this->filterset->get_filter('numberofoverrides')->get_filter_values();
            $numberofoverrides = isset($values[0]) ? current($values) : '';

            if ($numberofoverrides !== null && ($numberofoverrides != '' || $numberofoverrides === 0)) {
                // Fetch the list of instances for this template.
                $instances = $DB->get_records('pulse_autoinstances', ['templateid' => $this->templateid]);
                $overrides = [];
                if (!empty($instances)) {
                    // Store the number of overrides for this instance. Store the overrides based on the course.
                    foreach ($instances as $instanceid => $instance) {
                        $insobj = new \mod_pulse\automation\instances($instance->id);
                        $formdata = (object) $insobj->get_instance_formdata();
                        $count = $formdata->overridecount;
                        $overrides[$instance->courseid] = isset($overrides[$instance->courseid])
                            ? $overrides[$instance->courseid] + $count : $count;
                    }
                }
                // Filter the records by the number of overrides from the filter form.
                $this->rawdata = array_filter($this->rawdata, function($row) use ($overrides, $numberofoverrides) {
                    return isset($overrides[$row->id]) && $overrides[$row->id] == $numberofoverrides;
                });

                // Reset the records count to resize the page size.
                $this->pagesize($pagesize, count($this->rawdata));
            }
        }

    }

    /**
     * Bulk action check box.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_checkbox(stdClass $row) {
        global $DB;
        $html = '';

        // Count the number of instances in that course.
        $countcolorclass = $row->numberofinstance == 0 ? 'emptyinstance' : '';

        $bulkcourseinput = [
            'id' => 'courselistitem' . $row->id,
            'type' => 'checkbox',
            'name' => 'bc[]',
            'value' => $row->id,
            'class' => 'bulk-action-checkbox custom-control-input '. $countcolorclass,
            'data-action' => 'select',
        ];
        $html .= html_writer::start_div('custom-control custom-checkbox mr-1 ');
        $html .= html_writer::empty_tag('input', $bulkcourseinput);
        $labeltext = html_writer::span(get_string('bulkactionselect', 'moodle'), 'sr-only');
        $html .= html_writer::tag('label', $labeltext, [
            'class' => 'custom-control-label',
            'for' => 'courselistitem' . $row->id,
        ]);
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Name of the course category.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_category(stdClass $row) {
        global $DB;
        $categoryname = $row->categoryname;
        return $categoryname;
    }

    /**
     * Name of the course.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_coursename(stdClass $row) : string {
        return format_string($row->fullname);;
    }

    /**
     * Get the number of instances count in the related course.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_numberofinstance(stdClass $row) {
        global $DB;

        $countcolorclass = $row->numberofinstance == 0 ? 'emptycount' : '';
        $count = html_writer::tag('h6', $row->numberofinstance ?: 0, ['class' => 'numofinstance '.$countcolorclass]);
        return $count;
    }

    /**
     * Generates the actions links for the instance management.
     *
     * @param object $row The row containing the data.
     * @return string
     */
    public function col_actions($row) {
        global $OUTPUT, $DB;

        $baseurl = new \moodle_url('/mod/pulse/automation/templates/edit.php', [
            'id' => $this->templateid,
            'sesskey' => \sesskey(),
        ]);
        $actions = [];

        // Redirect to the related course automation page.
        $actions[] = [
            'url' => new \moodle_url('/mod/pulse/automation/instances/list.php', ['courseid' => $row->id, 'sesskey' => sesskey()]),
            'icon' => new \pix_icon('t/edit', \get_string('instanceslist', 'pulse')),
            'attributes' => ['class' => 'action-edit', 'target' => '_blank'],
        ];

        // Add instance.
        $actions[] = [
            'url' => new \moodle_url($baseurl, ['action' => 'add', 'courseid' => $row->id]),
            'icon' => new \pix_icon('t/add', \get_string('addinstance', 'pulse')),
            'attributes' => ['class' => 'action-add-instance'],
        ];

        // Enable / Disable course instance toggle option.
        $disabled = $row->numberofinstance - $row->enabledinstance; // Disable instances in the course.
        $status = $row->enabledinstance; // Find the most instances status enabled/disabled.

        $badge = '';
        $badge = ($row->numberofinstance && $row->enabledinstance && $disabled)
            ? html_writer::tag('span', get_string('mixed', 'pulse'), ['class' => 'badge badge-secondary']) : '';

        $checked = $row->enabledinstance ? ['checked' => 'checked'] : [];

        $checkbox = html_writer::div(
            html_writer::empty_tag('input',
                ['type' => 'checkbox', 'class' => 'custom-control-input'] + $checked
            ) . html_writer::tag('span', '', ['class' => 'custom-control-label']),
            'custom-control custom-switch'
        );
        $statusurl = new \moodle_url($baseurl, ['action' => ($status) ? 'disable' : 'enable', 'courseid' => $row->id]);
        $statusclass = 'pulse-manage-instance-status-switch ';
        $statusclass .= $status ? 'action-hide' : 'action-show';
        $actions[] = html_writer::link($statusurl->out(false), $checkbox, ['class' => $statusclass]);
        $actions[] = $badge;

        // Instance reports for this course.
        $actions[] = [
            'url' => new \moodle_url('/mod/pulse/automation/instances/list.php', [
                'courseid' => $row->id, 'sesskey' => sesskey(), 'templateid' => $this->templateid,
            ]),
            'icon' => new \pix_icon('i/calendar', \get_string('courseinsreport', 'pulse')),
            'attributes' => ['class' => 'action-report', 'target' => '_blank'],
        ];

        // Delete all instance in the course.
        $actions[] = [
            'url' => new \moodle_url($baseurl, ['action' => 'delete', 'courseid' => $row->id]),
            'icon' => new \pix_icon('t/delete', \get_string('delete')),
            'attributes' => ['class' => 'action-delete'],
            'action' => new \confirm_action(get_string('confirmdeleteinstance', 'pulse')),
        ];

        $actionshtml = [];
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
                $action['attributes'],
            );
        }
        return html_writer::div(join('', $actionshtml), 'menu-item-actions item-actions mr-0');
    }

}
