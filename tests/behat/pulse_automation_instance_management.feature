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
  Scenario: Add instances in the Instance Management
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkdelete-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"

    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 1')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "2(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "2" in the "Course 1" "table_row"

    And I click on ".action-add-instance" "css_element" in the "Course 2" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "3(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 2" "table_row"

    And I click on "Select all without instances" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "5(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"

    And I click on ".fa-calendar" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    Then I should see "Template1" in the "temp1C1" "table_row"

  @javascript
  Scenario: Modify the instances with bulk action group
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    # Delete the instances for the Course
    And I click on ".action-delete" "css_element" in the "Course 2" "table_row"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "3(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 2" "table_row"

    # Bulk delete the instances for the course
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 1')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkdelete-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "2(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"

    # Enable / Disable the instances with bulk option
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 3')]//input[@type='checkbox']" to "bc[]"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 4')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkdisable-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And ".pulse-manage-instance-status-switch .custom-control-input:checked" "css_element" should not exist in the "Course 3" "table_row"
    And ".pulse-manage-instance-status-switch .custom-control-input:checked" "css_element" should not exist in the "Course 4" "table_row"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 1')]//input[@type='checkbox']" to "bc[]"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 2')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkenable-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And ".pulse-manage-instance-status-switch .custom-control-input:checked" "css_element" should exist in the "Course 1" "table_row"
    And ".pulse-manage-instance-status-switch .custom-control-input:checked" "css_element" should exist in the "Course 3" "table_row"

    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"

    And I should see "5(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "2" in the "Course 1" "table_row"

    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Template1" in the "temp1C1" "table_row"
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I close all opened windows
    And I click on ".action-report" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    Then "Template1" "table_row" should not exist
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I close all opened windows

    # Filter the available course categories
    And I click on "#tool-details-tab" "css_element"
    And I wait "2" seconds
    And I set the following fields to these values:
      | Available in course categories | Category 1, Cat 1 |
    And I press "Save changes"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"
    And "Course 3" "table_row" should not exist
    And I click on "#tool-details-tab" "css_element"
    And I click on "span.badge" "css_element" in the "#fitem_id_categories .form-autocomplete-selection" "css_element"
    And I wait "1" seconds
    And I click on "span.badge" "css_element" in the "#fitem_id_categories .form-autocomplete-selection" "css_element"
    And I press "Save changes"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 3" in the "Cat 2" "table_row"
    And I wait "5" seconds

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

  @javascript
  Scenario: Utilizing the filter option in instance management
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    # Filtering "Category"
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Category | Category 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And "Cat 1" "table_row" should not exist
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Category | All |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"
    And I wait "5" seconds

    # Filtering "Course"
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Course name | Course 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And "Cat 2" "table_row" should not exist
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Course name | All |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"

    # Filtering "Number of instances"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "2" in the "Course 1" "table_row"
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of instance | 2 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "2" in the "Category 1" "table_row"
    And "Cat 1" "table_row" should not exist
    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of instance |  |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "2" in the "Category 1" "table_row"
    And I should see "1" in the "Cat 1" "table_row"

  # Filtering "Number of overrides"
  @javascript
  Scenario: Utilizing the number of overrides filter option in instance management
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    # Number of override: 1
    And I click on ".action-report" "css_element" in the "Category 1" "table_row"
    And I switch to a second window
    And I should see "Template1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title     | Template01 |
      | Reference | C2         |
    And I wait "2" seconds
    And I press "Save changes"
    And I should see "Template01" in the "temp1C2" "table_row"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And "Cat 1" "table_row" should not exist

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 0 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 2" in the "Cat 1" "table_row"
    And I should see "Course 3" in the "Cat 2" "table_row"
    And I should see "Course 4" in the "Cat 3" "table_row"
    And "Category 1" "table_row" should not exist

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides |  |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"

    And I click on ".action-edit" "css_element" in the "Course 2" "table_row"
    And I switch to a pulse open window
    And I should see "Template1" in the "temp1C2" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template02 |
    And I press "Save changes"
    And I should see "Template02" in the "temp1C2" "table_row"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"
    And "Cat 2" "table_row" should not exist

    # Number of override: 2
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a pulse open window
    And I click on ".action-edit" "css_element" in the "Template01" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_notes" "css_element"
    And I set the following fields to these values:
      | Internal notes | Demo Template01 |
    And I press "Save changes"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 2 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And "Cat 1" "table_row" should not exist

    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a pulse open window
    And I click on ".action-edit" "css_element" in the "Template01" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_notes" "css_element"
    And I press "Save changes"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 2 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Nothing to display" in the "#manage-instance-table" "css_element"

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I wait "2" seconds

  @javascript
  Scenario: Delete action for the template
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I should see "Template1" in the "temp1C1" "table_row"
    And I click on ".form-autocomplete-downarrow" "css_element" in the "#fitem_id_templateid .align-items-start" "css_element"
    And "Template1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And "Notification1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And I close all opened windows
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I click on ".action-delete" "css_element" in the "notification1" "table_row"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And "Notification1" "table_row" should not exist
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Template1" in the "temp1C1" "table_row"
    And "notification1C1" "table_row" should not exist
    And I click on ".form-autocomplete-downarrow" "css_element" in the "#fitem_id_templateid .align-items-start" "css_element"
    And "Template1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And "Notification1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should not be visible
    And I wait "2" seconds

  @javascript
  Scenario: Additional column added for the report source
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I press "Mark as done"
    And I log out

    And I log in as "admin"
    And I navigate to course "Course 1" automation instances
    And I should see "Template1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "#id_override_title" "css_element" in the "#fitem_id_title" "css_element"
    And I set the field "Title" to "Template01"
    And I click on "Condition" "link" in the "#automation-tabs" "css_element"
    And I click on "#id_override_triggeroperator" "css_element" in the "#fitem_id_triggeroperator" "css_element"
    And I set the field "Trigger operator" to "Any"
    And I click on "#id_override_condition_activity_status" "css_element" in the "#fitem_id_condition_activity_status" "css_element"
    And I set the field "Activity completion" to "All"
    And I wait "2" seconds
    And I set the field "Select activities" to "Assign1"
    And I click on "Notification" "link" in the "#automation-tabs" "css_element"
    And I click on "#id_override_pulsenotification_recipients" "css_element" in the "#fitem_id_pulsenotification_recipients" "css_element"
    And I set the field "Recipients" to "Student"
    And I click on "#id_override_pulsenotification_cc" "css_element" in the "#fitem_id_pulsenotification_cc" "css_element"
    And I set the field "Cc" to "Teacher"
    And I press "Save changes"

    And I wait "3" seconds
    And I click on ".action-report" "css_element" in the "Template01" "table_row"
    And I switch to a second window
    And I should see "student User 1" in the "Course 1" "table_row"
    And I log out

    And I log in as "admin"
    And I navigate to "Reports > Report builder > Custom reports" in site administration
    And I click on "New report" "button"
    And I set the following fields in the "New report" "dialogue" to these values:
      | Name                  | My report    |
      | Report source         | Notification |
      | Include default setup | 0            |
    And I click on "Save" "button" in the "New report" "dialogue"
    And I wait until the page is ready
    Then I should see "My report"
    And I click on "Template title" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I should see "Template1" in the ".reportbuilder-table tbody tr td" "css_element"
    And I click on "Template reference" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I should see "temp1" in the "Template1" "table_row"
    And I click on "Instance title" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds
    And I should see "Template01" in the "temp1" "table_row"
    And I click on "Instance reference" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds
    And I should see "C1" in the "temp1" "table_row"

  @javascript
  Scenario: A instance override capabiltiy check
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I log out

    # Allow the instance override
    And I log in as "teacher1"
    And I navigate to course "Course 1" automation instances
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template01 |
    And I wait "2" seconds
    And I press "Save changes"
    And I wait "10" seconds
    And I should see "Template01" in the "notification1C1" "table_row"
    And I close all opened windows
    And I log out

    # Allow the instance override
    And I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I click on ".fa-cog" "css_element" in the "teacher" "table_row"
    And I set the field "mod/pulse:overridetemplateinstance" to "0"
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I navigate to course "Course 1" automation instances
    And I click on ".action-edit" "css_element" in the "Template01" "table_row"
    And ".form-control[disabled='disabled']" "css_element" should exist in the "#fitem_id_title" "css_element"
    And I press "Save changes"
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I close all opened windows

  @javascript
  Scenario: Placeholder for extending assignment submission deadlines
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I set the following fields to these values:
      | Due date | ##1 March 2024## |
    And I press "Save and display"

    And I am on the "Assign2" "assign activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I set the following fields to these values:
      | Due date | ##31 March 2024## |
    And I press "Save and display"

    And I am on the "Assign3" "assign activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I set the following fields to these values:
      | Due date | ##1 April 2024## |
    And I press "Save and display"

    And I navigate to course "Course 1" automation instances
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Notification" "link" in the "#automation-tabs" "css_element"
    And I click on "#id_override_pulsenotification_headercontent_editor" "css_element" in the "#fitem_id_pulsenotification_headercontent_editor" "css_element"
    And I wait "2" seconds
    And I click on ".fa-angle-double-down" "css_element" in the "#header-email-vars-button" "css_element"
    And I click on "Show more" "link" in the ".User_field-placeholders" "css_element"
    And I click on "Show less" "link" in the ".User_field-placeholders" "css_element"
    And I click on pulse "id_pulsenotification_headercontent_editor" editor
    And I click on "Extensions" "link" in the ".Assignment_field-placeholders .placeholders" "css_element"
    And I click on "Preview" "button" in the "#fitem_id_pulsenotification_preview" "css_element"
    And I should see "No extensions have been granted for upcoming assignments." in the "Preview" "dialogue"
    And I click on ".close" "css_element" in the "Preview" "dialogue"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I should see "The due date for this assignment has now passed" in the "Time remaining" "table_row"
    And I am on the "Assign3" "assign activity" page
    And I should see "The due date for this assignment has now passed" in the "Time remaining" "table_row"
    And I log out

    # Due date extension in assign1 activity
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I click on "View all submissions" "link" in the ".tertiary-navigation" "css_element"
    And I click on "Edit" "link" in the "student User 1" "table_row"
    And I choose "Grant extension" in the open action menu
    And I set the following fields to these values:
      | Extension due date | ##1 July 2024## |
    And I press "Save changes"
    And I should see "Extension granted until: Monday, 1 July 2024, 12:00 AM" in the "student User 1" "table_row"

    # Due date extension in assign3 activity
    And I am on the "Assign3" "assign activity" page
    And I click on "View all submissions" "link" in the ".tertiary-navigation" "css_element"
    And I click on "Edit" "link" in the "student User 1" "table_row"
    And I choose "Grant extension" in the open action menu
    And I set the following fields to these values:
      | Extension due date | ##2 August 2024 14:27## |
    And I press "Save changes"
    And I should see "Extension granted until: Friday, 2 August 2024, 2:27 PM" in the "student User 1" "table_row"

    And I navigate to course "Course 1" automation instances
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Notification" "link" in the "#automation-tabs" "css_element"
    And I click on "Preview" "button" in the "#fitem_id_pulsenotification_preview" "css_element"
    And I wait "10" seconds
    And I should see "Assign1: Monday, 1 July 2024, 12:00 AM(Previously: Friday, 1 March 2024, 12:00 AM)" in the "Preview" "dialogue"
    And I should see "Assign3: Friday, 2 August 2024, 2:27 PM(Previously: Monday, 1 April 2024, 12:00 AM)" in the "Preview" "dialogue"
    And I click on ".close" "css_element" in the "Preview" "dialogue"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I should see "Monday, 1 July 2024, 12:00 AM" in the "Extension due date" "table_row"
    And I press "Mark as done"

    And I am on the "Assign3" "assign activity" page
    And I should see "Friday, 2 August 2024, 2:27 PM" in the "Extension due date" "table_row"
    And I press "Mark as done"
    And I log out

    And I log in as "admin"
    And I navigate to course "Course 1" automation instances
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Notification" "link" in the "#automation-tabs" "css_element"
    And I click on "Preview" "button" in the "#fitem_id_pulsenotification_preview" "css_element"
    And I should see "No extensions have been granted for upcoming assignments." in the "Preview" "dialogue"
    And I click on ".close" "css_element" in the "Preview" "dialogue"
    And I press "Save changes"

  @javascript
  Scenario: Notification of events condition for extending assignment submission deadlines
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I set the following fields to these values:
      | Due date | ##1 March 2024## |
    And I press "Save and display"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I should see "The due date for this assignment has now passed" in the "Time remaining" "table_row"
    And I log out

    And I log in as "admin"
    And I navigate to course "Course 1" automation instances
    And I should see "Template1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template1" "table_row"
    And I click on "Condition" "link" in the "#automation-tabs" "css_element"
    And I click on "#id_override_triggeroperator" "css_element" in the "#fitem_id_triggeroperator" "css_element"
    And I set the field "Trigger operator" to "Any"
    And I click on "#id_override_condition_events_status" "css_element" in the "#fitem_id_condition_events_status" "css_element"
    And I set the field "Events completion" to "All"
    And I open the autocomplete suggestions list in the "Event" "fieldset"
    And I click on "An extension has been granted. \mod_assign\event\extension_granted" item in the autocomplete list
    And I set the field "User" to "1"
    And I set the field "Event module" to "Assign1"
    And I click on "Notification" "link" in the "#automation-tabs" "css_element"
    And I click on "#id_override_pulsenotification_recipients" "css_element" in the "#fitem_id_pulsenotification_recipients" "css_element"
    And I set the field "Recipients" to "Student"
    And I click on "#id_override_pulsenotification_cc" "css_element" in the "#fitem_id_pulsenotification_cc" "css_element"
    And I set the field "Cc" to "Teacher"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I am on the "Assign1" "assign activity" page
    And I click on "View all submissions" "link" in the ".tertiary-navigation" "css_element"
    And I click on "Edit" "link" in the "student User 1" "table_row"
    And I choose "Grant extension" in the open action menu
    And I set the following fields to these values:
      | Extension due date | ##1 July 2024## |
    And I press "Save changes"
    And I should see "Extension granted until: Monday, 1 July 2024, 12:00 AM" in the "student User 1" "table_row"
    And I navigate to course "Course 1" automation instances
    And I trigger cron
    And I navigate to course "Course 1" automation instances
    And I should see "Template1" in the "temp1C1" "table_row"
    And I click on ".action-report" "css_element" in the "temp1C1" "table_row"
