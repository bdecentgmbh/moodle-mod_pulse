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
 * Conditions - Pulse condition class for the "Cohort Completion".
 *
 * @package   pulsecondition_cohort
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulsecondition_cohort;
use mod_pulse\automation\condition_base;

/**
 * Automation cohort completion condition form.
 */
class conditionform extends \mod_pulse\automation\condition_base {

    /**
     * Include condition
     *
     * @param array $option
     * @return void
     */
    public function include_condition(&$option) {
        $option['cohort'] = get_string('condition', 'pulsecondition_cohort');
    }

    /**
     * Loads the form elements for cohort condition.
     *
     * @param MoodleQuickForm $mform The form object.
     * @param object $forminstance The form instance.
     */
    public function load_instance_form(&$mform, $forminstance) {
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');

        $completionstr = get_string('condition', 'pulsecondition_cohort');

        $mform->addElement('select', 'condition[cohort][status]', $completionstr, $this->get_options());
        $mform->addHelpButton('condition[cohort][status]', 'condition', 'pulsecondition_cohort');

        $cohorts = cohort_get_all_cohorts();
        $cohorts = $cohorts['cohorts'];

        array_walk($cohorts, function(&$value) {
            $value = $value->name;
        });

        // TODO: double check all the config names and help icons.
        $cohorts = $mform->addElement('autocomplete', 'condition[cohort][cohorts]',
                    get_string('cohorts', 'pulsecondition_cohort'), $cohorts);
        $cohorts->setMultiple(true);
        $mform->hideIf('condition[cohort][cohorts]', 'condition[cohort][status]', 'eq', self::DISABLED);
        $mform->addHelpButton('condition[cohort][cohorts]', 'cohorts', 'pulsecondition_cohort');

        $mform->addElement('hidden', 'override[condition_cohort_cohorts]', 1);
        $mform->setType('override[condition_cohort_cohorts]', PARAM_RAW);
    }

    /**
     * Checks if the user has assigned into the specified cohorts.
     *
     * @param object $instancedata The instance data.
     * @param int $userid The user ID.
     * @param \completion_info|null $completion The completion information.
     * @return bool True if completed, false otherwise.
     */
    public function is_user_completed($instancedata, $userid, \completion_info $completion=null) {
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php'); // Cohort library file inclusion.

        // Find the cohort conditions is enabled if not then make this condition true.
        if (!isset($instancedata->condition['cohort']['status']) || $instancedata->condition['cohort']['status'] == 0 ) {
            return true;
        }

        // Get the cohort ids.
        $cohorts = $instancedata->condition['cohort']['cohorts'] ?? [];
        foreach ($cohorts as $cohort) {
            if (cohort_is_member($cohort, $userid)) {
                return true;
            }
        }
        // Cohorts are configured but not completed.
        return !empty($cohorts) ? false : true;
    }

    /**
     * Member added event observer.
     *
     * @param stdclass $eventdata
     * @return bool
     */
    public static function member_added($eventdata) {
        global $DB;

        $data = $eventdata->get_data();

        $cohortid = $data['objectid'];
        $relateduserid = $data['relateduserid'];

        // Trigger the instances, this will trigger its related actions for this user.
        $patlike = $DB->sql_like('pat.triggerconditions', ':cohort');
        $overlike = $DB->sql_like('additional', ':value');
        $cohortlike = $DB->sql_like('co.triggercondition', ':cohort2');

        $sql = "SELECT * FROM {pulse_autoinstances} ai
        JOIN {pulse_autotemplates} pat ON pat.id = ai.templateid
        JOIN {pulse_condition_overrides} co ON co.instanceid = ai.id
        WHERE ($patlike OR ($cohortlike AND co.status > 0) ) AND $overlike";

        $params = ['cohort' => 'cohort', 'cohort2' => 'cohort', 'value' => '%"'.$cohortid.'"%'];
        $instances = $DB->get_records_sql($sql, $params);
        $condition = new self();
        foreach ($instances as $key => $instance) {
            // TODO: Condition status check.
            $condition->trigger_instance($instance->instanceid, $relateduserid);
        }
        return true;
    }
}
