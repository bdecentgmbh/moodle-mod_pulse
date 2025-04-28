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
 * Activity custom completion subclass for the pulse activity.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_pulse\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the pulse activity.
 *
 * Contains the class for defining mod_pulse's custom completion rules
 * and fetching a pulse instance's completion statuses for a user.
 **/
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $pulse = $this->get_pulse();
        $completion = $this->get_completion();

        switch ($rule) {
            case 'completionwhenavailable':
                if ($pulse->completionavailable) {
                    $modinfo = get_fast_modinfo($this->cm->course, $this->userid);
                    $cm = $modinfo->get_cm($this->cm->id);

                    $status = $cm->get_user_visible() ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
                }
                break;
            case 'completionapproval':
                // Completion by any selected role user.
                if ($pulse->completionapproval) {
                    if (!empty($completion) && $completion->approvalstatus == 1) {
                        $status = COMPLETION_COMPLETE;
                    } else {
                        $status = COMPLETION_INCOMPLETE;
                    }
                }
                break;
            case 'completionself':
                // Self completion by own.
                if ($pulse->completionself) {
                    if (!empty($completion) && $completion->selfcompletion == 1) {
                        $status = COMPLETION_COMPLETE;
                    } else {
                        $status = COMPLETION_INCOMPLETE;
                    }
                }
                break;
            default:
                $status = false;
                break;
        }

        return (isset($status) && $status) ? $status : COMPLETION_INCOMPLETE;
    }

    /**
     * Get pulse instance.
     *
     * @return object pulse instance.
     */
    public function get_pulse() {
        global $DB;
        return $DB->get_record('pulse', ['id' => $this->cm->instance]);
    }

    /**
     * Get pulse completion users status record for current user.
     *
     * @return object|null user completion data for pulse instance.
     */
    public function get_completion() {
        global $DB;
        return $DB->get_record('pulse_completion', ['userid' => $this->userid, 'pulseid' => $this->cm->instance]);
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {

        return [
            'completionwhenavailable',
            'completionself',
            'completionapproval',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        // Available completion.
        $availablestring = get_string('completion:available', 'pulse');
        $selfstring = get_string('completion:self', 'pulse');
        $approvalstring = get_string('completion:approval', 'pulse');

        if (\mod_pulse\helper::pulse_user_isstudent($this->cm->id)) {
            if ( $this->is_available('completionwhenavailable') ) {
                $state = $this->get_state('completionwhenavailable');
                if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                    $availablestring = get_string('restrictionmet', 'pulse');
                }
            }
            // Self completion descriptions.
            if ($this->is_available('completionself') ) {
                $state = $this->get_state('completionself');
                if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                    $pulse = $this->get_pulse();
                    $date = \mod_pulse\helper::pulse_already_selfcomplete($this->cm->instance, $this->userid);
                    $selfstring = \mod_pulse\helper::get_complete_state_button_text($pulse->completionbtntext, $date);
                }
            }
            // Approval completion description.
            if ($this->is_available('completionapproval') ) {
                $state = $this->get_state('completionapproval');
                if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                    $message = \mod_pulse\helper::pulse_user_approved($this->cm->instance, $this->userid);
                    $approvalstring = html_to_text($message);
                }
            }
        }
        return [
            'completionwhenavailable' => $availablestring,
            'completionself' => $selfstring,
            'completionapproval' => $approvalstring,
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {

        return [
            'completionwhenavailable',
            'completionself',
            'completionapproval',
        ];
    }
}
