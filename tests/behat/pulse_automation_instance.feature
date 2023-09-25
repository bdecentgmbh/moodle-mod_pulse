@mod @mod_pulse @pulse_automation_instance
Feature: Pulse automation instances
  In order to check the the pulse automation template works
  As a teacher.

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT1     | CAT3     |
    And the following "course" exist:
      | fullname    | shortname | category |
      | Course 1    | C1        | 0        |
      | Course 2    | C2        | CAT1     |
      | Course 3    | C3        | CAT2     |
      | Course 4    | C4        | CAT3     |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | student | User 1 | student1@test.com |
      | teacher1 | Teacher | User 1 | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student |

  @javascript
  Scenario: Create the automation template instance.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
    | Title     | WELCOME MESSAGE |
    | Reference | Welcomemessage  |
    | Visibility| Show            |
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | override[title] | 1                  |
      | Title     | WELCOME MESSAGE Instance |
      | insreference | Welcomemessageinstance   |
    And I press "Save changes"
    Then I should see "Template inserted successfully"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I should see "Welcomemessageinstance" in the "#pulse_automation_template .template-reference" "css_element"

  @javascript
  Scenario: Edit the automation template instance.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
    | Title     | WELCOME MESSAGE |
    | Reference | Welcomemessage  |
    | Visibility| Show            |
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | Welcomemessage   |
    And I press "Save changes"
    Then I should see "Template inserted successfully"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I should see "Welcomemessage" in the "#pulse_automation_template .template-reference" "css_element"
    And "#pulse_automation_template .menu-item-actions .action-edit" "css_element" should exist
    Then I click on ".action-edit" "css_element" in the "WELCOME MESSAGE" "table_row"
    Then I should see "Edit instance"
    And I set the following fields to these values:
      | override[title]     | 1  |
      | Title     | DEMO MESSAGE  |
      | insreference | demomessageinstance   |
    Then I press "Save changes"
    And I should see "DEMO MESSAGE" in the "#pulse_automation_template" "css_element"
    And I should not see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I should see "demomessage" in the "#pulse_automation_template .template-reference" "css_element"
    And I should not see "Welcomemessageinstance" in the "#pulse_automation_template .template-reference" "css_element"

  @javascript
  Scenario: Dupilcate the automation template instance.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
    | Title     | WELCOME MESSAGE |
    | Reference | Welcomemessage  |
    | Visibility| Show            |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | override[title] | 1                  |
      | Title     | WELCOME MESSAGE Instance |
      | insreference| Welcomemessageinstance|
    And I press "Save changes"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I should not see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(2)" "css_element"
    Then I click on "#pulse_automation_template tbody tr:nth-child(1) .action-copy" "css_element"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(2)" "css_element"

  @javascript
  Scenario: Delete the automation template instance.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
      | Visibility| Show            |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | Welcomemessage   |
    And I press "Save changes"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    Then I click on "#pulse_automation_template .action-delete" "css_element"
    #Then I should see "Confirmation" in the ".confirmation-dialogue" "css_element"
    And I click on "Cancel" "button" in the ".confirmation-dialogue" "css_element"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    Then I click on "#pulse_automation_template .action-delete" "css_element"
    And I click on "Yes" "button" in the ".confirmation-dialogue" "css_element"
    Then I should see "Nothing to display"
    And "#pulse_automation_template" "css_element" should not exist

  @javascript
  Scenario: Check the multiple automation template instance.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
    Then I create automation template with the following fields to these values:
      | Title     | Notification |
      | Reference | notification |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    Then I should see "Notification" in the ".template-add-form .custom-select" "css_element"
    Then I click on "Add automation instance" "button"
    Then the field "Title" matches value "WELCOME MESSAGE"
    And I set the following fields to these values:
      | insreference | Welcomemessageinstance   |
    And I press "Save changes"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I set the field "templateid" to "Notification"
    Then I click on "Add automation instance" "button"
    Then the field "Title" matches value "Notification"
    And I set the following fields to these values:
      | insreference | notificationinstance   |
    And I press "Save changes"
    And I should see "Notification" in the "#pulse_automation_template tbody tr:nth-child(2)" "css_element"
    And I set the field "templateid" to "WELCOME MESSAGE"
    Then I click on "Add automation instance" "button"
    Then the field "Title" matches value "WELCOME MESSAGE"
    And I set the following fields to these values:
      | override[title] | 1                  |
      | insreference | Welcomemessageinstance2   |
      | Title     | WELCOME CONTENT |
    And I press "Save changes"
    And I should see "WELCOME CONTENT"
