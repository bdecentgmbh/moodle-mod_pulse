@mod @mod_pulse @pulse_automation_template
Feature: Pulse automation templates
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
  Scenario: Check the automation template.
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I should see "Automation templates" in the "#region-main h2" "css_element"
    And I should see "Create new template"
    Then I click on "Create new template" "button"
    And I set the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
      | Visibility|  Show           |
      | Status    |  Enabled        |
    Then I press "Save changes"
    Then I should see "Template inserted successfully"
    Then I should see "Automation templates"
    Then "#region-main-box .flexible#pulse_automation_template" "css_element" should exist
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I should see "Welcomemessage" in the "#pulse_automation_template .template-reference" "css_element"
    And "#pulse_automation_template .menu-item-actions .action-edit" "css_element" should exist
    Then I create automation template with the following fields to these values:
      | Title     |  Triggers           |
      | Reference | Conditiontriggers   |
    Then I should see "Template inserted successfully"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I should see "Welcomemessage" in the "#pulse_automation_template tbody tr:nth-child(1) .template-reference" "css_element"
    And I should see "Triggers" in the "#pulse_automation_template tbody tr:nth-child(2)" "css_element"
    And I should see "Conditiontriggers" in the "#pulse_automation_template tbody tr:nth-child(2) .template-reference" "css_element"

  @javascript
  Scenario: Edit the automation template
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
    Then I should see "Template inserted successfully"
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I should see "Welcomemessage" in the "#pulse_automation_template tbody tr:nth-child(1) .template-reference" "css_element"
    And "#pulse_automation_template .menu-item-actions .action-edit" "css_element" should exist
    Then I click on ".action-edit" "css_element" in the "WELCOME MESSAGE" "table_row"
    Then I should see "Edit template"
    And I set the following fields to these values:
      | Title     |  Triggers           |
      | Reference | Conditiontriggers  |
    Then I press "Save changes"
    Then I should see "Template updated successfully"
    And I should see "Triggers" in the "#pulse_automation_template tbody tr:nth-child(1)" "css_element"
    And I should see "Conditiontriggers" in the "#pulse_automation_template tbody tr:nth-child(1) .template-reference" "css_element"

  @javascript
  Scenario: Check Visibility of automation template
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
      | Visibility| Show            |
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I am on "Course 1" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    Then I should see "Automation" in the "#region-main h2" "css_element"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I click on ".action-edit" "css_element" in the "WELCOME MESSAGE" "table_row"
    And I set the field "Visibility" to "Hidden"
    Then I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should not exist
    Then I should not see "WELCOME MESSAGE" in the ".template-add-form" "css_element"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I click on ".action-show" "css_element" in the "WELCOME MESSAGE" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form" "css_element"

  @javascript
  Scenario: Check Status of automation template
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
      | Visibility| Show            |
      | Status    | Enable          |
    And I should see "WELCOME MESSAGE" in the "#pulse_automation_template" "css_element"
    And I am on "Course 1" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    Then I should see "Automation" in the "#region-main h2" "css_element"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I click on ".action-edit" "css_element" in the "WELCOME MESSAGE" "table_row"
    And I set the field "Visibility" to "Hidden"
    Then I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should not exist
    Then I should not see "WELCOME MESSAGE" in the ".template-add-form" "css_element"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I click on ".action-show" "css_element" in the "WELCOME MESSAGE" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form" "css_element"

  @javascript
  Scenario: Check Available in course categories for automation template
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
        | Title                          | WELCOME MESSAGE |
        | Reference                      | Welcomemessage  |
        | Available in course categories | Category 1      |
    And I am on "Course 1" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    # Course 2
    And I am on "Course 2" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should not exist
    Then I should not see "WELCOME MESSAGE" in the ".template-add-form" "css_element"
    # Course 3
    And I am on "Course 2" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should not exist
    Then I should not see "WELCOME MESSAGE" in the ".template-add-form" "css_element"
    # Update the template
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I click on ".action-edit" "css_element" in the "WELCOME MESSAGE" "table_row"
    And I set the field "Available in course categories" to "Category 1, Cat 1"
    Then I press "Save changes"
    And I am on "Course 1" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    # Course 2
    And I am on "Course 2" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should exist
    Then I should see "WELCOME MESSAGE" in the ".template-add-form .custom-select" "css_element"
    # Course 3
    And I am on "Course 3" course homepage
    Then I should see "Automation"
    And I follow "Automation"
    And ".template-add-form .custom-select#id_templateid" "css_element" should not exist
    Then I should not see "WELCOME MESSAGE" in the ".template-add-form" "css_element"

  @javascript
  Scenario: Check condition for automation template
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
        | Title                          | WELCOME MESSAGE |
        | Reference                      | Welcomemessage  |
        | Available in course categories | Category 1      |
    Then I create "Welcomemessage" template with the set the condition:
        | Triggers         | Activity completion, Member in cohorts |
        | Trigger operator | All                 |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I press "Add automation instance"
    Then I follow "Condition"
    Then I should see "Activity completion"
    Then I should see "Member in cohorts"
    Then the field "Activity completion" matches value "All"
    Then the field "Select activities" matches value ""
    Then the field "Member in cohorts" matches value "All"
    Then the field "Cohorts" matches value ""

  @javascript
  Scenario: Check Notification for automation template
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    Then I create automation template with the following fields to these values:
        | Title                          | WELCOME MESSAGE |
        | Reference                      | Welcomemessage  |
        | Available in course categories | Category 1      |
    Then I create "Welcomemessage" template with the set the condition:
        | Triggers         | Activity completion, Member in cohorts |
        | Trigger operator | All                                    |
    Then I create "Welcomemessage" template with the set the notification:
        | Sender      |  Group teacher    |
        | Interval    |  Once            |
        | Cc          |  Teacher         |
        | Bcc         |  Manager         |
        | Subject     |  Demo MESSAGE    |
        | Header content | Lorem Ipsum is therefore always free from repetition, injected humour|
        | Static content | There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form |
    Then I should see "Template updated successfully"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I press "Add automation instance"
    Then I click on "#automation-tabs .nav-item:nth-child(3) a" "css_element"
    Then I wait "10" seconds
    Then the field "Sender" matches value "Group teacher"
    Then the field "Interval" matches value "Once"
    Then the field "Subject" matches value "Demo MESSAGE"

  @javascript
  Scenario: Check Notification for automation overrides badge
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | Welcomemessage  |
    Then I create automation template with the following fields to these values:
      | Title     | Notification |
      | Reference | notification |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And I set the field "templateid" to "WELCOME MESSAGE"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | Welcomemessageinstance   |
    And I press "Save changes"
    And I set the field "templateid" to "WELCOME MESSAGE"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | Welcomemessageinstance2   |
    And I press "Save changes"
    And I set the field "templateid" to "WELCOME MESSAGE"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | Welcomemessageinstance3   |
    And I press "Save changes"
    And I set the field "templateid" to "Notification"
    Then I click on "Add automation instance" "button"
    And I set the following fields to these values:
      | insreference | notificationinstance   |
    And I press "Save changes"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    And I should see "3(0)" in the "WELCOME MESSAGE" "table_row"
    And I should see "1(0)" in the "notification" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on ".pulse-instance-status-switch" "css_element" in the "Welcomemessageinstance" "table_row"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    And I should see "3(1)" in the "WELCOME MESSAGE" "table_row"
    And I should see "1(0)" in the "notification" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Automation"
    Then I click on ".action-hide" "css_element" in the "Welcomemessageinstance2" "table_row"
    Then I click on ".action-hide" "css_element" in the "notificationinstance" "table_row"
    And I navigate to "Plugins > Activity modules > Pulse > Automation templates" in site administration
    And I should see "3(2)" in the "WELCOME MESSAGE" "table_row"
    And I should see "1(1)" in the "notification" "table_row"
