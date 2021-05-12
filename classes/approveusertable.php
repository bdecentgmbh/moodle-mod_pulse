<?php

namespace mod_pulse;

class approveusertable extends \core_user\table\participants {

    protected $completionusers;

    protected $cm;

    public function __construct($tableid, $cm, $completionusers) {

        parent::__construct($tableid);

        $this->cm = $cm;        
        $this->completionusers = $completionusers;
    }

    /**
     * Render the participants table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
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
        
        $this->define_columns($columns);
        $this->define_headers($headers);

        // The name column is a header.
        $this->define_header_column('fullname');

        // Make this table sorted by last name by default.
        $this->sortable(true, 'lastname');

        $this->set_attribute('id', 'participants');

        \table_sql::out($pagesize, $useinitialsbar, $downloadhelpbutton);

    }

    function col_approvalstatus($row) {
        $status = ( in_array($row->id, array_keys($this->completionusers)) && $this->completionusers[$row->id] ) ? 1 : 0;

        if ($status == 1) {
            $result = \html_writer::tag('span', get_string('approved', 'mod_pulse'), ['class' => 'badge badge-success']);
            $url = new \moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id, 'userid' => $row->id, 'action' => 'decline']);
            return $result.' '.\html_writer::link($url, get_string('decline', 'mod_pulse'), ['class' => 'approvebtn btn btn-secondary']);
        } else {
            $result = \html_writer::tag('span', get_string('declined', 'mod_pulse'), ['class' => 'badge badge-warning']);
            $url = new \moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id, 'userid' => $row->id, 'action' => 'approve']);
            return $result.' '.\html_writer::link($url, get_string('approve', 'mod_pulse'), ['class' => 'approvebtn btn btn-primary']);
        }
    }
}

?>