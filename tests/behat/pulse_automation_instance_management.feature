@mod @mod_pulse @pulse_automation_instance @_switch_window
Feature: Pulse automation instances management
  In order to check the the pulse automation template works
  As a teacher.

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT1     | CAT3     |
    And the following "course" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | CAT1     | 1                |
      | Course 3 | C3        | CAT2     | 1                |
      | Course 4 | C4        | CAT3     | 1                |
    And the following "activities" exist:
      | activity | name    | course | idnumber | intro            | section | completion |
      | assign   | Assign1 | C1     | assign1  | Page description | 1       | 1          |
      | assign   | Assign2 | C1     | assign2  | Page description | 2       | 1          |
      | assign   | Assign3 | C1     | assign3  | Page description | 3       | 1          |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | student   | User 1   | student1@test.com |
      | teacher1 | Teacher   | User 1   | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Enable completion tracking" to "Yes"
    And I press "Save and display"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"
    And I click on "Course completion" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Assign1" to "1"
    And I press "Save changes"

    Then I create automation template with the following fields to these values:
      | Title     | Template1 |
      | Reference | temp1     |
    And I click on "Create new template" "button"
    Then I set the following fields to these values:
      | Title     | Notification1 |
      | Reference | notification1 |
    And I press "Save changes"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "0" in the "Course 1" "table_row"

    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "0(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "notification1" "table_row"

    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"
    And I log out

  @javascript
  Scenario: Instance mixed status check
    Given I log in as "admin"
    # Enable / Disable Instance Mixed option
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "3" in the "Course 1" "table_row"
    And ".menu-item-actions .badge" "css_element" should not exist in the "Course 1" "table_row"
    And I click on ".action-report" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I click on ".pulse-instance-status-switch" "css_element" in the ".menu-item-actions" "css_element"
    And I switch to the main window
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "Mixed" in the "Course 1" "table_row"
    And I should see "3" in the "Course 1" "table_row"
    And I wait "15" seconds
