@mod @mod_pulse @pulse_appearance
Feature: Pulse appearance display modes
  In order to check the the pulse apearance works
  As a teacher
  I should see the alert box for box display mode.

  Background: Create pulse instance.
    Given the following "course" exist:
      | fullname| shortname | category |
      | Test | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | student | User 1 | student1@test.com |
      | teacher1 | Teacher | User 1 | teacher1@test.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activity" exists:
      | activity | pulse                |
      | course   | C1                   |
      | idnumber | 00001                |
      | name     | pulse box mode       |
      | intro    | pulse box mode       |
      | section  | 1                    |

  @javascript
  Scenario: Change the display mode to box.
    Given I log in as "teacher1"
    And I am on "Test" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I follow "Expand all"
    And I set the following fields to these values:
      | Display mode | Box         |
      | Box Type     | Success     |
      | Box Icon     | fa-clone    |
    And I set the field "Send Pulse notification" to "0"
    And I press "Save and return to course"
    Then ".pulse-box" "css_element" should exist in the ".modtype_pulse" "css_element"
    And ".alert-success" "css_element" should exist in the ".pulse-box" "css_element"
    And ".fa-clone" "css_element" should exist in the ".pulse-box-icon" "css_element"
    And I log out
    Then I log in as "student1"
    And I am on "Test" course homepage
    Then I should see "pulse box mode"
    And ".pulse-box" "css_element" should exist in the ".modtype_pulse" "css_element"
    And ".alert-success" "css_element" should exist in the ".pulse-box" "css_element"
    And ".fa-clone" "css_element" should exist in the ".pulse-box-icon" "css_element"

  @javascript
  Scenario: Add custom class to pulse.
    Given I log in as "teacher1"
    And I am on "Test" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I follow "Expand all"
    And I set the following fields to these values:
      | CSS class | pulse-appearance-custom-class |
    And I set the field "Send Pulse notification" to "0"
    And I press "Save and return to course"
    Then ".pulse-appearance-custom-class" "css_element" should exist in the "#section-1" "css_element"
    And I log out
    Then I log in as "student1"
    And I am on "Test" course homepage
    Then I should see "pulse box mode"
    And ".pulse-appearance-custom-class" "css_element" should exist in the "#section-1" "css_element"
