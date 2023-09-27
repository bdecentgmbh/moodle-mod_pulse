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
 * Behat pulse action notification - related steps definitions.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;

/**
 * Pulse notification automation - related steps definitions.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_pulseaction_notification extends behat_base {

    /**
     * Fills a automation template condition form with field/value data.
     *
     * @Given /^I create pulse notification template "([^"]*)" "([^"]*)" to these values:$/
     * @param string $title
     * @param string $reference
     * @param TableNode $notificationdata
     */
    public function i_create_pulsenotification_template_with_general($title, $reference, $notificationdata) {

        $this->execute("behat_general::i_click_on", ["Create new template", "button"]);
        $this->execute('behat_forms::i_set_the_field_to', ["Title", $title]);
        $this->execute('behat_forms::i_set_the_field_to', ["Reference", $reference]);
        $this->execute("behat_general::i_click_on_in_the", ["Condition", "link", "#automation-tabs", "css_element"]);
        $this->execute('behat_forms::i_set_the_field_to', ["Triggers", "User enrolment"]);
        $this->execute("behat_general::i_click_on_in_the", ["Notification", "link", "#automation-tabs", "css_element"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$notificationdata]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }

    /**
     * Fills a automation template condition form with field/value data.
     *
     * @Given /^I create pulse notification instance "([^"]*)" "([^"]*)" to these values:$/
     * @param string $title
     * @param string $reference
     * @param TableNode $notificationdata
     */
    public function i_create_pulsenotification_instance_with_general($title, $reference, TableNode $notificationdata) {

        $this->execute('behat_forms::i_set_the_field_to', ["templateid", $title]);
        $this->execute("behat_general::i_click_on", ["Add automation instance", "button"]);
        $this->execute('behat_forms::i_set_the_field_to', ["Reference", $reference]);
        $this->execute("behat_general::i_click_on_in_the", ["Notification", "link", "#automation-tabs", "css_element"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$notificationdata]);
        $this->execute("behat_general::i_click_on", ["Save changes", "button"]);
    }

    /**
     * Create a new an automation instance and fills the form with field/value data.
     *
     * @Given /^I initiate new automation instance of template "([^"]*)" to these values:$/
     * @param String $title
     * @param TableNode $generaldata
     */
    public function i_set_pulsenotification_instance_with_general($title, TableNode $generaldata) {

        $this->execute('behat_forms::i_set_the_field_to', ["templateid", $title]);
        $this->execute("behat_general::i_click_on", ["Add automation instance", "button"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$generaldata]);
    }

    /**
     * Fills the previously initiated instance notification actions form with these values.
     *
     * @Given /^I set previous automation instance notification to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $notificationdata
     */
    public function i_set_previous_instance_notification($notificationdata) {
        $this->execute("behat_general::i_click_on_in_the", ["Notification", "link", "#automation-tabs", "css_element"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$notificationdata]);
    }

    /**
     * Create a new automation template, Fills a automation template form with field/value data.
     *
     * @Given /^I initiate new automation template to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $generaldata
     */
    public function i_set_pulsenotification_template_with_general($generaldata) {
        $this->execute('behat_pulse::i_navigate_to_automation_templates');
        $this->execute("behat_general::i_click_on", ["Create new template", "button"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$generaldata]);
    }

    /**
     * Fills the previously initiated templates notification actions form with these values.
     *
     * @Given /^I set previous automation template notification to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $notificationdata
     */
    public function i_set_previous_template_notification($notificationdata) {

        $this->execute("behat_general::i_click_on_in_the", ["Notification", "link", "#automation-tabs", "css_element"]);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', [$notificationdata]);

    }
}
