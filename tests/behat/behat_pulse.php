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
     * Select the conditions are met option on the activity completion tracking .
     *
     * @Given I set the activity completion tracking
     */
    public function i_set_the_activity_completion_tracking() {
        global $CFG;

        if ($CFG->branch >= "401") {
            $this->execute('behat_forms::i_set_the_field_to', ['Add requirements', '1']);
        } else {
            $this->execute('behat_forms::i_set_the_field_to',
            ['Completion tracking', 'Show activity as complete when conditions are met']);
        }
    }

    /**
     * Switches to a pulse new window.
     *
     * @Given /^I switch to a pulse open window$/
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function switch_to_open_window() {
        $names = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow(end($names));
    }

    /**
     * Switches to a pulse new window.
     *
     * @Given /^I click on pulse "([^"]*)" editor$/
     *
     * @param string $editor
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function i_click_on_pulse_editor($editor) {
        global $CFG;

        if ($CFG->branch >= 402) {
            $this->execute('behat_general::i_click_on_in_the',
                ['#'.$editor . '_ifr', 'css_element', '#fitem_'.$editor, 'css_element']);
        } else {
            $this->execute('behat_general::i_click_on_in_the',
                ['#'.$editor . 'editable', 'css_element', '#fitem_'.$editor, 'css_element']);
        }
    }

    /**
     * View assignment submission button.
     *
     * @Given /^I click on assignment submissions button$/
     *
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function i_click_on_assignment_submissions_button() {
        global $CFG;

        if ($CFG->branch >= 405) {
            $this->execute('behat_general::i_click_on_in_the',
                ['Submissions', 'link', '.secondary-navigation', 'css_element']);
        } else {
            $this->execute('behat_general::i_click_on_in_the',
                ['View all submissions', 'link', '.tertiary-navigation', 'css_element']);
        }
    }

    /**
     * Click on user edit menu button on submissions page.
     *
     * @Given /^I click on "([^"]*)" edit menu on submissions page$/
     *
     * @param string $user
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function i_click_on_user_edit_menu_on_submissions_page($user) {
        global $CFG;

        if ($CFG->branch >= 405) {
            $this->execute('behat_general::i_click_on_in_the',
                ['#action-menu-toggle-0', 'css_element', $user, 'table_row']);
        } else {
            $this->execute('behat_general::i_click_on_in_the',
                ['Edit', 'link', $user, 'table_row']);
        }
    }

    /**
     * Click on user edit menu button on submissions page.
     *
     * @Given /^I add pulse to course "([^"]*)" section "([^"]*)" with:$/
     *
     * @param string $coursename The course name.
     * @param string $section The section number.
     * @param TableNode $data The pulse data.
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function i_add_pulse_to_course_section($coursename, $section, TableNode $data) {
        global $CFG;

        if ($CFG->branch >= 405) {
            $this->execute('behat_course::i_add_to_course_section_and_i_fill_the_form_with',
                ['pulse', $coursename, $section, $data]);
        } else {
            $this->execute('behat_general::i_add_to_section_and_i_fill_the_form_with',
                ['pulse', $section, $data]);
        }
    }
}
