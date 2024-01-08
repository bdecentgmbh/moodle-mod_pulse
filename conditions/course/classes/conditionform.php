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
 * Conditions - Pulse condition class for the "Course Completion".
 *
 * @package   pulsecondition_course
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_course;

use mod_pulse\automation\condition_base;

/**
 * Automation course completion condition form.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Verify the user is completed the course which is configured in the conditions for the notification.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if completed, false otherwise.
     */
    public function is_user_completed($instancedata, int $userid, \completion_info $completion=null) {
        global $DB;

        $courseid = $instancedata->courseid;
        $course = get_course($courseid);

        $completion = ($completion !== null) ? $completion : new \completion_info($course);
        return $completion->is_course_complete($userid);
    }

    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['course'] = get_string('coursecompletion', 'pulsecondition_course');
    }

    /**
     * Loads the form elements for course completion condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('coursecompletion', 'pulsecondition_course');

        $mform->addElement('select', 'condition[course][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[course][status]', 'coursecompletion', 'pulsecondition_course');
    }

    /**
     * Course completed event observer. trigger the actions when the user completies the course.
     *
     * @param stdclass $eventdata
     * @return bool
     */
    public static function course_completed($eventdata) {
        global $DB;

        $data = $eventdata->get_data();
        $courseid = $data['courseid'];
        $relateduserid = $data['userid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $like = $DB->sql_like('pat.triggerconditions', ':value');
        $sql = "SELECT *, ai.id as id, ai.id as instanceid FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
        LEFT JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id AND co.triggercondition = 'course'
        WHERE ai.courseid=:courseid AND (co.status > 0 OR $like)";

        // Parameters.
        $params = ['courseid' => $courseid, 'value' => '%"course"%'];
        // Fetch the list of notifications.
        $instances = $DB->get_records_sql($sql, $params);

        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition = (new self())->trigger_instance($instance->instanceid, $relateduserid);
        }

        return true;
    }
}
