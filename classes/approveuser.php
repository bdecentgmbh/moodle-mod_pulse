<?php

namespace mod_pulse;

use context_course;
use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;
use html_table;
use html_writer;

class approveuser {

    public $cm;

    public $course;

    public $pulse;

    public $completionusers = [];

    public function __construct($cmid) {
        global $DB;
        $this->cm = get_coursemodule_from_id('pulse', $cmid);
        $this->course = get_course($this->cm->course);
        $this->pulse = $DB->get_record('pulse', ['id' => $this->cm->instance] );        
        $this->completion = $DB->get_records('pulse_completion', ['pulseid' => $this->pulse->id]);
        // $this->courseusers = $this->get_courseusers();
        $this->coursecontext = context_course::instance($this->course->id);
        $this->completionusers();
    }

    public function getuserslist() {
        
        $filterset = new \core_user\table\participants_filterset();
        $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$this->course->id]));

        $participanttable = new \mod_pulse\approveusertable("user-index-participants-{$this->course->id}", $this->cm, $this->completionusers);
        $participanttable->set_filterset($filterset);       
        return  $participanttable->out(10, true);

        // approveusertable
        /* $users = $this->courseusers;
        $table = new html_table();
        $table->head = array('Lastname', 'Firstname', 'approvalstatus', 'action');
        $this->completionusers();
        if (!empty($users)) {
            foreach ($users as $user) {
                $row = [];
                $row[] = $user->firstname;
                $row[] = $user->lastname;
                $row[] = ( in_array($user->id, array_keys($this->completionusers)) && $this->completionusers[$user->id] ) ? 1 : 0;
                $url = new \moodle_url('/mod/pulse/approve.php', ['cmid' => $this->cm->id, 'userid' => $user->id, 'action' => 'approve']);
                $row[] = html_writer::link($url, 'Activate');
                $table->data[] = $row;
            }
        }
        return html_writer::table($table); */
    }

    public function completionusers() {
        foreach ($this->completion as $completion) {
            $this->completionusers[$completion->userid] = $completion->approvalstatus;
        }
    }

    public function get_courseusers() {
        global $USER;
        // Force group filtering if user should only see a subset of groups' users.
        /* if ($this->cm->groupmode != NOGROUPS ) {
            $groups = groups_get_user_groups($this->course->id, $USER->id);
            $courseusers = [];
            foreach ($groups as $groupids) {
                foreach ($groupids as $groupid) {
                    $groupusers = groups_get_members($groupid);
                    $courseusers = array_merge($courseusers, $groupusers);
                }
            }
        } else {
            $courseusers = get_enrolled_users($this->coursecontext);
        }
        return $courseusers; */
    }
}