@mod @mod_pulse @core_completion
Feature: View activity completion information in the pulse activity
  In order to have visibility of pulse completion requirements
  As a student
  I need to be able to view my pulse completion progress

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student    | User 1 | student1@test.com |
      | teacher1 | Teacher   | User 1 | teacher1@test.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student        |
      | teacher1 | C1 | editingteacher |
    And the following "activity" exists:
      | activity    | pulse            |
      | course      | C1               |
      | idnumber    | pulse1           |
      | name        | Test pulse 1     |
      | intro       | Test pulse 1     |
      | pulse       | 0                |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I expand all fieldsets
    And I click on "[name='pulse'][type='checkbox']" "css_element"
    And I press "Save and return to course"

  @javascript
  Scenario: View automatic completion items
    Given I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I set the following fields to these values:
    | Completion tracking  | Show activity as complete when conditions are met |
    And I click on "Completion when available" "checkbox"
    And I press "Save and return to course"
    # Teacher view.
    # And "Test pulse 1" should have the "" or "auto" completion condition
    And "Test pulse 1" should have the "Restrictions must be met" completion condition type "auto"
    And I log out
    # Student view.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Test pulse 1"
    Then the "Restriction met" "auto" completion condition of "Test pulse 1" is displayed as "done"

  @javascript
  Scenario: Use manual mark as complete completion
    Given I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met    |
    And I click on "Mark as complete by student to complete this activity" "checkbox"
    And I press "Save and return to course"
    # Teacher view.
    # confirm the activity completion enabled.
    And "Test pulse 1" should have the "Mark complete" completion condition type "auto"
    And I should not see "Mark complete" in the ".modtype_pulse .contentwithoutlink" "css_element"
    And I log out
    # Student view.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Test pulse 1"
    And I should see "Mark complete" in the ".pulse-completion-btn" "css_element"
    When I click on "Mark complete" "link"
    And I should see "Marked as completed" in the ".notifications" "css_element"
    Then I should see "Self marked complete on" completion condition of "Test pulse 1" is displayed as "done"

  @javascript
  Scenario: Use manual completion Required by approval complete.
    Given I am on "Course 1" course homepage with editing mode on
    And I click on "Edit" "link" in the ".modtype_pulse" "css_element"
    And I click on ".menu-action-text" "css_element" in the ".modtype_pulse" "css_element"
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met    |
    And I click on "Require approval by one of the following roles" "checkbox"
    And I click on ".form-autocomplete-downarrow" "css_element" in the "#fgroup_id_completionrequireapproval" "css_element"
    And I click on "Teacher" "list_item" in the "#fgroup_id_completionrequireapproval [class='form-autocomplete-suggestions']" "css_element"
    And I press "Save and return to course"
    # Teacher view.
    And "Test pulse 1" should have the "Approval required" completion condition type "auto"
    And I should see "Approve users" in the ".pulse-completion-btn" "css_element"
    And I click on "Approve users" "link" in the ".pulse-completion-btn" "css_element"
    And I should see "Student User 1" in the "participants" "table"
    When I click on "Approve" "link" in the "Student User 1" "table_row"
    And I should see "Approval successful" in the ".notifications" "css_element"
    And I should see "Approved" in the "Student User 1" "table_row"
    And I log out
    # Student view
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Test pulse 1"
    Then I should see "Approved on" completion condition of "Test pulse 1" is displayed as "done"
