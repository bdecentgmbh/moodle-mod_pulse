@mod @mod_pulse

Feature: Check pulse activity works
  In order to check pulse activity works
  As a teacher
  I should create pulse activity

  @javascript
  Scenario: Pulse activity should shown the Content text.
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Test     | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | student   | User 1   | student1@test.com |
      | teacher1 | Teacher   | User 1   | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    Given I log in as "teacher1"
    And I am on "Test" course homepage with editing mode on
    When I add pulse to course "Test" section "1" with:
      | Title   | Test pulse         |
      | Content | Test pulse content |
    Then "Test pulse content" activity should be visible
    And I turn editing mode off
    And "Test pulse content" activity should be visible
    And I log out
    And I log in as "student1"
    And I am on "Test" course homepage
    And I should see "Test pulse content"
    And I log out

  @javascript
  Scenario Outline: Check linked course name placeholders
    Given the following "courses" exist:
      | fullname          | shortname | category |
      | Pulse Test Course | PTC1      | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | student2 | Student   | User 2   | student2@test.com |
      | teacher2 | Teacher   | User 2   | teacher2@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher2 | PTC1   | editingteacher |
      | student2 | PTC1   | student        |
    And the following "activity" exists:
      | activity | pulse         |
      | course   | PTC1          |
      | name     | Link test     |
      | intro    | <placeholder> |
    When I log in as "student2"
    And I am on "Pulse Test Course" course homepage
    Then I should see "<link_text>" in the ".modtype_pulse" "css_element"
    And ".modtype_pulse a[target='_blank'][href*='course/view.php']" "css_element" should exist
    When I click on "<link_text>" "link" in the ".modtype_pulse" "css_element"
    And I switch to a pulse open window
    Then I should see "Pulse Test Course"

    Examples:
      | placeholder               | link_text         |
      | {Course_Fullname_linked}  | Pulse Test Course |
      | {Course_Shortname_linked} | PTC1              |
