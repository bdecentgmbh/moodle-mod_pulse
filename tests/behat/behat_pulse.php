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
 * Behat pulse-related steps definitions.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Course-related steps definitions.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_pulse extends behat_base {

    /**
     * Moodle branch number.
     *
     * @return string Moodle branch number.
     */
    public function moodle_branch() {
        global $CFG;
        return $CFG->branch;
    }

    /**
     * Check that the activity has the given automatic completion condition.
     *
     * @Given /^"((?:[^"]|\\")*)" should have the "((?:[^"]|\\")*)" completion condition type "((?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     * @param string $conditionname The automatic condition name.
     * @param string $completiontype The completion type text.
     */
    public function activity_should_have_the_completion_condition_type(string $activityname,
        string $conditionname, string $completiontype): void {
        global $CFG;
        if ($CFG->branch >= "311") {
            $params = [$activityname, $conditionname];
            $this->execute("behat_completion::activity_should_have_the_completion_condition", $params);
        } else {
            $params = [$activityname, 'pulse', $completiontype];
            $this->execute("behat_completion::activity_has_configuration_completion_checkbox", $params);
        }
    }

    /**
     * Checks if the activity with specified name is maked as complete.
     *
     * @Given /^the "([^"]*)" "([^"]*)" completion condition of "([^"]*)" is displayed as "([^"]*)"$/
     * @param string $conditionname The completion condition text.
     * @param string $completiontype The completion type text.
     * @param string $activityname The activity name.
     * @param string $completionstatus The completion status. Must be either of the following: 'todo', 'done', 'failed'.
     */
    public function activity_completion_condition_displayed_as(string $conditionname, string $completiontype, string $activityname,
            string $completionstatus): void {
        if ($this->moodle_branch() >= "311") {
            $params = [$conditionname, $activityname, $completionstatus];
            $this->execute("behat_completion::activity_completion_condition_displayed_as", $params);
        } else {
            $params = [$activityname, 'pulse', $completiontype];
            $this->execute("behat_completion::activity_marked_as_complete", $params);
        }
    }

    /**
     * Checks if the activity with specified name is maked as complete.
     *
     * @Given /^I should see "([^"]*)" completion condition of "([^"]*)" is displayed as "([^"]*)"$/
     * @param string $conditionname The completion condition text.
     * @param string $activityname The activity name.
     * @param string $completionstatus The completion status. Must be either of the following: 'todo', 'done', 'failed'.
     */
    public function i_should_see_completion_condition_displayed_as(string $conditionname, string $activityname,
            string $completionstatus): void {
        if ($this->moodle_branch() >= "311") {
            $params = [$conditionname, $activityname, $completionstatus];
            $this->execute("behat_completion::activity_completion_condition_displayed_as", $params);
        } else {
            $params = [$activityname];
            $this->execute("behat_general::assert_page_contains_text", [$conditionname]);
        }
    }
}
