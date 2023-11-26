@mod @mod_pulse @pulse_triggercondition  @pulsecondition_course
Feature: Course Completion trigger event.
  In To Verify Pulse Automation Template Conditions for Course Completion as a Teacher.

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
  Scenario: Check the pluse condition course trigger workflow.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 01 |
      | Reference | Welcomemessage  |
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 02 |
      | Reference | Welcomemessage02  |
    Then I create "Welcomemessage" template with the set the condition:
      | Triggers         | Course completion   |
      | Trigger operator | All                 |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    When I open the autocomplete suggestions list
    And I click on "WELCOME MESSAGE 01" item in the autocomplete list
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage   |
    Then I follow "Condition"
    Then I should see "Course completion"
    Then the field "Course completion" matches value "All"
    And I press "Save changes"
    When I open the autocomplete suggestions list
    And I click on "WELCOME MESSAGE 02" item in the autocomplete list
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage2   |
    Then I follow "Condition"
    Then I should see "Course completion" in the "#id_course" "css_element"
    Then the field "Course completion" matches value "Disable"
    Then I wait "5" seconds
    Then I click on "input[name='override[condition_course_status]'].checkboxgroupautomation" "css_element"
    And I set the field "Course completion" to "All"
    And I press "Save changes"
