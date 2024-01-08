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
 * Conditions - Pulse condition class for the "Enrolment Completion".
 *
 * @package   pulsecondition_enrolment
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulsecondition_enrolment;

use mod_pulse\automation\condition_base;

/**
 * Pulse automation conditions form and basic details.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Verify the user is enroled in the course which is configured in the conditions for the notification.
     *
     * @param stdclass $instancedata
     * @param int $userid
     * @param \completion_info $completion
     * @return bool
     */
    public function is_user_completed($instancedata, int $userid, \completion_info $completion=null) {
        $courseid = $instancedata->courseid;
        $context = \context_course::instance($courseid);

        return is_enrolled($context, $userid);
    }

    /**
     * Include data to action.
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['enrolment'] = get_string('enrolment', 'pulsecondition_enrolment');
    }

    /**
     * Loads the form elements for enrolment condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {

        $completionstr = get_string('enrolment', 'pulsecondition_enrolment');
        $mform->addElement('select', 'condition[enrolment][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[enrolment][status]', 'enrolment', 'pulsecondition_enrolment');
    }

    /**
     * User enrolled event observer. Triggeres the instance actions when user enrolled in the course.
     *
     * @param stdclass $eventdata
     * @return void
     */
    public static function user_enrolled($eventdata) {
        global $DB;

        $data = $eventdata->get_data();
        $courseid = $data['courseid'];
        $relateduserid = $data['userid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $like = $DB->sql_like('pat.triggerconditions', ':value');
        $sql = "SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
        JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id
        WHERE ai.courseid=:courseid AND ($like OR co.triggercondition = 'enrol')";

        $params = ['courseid' => $courseid, 'value' => '%"enrol"%'];
        $instances = $DB->get_records_sql($sql, $params);
        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition = (new self())->trigger_instance($instance->instanceid, $relateduserid);
        }
        return true;
    }
}
