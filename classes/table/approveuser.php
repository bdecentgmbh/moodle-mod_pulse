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
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\table;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

require_once($CFG->dirroot. '/mod/pulse/lib.php');

class approveuser extends \core_user\table\participants {

    /**
     * Approved users list.
     *
     * @var mixed
     */
    protected $completionusers = array();

    /**
     * Course module instance.
     *
     * @var mixed
     */
    protected $cm;

    /**
     * Fetch completions users list.
     *
     * @param  mixed $tableid
     * @return void
     */
    public function __construct($tableid) {
        global $PAGE, $DB;
        parent::__construct($tableid);
        // Page doesn't set when called via dynamic table.
        // Fix this use the cmid from table unique id.
        if (empty($PAGE->cm)) {
            $expuniqueid = explode('-', $tableid);
            $cmid = (int) end($expuniqueid);
            $this->cm = get_coursemodule_from_id('pulse', $cmid);
        } else {
            $this->cm = $PAGE->cm;
        }
        $this->pulse = $DB->get_record('pulse', ['id' => $this->cm->instance] );
        $this->completion = $DB->get_records('pulse_completion', ['pulseid' => $this->pulse->id]);
        $this->completionusers();
    }

    /**
     * List of completed users.
     *
     * @return void
     */
    public function completionusers() {

        foreach ($this->completion as $completion) {
            $this->completionusers[$completion->userid] = $completion->approvalstatus;
        }

    }

    /**
     * Table header and columns definition.
     *
     * @param  mixed $pagesize
     * @param  mixed $useinitialsbar
     * @param  mixed $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $CFG, $OUTPUT, $PAGE;
        // Define the headers and columns.
        $headers = [];
        $columns = [];
        $headers[] = get_string('fullname');
        $columns[] = 'fullname';
        // Add column for groups if the user can view them.
        $canseegroups = !isset($hiddenfields['groups']);
        // Do not show the columns if it exists in the hiddenfields array.
        if (!isset($hiddenfields['lastaccess'])) {
            if ($this->courseid == SITEID) {
                $headers[] = get_string('lastsiteaccess');
            } else {
                $headers[] = get_string('lastcourseaccess');
            }
            $columns[] = 'lastaccess';
        }
        $columns[] = 'approvalstatus';
        $headers[] = get_string('action');
        $this->no_sorting('approvalstatus');

        $this->define_columns($columns);
        $this->define_headers($headers);
        // The name column is a header.
        $this->define_header_column('fullname');
        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');
        $this->set_attribute('id', 'participants');
        \table_sql::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * User approved status and status update actions column.
     *
     * @param  mixed $row
     * @return string $result;
     */
    public function col_approvalstatus($row) {

        $status = (in_array($row->id, array_keys($this->completionusers)) && $this->completionusers[$row->id] ) ? 1 : 0;
        if ($status == 1) {
            $str = html_writer::tag('span', get_string('approved', 'mod_pulse'), ['class' => 'badge badge-success']);
            $url = new moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id, 'userid' => $row->id, 'action' => 'decline']);
            $str .= ' '.html_writer::link($url, get_string('decline', 'mod_pulse'), ['class' => 'approvebtn btn btn-secondary']);

            return $str;
        } else {
            $str = html_writer::tag('span', get_string('declined', 'mod_pulse'), ['class' => 'badge badge-warning']);
            $url = new moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id, 'userid' => $row->id, 'action' => 'approve']);
            $str .= ' '.html_writer::link($url, get_string('approve', 'mod_pulse'), ['class' => 'approvebtn btn btn-primary']);
            return $str;
        }
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new \moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id]);
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        list($twhere, $tparams) = $this->get_sql_where();
        $psearch = new approveuser_search($this->course, $this->context, $this->filterset);
        // Add filter for user context assigned users.
        if (!pulse_has_approvalrole($this->pulse->completionapprovalroles, $this->cm->id, false)) {
            if ($mentesusers = pulse_user_getmentessuser() ) {
                global $DB;
                list($userinsql, $userinparams) = $DB->get_in_or_equal($mentesusers, SQL_PARAMS_NAMED, 'mentees', true);
                $twhere .= ($twhere != '') ? " AND udistinct.id $userinsql" : " udistinct.id $userinsql ";
                $tparams = array_merge($tparams, $userinparams);
            }
        }

        $total = $psearch->get_total_participants_count($twhere, $tparams);

        $this->pagesize($pagesize, $total);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }

        $rawdata = $psearch->get_participants($twhere, $tparams, $sort, $this->get_page_start(), $this->get_page_size());

        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $this->rawdata[$user->id] = $user;
        }
        $rawdata->close();

        if ($this->rawdata) {
            $this->allroleassignments = get_users_roles($this->context, array_keys($this->rawdata),
                    true, 'c.contextlevel DESC, r.sortorder ASC');
        } else {
            $this->allroleassignments = [];
        }

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }
}