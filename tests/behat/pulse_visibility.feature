@mod @mod_pulse

Feature: Check pulse activity works
  In order to check pulse activity works
  As a teacher
  I should create pulse activity

  @javascript
  Scenario: Pulse activity should shown the Content text.
    Given the following "courses" exist:
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
    Given I log in as "teacher1"
    And I am on "Test" course homepage with editing mode on
    When I add a "pulse" to section "1" and I fill the form with:
      | Title | Test pulse |
      | Content | Test pulse content |
    Then "Test pulse content" activity should be visible
    And I turn editing mode off
    And "Test pulse content" activity should be visible
    And I log out
    And I log in as "student1"
    And I am on "Test" course homepage
    And I should see "Test pulse content"
    And I log out
