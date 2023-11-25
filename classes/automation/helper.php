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
 * Notification pulse action - Automation helper.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\automation;

use core_calendar\local\event\entities\event;
use html_writer;
use mod_pulse\plugininfo\pulseaction;
use moodle_url;
use single_button;

/**
 * Automation helper.
 */
class helper {

    /**
     * Create the instance of the helper.
     *
     * @return helper
     */
    public static function create() {
        static $instance;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Get templates for an instance. Retrieves templates for a given course, filtering based on categories, status, and visibility.
     *
     * @param int|null $courseid The ID of the course. Defaults to null.
     *
     * @return array Associative array of templates.
     */
    public static function get_templates_forinstance($courseid=null) {
        global $DB;
        // Get course information.
        $course = get_course($courseid);
        // Generate the SQL LIKE condition for categories.
        $like = $DB->sql_like('categories', ':value');
        // Construct the SQL query.
        $sql = "SELECT * FROM {pulse_autotemplates}
            WHERE (categories = '[]' OR categories = '' OR $like) AND visible = 1";
        $params = ['value' => '%"'.$course->category.'"%'];
        // Retrieve records from the database.
        $records = $DB->get_records_sql_menu($sql, $params);
        // Format string values in the result.
        array_walk($records, function(&$val) {
            $val = format_string($val);
        });

        return $records;
    }

    /**
     * Merge instance overrides with template data.
     *
     * @param array $overridedata Array of overridden data.
     * @param array $templatedata Array of template data.
     *
     * @return array Merged data.
     */
    public static function merge_instance_overrides($overridedata, $templatedata) {
        // Filter the empty values.
        $filtered = array_filter((array) $overridedata, function($value) {
            return $value !== null;
        });
        // Merge the templatedata with filterdata.
        $filtered = array_merge((array) $templatedata, $filtered);

        return $filtered;
    }

    /**
     * Filter the record data by keys with a specific prefix.
     *
     * @param array|object $record The record data to be filtered.
     * @param string $prefix The prefix to filter keys by.
     *
     * @return array The filtered data with the prefix removed from keys.
     */
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

    /**
     * Get a list of available actions plugins.
     *
     * @return array An array of available actions.
     */
    public static function get_actions() {
        return \mod_pulse\plugininfo\pulseaction::get_list();
    }

    /**
     * Get a list of available conditions.
     *
     * @return array An array of available conditions.
     */
    public static function get_conditions() {
        return \mod_pulse\plugininfo\pulsecondition::get_list();
    }

    /**
     * Prepare editor draft files for actions.
     *
     * @param array $data The data to be prepared.
     * @param context $context The context.
     */
    public static function prepare_editor_draftfiles(&$data, $context) {

        $actions = self::get_actions();
        foreach ($actions as $key => $action) {
            $action->prepare_editor_fileareas($data, $context);
        }
    }

    /**
     * Post-update editor draft files for actions.
     *
     * @param array $data The data to be updated.
     * @param context $context The context.
     */
    public static function postupdate_editor_draftfiles(&$data, $context) {

        $actions = self::get_actions();
        foreach ($actions as $key => $action) {
            $action->postupdate_editor_fileareas($data, $context);
        }
    }

    /**
     * Get instances associated with a course.
     *
     * @param int $courseid The ID of the course.
     *
     * @return array An array of instances associated with the course.
     */
    public static function get_course_instances($courseid) {
        global $DB;

        $list = $DB->get_records('pulse_autoinstances', ['courseid' => $courseid]);

        return $list;
    }

    /**
     * Insert the additional module fields data to the table.
     *
     * @param int $tablename
     * @param int $instanceid
     * @param array $options
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
     * @param bool $filtered Is the table result is filtered.
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
        global $OUTPUT, $CFG, $PAGE;

        require_once($CFG->dirroot. '/mod/pulse/automation/automationlib.php');

        // Form to add automation template as instance for the course.
        $url = (new moodle_url('/mod/pulse/automation/instances/edit.php', ['course' => $courseid]))->out(false);
        $form = new \template_addinstance_form($url, ['courseid' => $courseid], 'get');

        $html = \html_writer::start_tag('div', ['class' => 'template-add-form']);
        $templates = self::get_templates_forinstance($courseid);
        if (!empty($templates)) {
            $html .= $form->render();
        }

        // Button to access the manage the automation templates list.
        // Verify the user capability.
        $context = $PAGE->context;
        if (has_capability('mod/pulse:viewtemplateslist', $context)) {
            $manageurl = new moodle_url('/mod/pulse/automation/templates/list.php');
            $html .= \html_writer::link($manageurl->out(true),
                get_string('managetemplate', 'pulse'), ['class' => 'btn btn-primary', 'target' => '_blank']);
        }

        $tdir = optional_param('tdir', null, PARAM_INT);
        $tdir = ($tdir == SORT_ASC) ? SORT_DESC : SORT_ASC;
        $dirimage = ($tdir == SORT_ASC) ? '<i class="fa fa-sort-amount-up"></i>' : $OUTPUT->pix_icon('t/sort_by', 'Sortby');

        $manageurl = new moodle_url('/mod/pulse/automation/instances/list.php', [
            'courseid' => $courseid, 'tsort' => 'idnumber', 'tdir' => $tdir
        ]);

        if (!empty($templates)) {
            $html .= \html_writer::link($manageurl->out(false),
                $dirimage.get_string('sort'), ['class' => 'sort-autotemplates btn btn-primary ml-2']);
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
            $classname = 'pulseaction_'.$value->get_component();
            $result['badge'] = \html_writer::tag('span',
                get_string('formtab', 'pulseaction_'.$value->get_component()), ['class' => 'badge badge-primary '.$classname]);
            $result['icon'] = \html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon '.$classname]);
            $value = $result;
        });

        $templatehelp = [
            'help1' => implode(',', array_column($actions, 'icon')),
            'help2' => get_string('automation_title', 'pulse'),
            'help3' => implode(',', array_column($actions, 'badge')),
            'help4' => '<h5 class="template-reference">'.get_string('automation_reference', 'pulse').'</h5>',
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
            $classname = 'pulseaction_'.$value->get_component();
            $result['badge'] = \html_writer::tag('span',
                get_string('formtab', 'pulseaction_'.$value->get_component()), ['class' => 'badge badge-primary ' . $classname]);
            $result['icon'] = \html_writer::span($value->get_action_icon(), 'action', ['class' => 'action-icon ' . $classname]);
            $value = $result;
        });

        $templatehelp = [
            'help1' => implode(',', array_column($actions, 'icon')),
            'help2' => get_string('automationinstance_title', 'pulse'),
            'help3' => implode(',', array_column($actions, 'badge')),
            'help4' => '<h5 class="template-reference">'.get_string('automationinstance_reference', 'pulse').'</h5>',
            'help5' => $OUTPUT->pix_icon('t/edit', \get_string('edit')),
            'help6' => $OUTPUT->pix_icon('t/copy', \get_string('copy')),
            'help7' => $OUTPUT->pix_icon('i/calendar', \get_string('instancereport', 'pulse')),
            'help8' => \html_writer::tag('div', '<input type="checkbox" class="custom-control-input" checked>
                <span class="custom-control-label"></span>', ['class' => "custom-control custom-switch"]),
            'help9' => $OUTPUT->pix_icon('t/delete', \get_string('delete')),

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
     * Display the warning messages of actions for this course, If this instance is not ready to preform the actions.
     *
     * @param stdclass $course
     * @return void
     */
    public static function display_actions_course_warnings($course) {
        $actions = self::get_actions();

        $messages = [];
        foreach ($actions as $component => $action) {
            if (method_exists($action, 'display_instance_warnings')) {
                $messages += $action->display_instance_warnings($course);
            }
        }

        $ul = \html_writer::tag('ul', implode('', array_map(fn($val) => '<li>' . $val . '</li>', array_filter($messages))));
        echo \html_writer::div($ul, 'pulse-warnings text-warning');
    }


    /**
     * Find the time management tool installed and enabled in the learningtools.
     *
     * @return bool result of the time management plugin availability.
     */
    public function timemanagement_installed() {
        global $DB, $CFG;
        $tools = \core_plugin_manager::instance()->get_subplugins_of_plugin('local_learningtools');
        if (in_array('ltool_timemanagement', array_keys($tools))) {
            $status = $DB->get_field('local_learningtools_products', 'status', ['shortname' => 'timemanagement']);
            if ($status) {
                require_once($CFG->dirroot.'/local/learningtools/ltool/timemanagement/lib.php');
            }
            return ($status) ? true : false;
        }
        return false;
    }

    /**
     * Get course time mananagment details user current course progress and due modules course.
     *
     * @param string $var
     * @param \stdClass $course
     * @param int $userid
     * @param \context $context
     * @param \stdClass $mod
     * @return string
     */
    public function timemanagement_details(string $var, \stdClass $course, int $userid, $context=null, $mod=null): string {
        global $CFG, $DB, $PAGE;

        require_once($CFG->dirroot.'/enrol/locallib.php');

        // Upcoming event dates.
        if ($var == 'eventdates') {
            $calendar = \calendar_information::create(time(), $course->id, null);
            list($data, $template) = calendar_get_view($calendar, 'upcoming_mini');
            $final = isset($data->events) ? array_map(function($event) {
                $link = \html_writer::link($event->viewurl, $event->popupname);
                $link .= '<br>'.$event->formattedtime;
                return \html_writer::tag('li', $link);
            } , $data->events) : [];

            return $final ? \html_writer::tag('ul', implode('', $final)) : '';
        }

        // Other than eventdates all are need learning tools time management installed.
        if (!$this->timemanagement_installed()) {
            return '';
        }

        $context = $context ?? context_course::instance($course->id);
        // Find the course due date. only if the timemanagement installed.
        if ($var == 'coursedue' && function_exists('ltool_timemanagement_cal_course_duedate')) {
            // Get user enrolment info in course.
            $usercourseenrollinfo = ltool_timemanagement_get_course_user_enrollment($course->id, $userid);
            $coursedatesinfo = $DB->get_record('ltool_timemanagement_course', array('course' => $course->id));
            // Check course set in time management.
            if ($coursedatesinfo) {
                $duedate = ltool_timemanagement_cal_course_duedate($coursedatesinfo, $usercourseenrollinfo[0]['timestart']);
                return $duedate ? userdate($duedate) : '';
            }
            return '';
        }

        if ($mod && $var == 'activityduedate' && function_exists('ltool_timemanagement_get_course_user_enrollment')) {
            $userenrolments = ltool_timemanagement_get_course_user_enrollment($course->id, $userid);
            $record = $DB->get_record('ltool_timemanagement_modules', array('cmid' => $mod->cmid));
            if ($record) {
                $dates = ltool_timemanagement_cal_coursemodule_managedates($record, $userenrolments[0]['timestart']);
                return isset($dates['duedate']) ? userdate($dates['duedate']) : '';
            }
            return '';
        }

        if ($mod && $var == 'upcomingmods' && function_exists('ltool_timemanagement_get_course_user_enrollment')) {
            $mods = $this->ltool_timemanagement_get_upcoming_activities($course->id, $userid);
            return implode(', ', array_map(
                fn($mod) => \html_writer::link((new \moodle_url("/mod/$mod->modname/view.php", ['id' => $mod->id]))->out(false),
                    format_string($mod->name)),
                array_values($mods)));
        }

        return '';
    }

    /**
     * Get upcoming activities list from time management learning tool.
     *
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    protected function ltool_timemanagement_get_upcoming_activities($courseid, $userid) {
        global $DB;
        $upcomingmods = [];
        $modinfo = get_fast_modinfo($courseid);
        $userenrolments = ltool_timemanagement_get_course_user_enrollment($courseid, $userid);
        if (!empty($modinfo->sections) && !empty($userenrolments)) {
            foreach ($modinfo->sections as $modnumbers) {
                if (!empty($modnumbers)) {
                    foreach ($modnumbers as $modnumber) {
                        $mod = $modinfo->cms[$modnumber];
                        if ($DB->record_exists('course_modules', array('id' => $mod->id, 'deletioninprogress' => 0))
                            && !empty($mod) && $mod->uservisible) {
                            $record = $DB->get_record('ltool_timemanagement_modules', array('cmid' => $mod->id));
                            if (!empty($record)) {
                                $timestarted = $userenrolments[0]['timestart'];
                                list('startdate' => $startdate,
                                'duedate' => $duedate) = ltool_timemanagement_cal_coursemodule_managedates($record, $timestarted);
                                if ($startdate > time()) {
                                    $upcomingmods[$modnumber] = $mod;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $upcomingmods;
    }

}
