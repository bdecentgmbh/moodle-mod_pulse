@mod @mod_pulse @pulse_triggercondition @pulsecondition_activity
Feature: Activity trigger event.
  In To Verify Pulse Automation Template Conditions for Activity as a Teacher.

  Background:
    Given the following "course" exist:
      | fullname    | shortname | category | enablecompletion |
      | Course 1    | C1        | 0        |    1             |
    And the following "activities" exist:
      | activity | name        | course | idnumber | completion |
      | page     | TestPage 01 | C1     | page1    |    1       |
      | page     | TestPage 02 | C1     | page2    |    1       |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | student | User 1 | student1@test.com |
      | teacher1 | Teacher | User 1 | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student |

  @javascript
  Scenario: Check the pluse condition activity trigger workflow.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 01 |
      | Reference | Welcomemessage  |
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 02 |
      | Reference | Welcomemessage02  |
    Then I create "Welcomemessage" template with the set the condition:
      | Triggers         | Activity completion |
      | Trigger operator | All                 |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And I set the field "templateid" to "WELCOME MESSAGE 01"
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage   |
    Then I follow "Condition"
    Then I should see "Activity completion"
    Then the field "Activity completion" matches value "All"
    And I should see "Select activities"
    Then I click on "#fitem_id_condition_activity_modules .form-autocomplete-downarrow" "css_element"
    Then I should see "TestPage 01" in the "#fitem_id_condition_activity_modules .form-autocomplete-suggestions" "css_element"
    Then I should see "TestPage 02" in the "#fitem_id_condition_activity_modules .form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    And I set the field "templateid" to "WELCOME MESSAGE 02"
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage2   |
    Then I follow "Condition"
    Then I should see "Activity completion"
    And I should not see "Select activities"
    Then the field "Activity completion" matches value "Disable"
    Then I wait "5" seconds
    Then I click on "input[name='override[condition_activity_status]'].checkboxgroupautomation" "css_element"
    And I set the field "Activity completion" to "All"
    And I should see "Select activities"
    And I press "Save changes"
