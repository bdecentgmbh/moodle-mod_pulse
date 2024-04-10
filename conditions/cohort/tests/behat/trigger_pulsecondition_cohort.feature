@mod @mod_pulse @pulse_triggercondition  @pulsecondition_cohort
Feature: Cohort trigger event.
  In To Verify Pulse Automation Template Conditions for Cohort as a Teacher.

  Background:
    Given the following "course" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | User 1   | user1@test.com    |
      | user2    | User      | User 2   | user2@test.com    |
      | teacher1 | Teacher   | User 1   | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH2    |

  @javascript
  Scenario: Check the pluse condition cohorts trigger workflow.
    Given I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 01 |
      | Reference | Welcomemessage     |
    Then I create automation template with the following fields to these values:
      | Title     | WELCOME MESSAGE 02 |
      | Reference | Welcomemessage02   |
    Then I create "Welcomemessage" template with the set the condition:
      | Triggers         | Member in cohorts |
      | Trigger operator | All               |
    And I am on "Course 1" course homepage
    And I follow "Automation"
    When I open the autocomplete suggestions list
    And I click on "WELCOME MESSAGE 01" item in the autocomplete list
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage |
    Then I follow "Condition"
    Then I should see "Member in cohorts"
    Then the field "Member in cohorts" matches value "All"
    And I should see "Cohorts"
    Then I click on "#fitem_id_condition_cohort_cohorts .form-autocomplete-downarrow" "css_element"
    Then I should see "Cohort 1" in the "#fitem_id_condition_cohort_cohorts .form-autocomplete-suggestions" "css_element"
    Then I should see "Cohort 2" in the "#fitem_id_condition_cohort_cohorts .form-autocomplete-suggestions" "css_element"
    And I press "Save changes"
    When I open the autocomplete suggestions list
    And I click on "WELCOME MESSAGE 02" item in the autocomplete list
    Then I press "Add automation instance"
    And I set the following fields to these values:
      | insreference | Welcomemessage2 |
    Then I follow "Condition"
    Then I should see "Member in cohorts" in the "#id_cohort" "css_element"
    And I should not see "Cohorts" in the "#fitem_id_condition_cohort_cohorts" "css_element"
    Then the field "Member in cohorts" matches value "Disable"
    Then I wait "5" seconds
    Then I click on "input[name='override[condition_cohort_status]'].checkboxgroupautomation" "css_element"
    And I set the field "Member in cohorts" to "All"
    And I should see "Cohorts" in the "#fitem_id_condition_cohort_cohorts" "css_element"
    And I press "Save changes"
