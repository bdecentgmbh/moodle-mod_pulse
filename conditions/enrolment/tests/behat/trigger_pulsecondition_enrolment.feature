@mod @mod_pulse @pulse_triggercondition  @pulsecondition_enrolment
Feature: Course Enrolment trigger event.
  In To Verify Pulse Automation Template Conditions for Course Enrolment as a Teacher.

  Background:
    Given the following "course" exist:
      | fullname    | shortname | category | enablecompletion |
      | Course 1    | C1        | 0        |    1             |
    And the following "users" exist:
      | username | firstname | lastname     | email |
      | user1    | User      | User 1       | user1@test.com    |
      | user2    | User      | User 2       | user2@test.com |
      | teacher1 | Teacher   | User 1       | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student |
      | user2    | C1     | student |

  @javascript
  Scenario: Check the pluse condition enrolment trigger workflow.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 01 |
      | Reference | Welcomemessage  |
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 02 |
      | Reference | Welcomemessage02  |
    Then I create "Welcomemessage" template with the set the condition:
      | Triggers         | User enrolment   |
      | Trigger operator | All                 |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    And I set the field "templateid" to "WELCOME MESSAGE 01"
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage   |
    Then I follow "Condition"
    Then I should see "User enrolment"
    Then the field "User enrolment" matches value "All"
    And I press "Save changes"
    And I set the field "templateid" to "WELCOME MESSAGE 02"
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage2   |
    Then I follow "Condition"
    Then I should see "User enrolment" in the "#id_enrolment" "css_element"
    Then the field "User enrolment" matches value "Disable"
    Then I wait "5" seconds
    Then I click on "input[name='override[condition_enrolment_status]'].checkboxgroupautomation" "css_element"
    And I set the field "User enrolment" to "All"
    And I press "Save changes"
