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

    /**
     * Checks if the activity with specified name is maked as complete.
     *
     * @Given /^I create demo presets$/
     * @return void
     */
    public function i_create_demo_presets(): void {
        global $CFG;
        require_once($CFG->dirroot.'/mod/pulse/lib.php');
        \mod_pulse\preset::pulse_create_presets();
    }


    /**
     * Open the automation templates listing page.
     *
     * @Given /^I navigate to automation templates$/
     */
    public function i_navigate_to_automation_templates() {
        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ['Plugins > Activity modules > Pulse > Automation templates']);
    }

    /**
     * Open the automation instance listing page for the course.
     *
     * @Given /^I navigate to course "(?P<coursename>(?:[^"]|\\")*)" automation instances$/
     * @param string $coursename Coursename.
     */
    public function i_navigate_to_course_automation_instances($coursename) {
        $this->execute('behat_navigation::i_am_on_course_homepage', [$coursename]);
        $this->execute('behat_navigation::i_select_from_secondary_navigation', get_string('automation', 'pulse'));
    }

    /**
     * Fills a automation template create form with field/value data.
     *
     * @Given /^I create automation template with the following fields to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $data
     */
    public function i_create_automation_template_with_the_following_fields_to_these_values(TableNode $data) {

        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ["Plugins > Activity modules > Pulse > Automation templates"]);
        $this->execute("behat_general::i_click_on", ["Create new template", "button"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$data]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }


    /**
     * Fills a automation template condition form with field/value data.
     *
     * @Given /^I create "([^"]*)" template with the set the condition:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $reference
     * @param TableNode $data
     */
    public function i_create_autoation_template_condition_to_these_values($reference, TableNode $data) {

        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ["Plugins > Activity modules > Pulse > Automation templates"]);
        $this->execute("behat_general::i_click_on_in_the", [".action-edit", "css_element", $reference, "table_row"]);
        $this->execute("behat_general::click_link", ["Condition"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$data]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }


    /**
     * Fills a automation template notification form with field/value data.
     *
     * @Given /^I create "([^"]*)" template with the set the notification:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $reference
     * @param TableNode $data
     */
    public function i_create_automation_template_notification_to_these_values($reference, TableNode $data) {

        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ["Plugins > Activity modules > Pulse > Automation templates"]);
        $this->execute("behat_general::i_click_on_in_the", [".action-edit", "css_element", $reference, "table_row"]);
        $this->execute("behat_general::i_click_on", ["#automation-tabs .nav-item:nth-child(3) a", "css_element"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$data]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }

    /**
     * Select the conditions are met option on the activity completion tracking .
     *
     * @Given I set the activity completion tracking
     */
    public function i_set_the_activity_completion_tracking() {
        global $CFG;

        if ($CFG->branch == "403") {
            $this->execute('behat_forms::i_set_the_field_to', ['id_completion_2', '2']);
        } else {
            $this->execute('behat_forms::i_set_the_field_to',
            ['Completion tracking', 'Show activity as complete when conditions are met']);
        }
    }
}
