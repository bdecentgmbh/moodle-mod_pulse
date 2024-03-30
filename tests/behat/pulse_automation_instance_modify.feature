@mod @mod_pulse @pulse_automation_instance @_switch_window
Feature: Pulse automation instances
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

    And I log in as "admin"
    Then I create automation template with the following fields to these values:
      | Title             | Template 1     |
      | Reference         | temp1          |
    And I click on "Create new template" "button"
    Then I set the following fields to these values:
      | Title             | Notification1  |
      | Reference         | notification1  |
    And I press "Save changes"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "0" in the "Course 1" "table_row"
    And I log out

  @javascript @_switch_window
  Scenario: Add instances in the Instance Management
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 1')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "2(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "2" in the "Course 1" "table_row"

    And I click on ".action-add-instance" "css_element" in the "Course 2" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "3(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 2" "table_row"

    And I click on "Select all without instances" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "5(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"

    And I click on ".fa-calendar" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    Then I should see "Template 1" in the "temp1C2" "table_row"

  @javascript
  Scenario: Modify the instances with bulk action group
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"

    And I click on ".action-delete" "css_element" in the "Course 2" "table_row"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "3(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 2" "table_row"

    And I set the field with xpath "//tr[contains(normalize-space(.), 'Course 1')]//input[@type='checkbox']" to "bc[]"
    And I click on "#bulkdelete-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "2(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"

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
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"

    And I should see "4(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"

    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Template 1" in the "temp1C1" "table_row"
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I close all opened windows
    And I click on ".action-report" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    Then "Template 1" "table_row" should not exist
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I close all opened windows

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

    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "2" in the "Course 1" "table_row"
    And ".menu-item-actions .badge" "css_element" should not exist in the "Course 1" "table_row"
    And I click on ".action-report" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I click on ".quickeditlink" "css_element" in the "temp1C1" "table_row"
    And I wait "5" seconds
    And I set the following fields to these values:
      | Template 1 | Mixed Template |
    And I press enter
    And I should see "Mixed Template" in the "temp1C1" "table_row"
    And I wait "2" seconds
    And I should see "Template 1" in the ".generaltable tbody tr:nth-child(2) td" "css_element"
    And I click on ".pulse-instance-status-switch " "css_element" in the ".menu-item-actions " "css_element"
    And I close all opened windows
    And I reload the page
    And I should see "Mixed" in the "Course 1" "table_row"

  @javascript
  Scenario: Utilizing the filter option in instance management
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I should see "0(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "notification1" "table_row"

    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"

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

    # # Filtering "Course"
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

    # Filtering "Number of overrides"

    # Number of override: 1
    And I click on ".action-edit" "css_element" in the "Category 1" "table_row"
    And I switch to a second window
    And I should see "Template 1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template 01 |
    And I wait "2" seconds
    And I press "Save changes"
    And I should see "Template 01" in the "temp1C1" "table_row"
    And I close all opened windows

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
    And I switch to a second window
    And I should see "Template 1" in the "temp1C2" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template 02 |
    And I press "Save changes"
    And I should see "Template 02" in the "temp1C2" "table_row"
    And I close all opened windows

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 1 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And I should see "Course 2" in the "Cat 1" "table_row"
    And "Cat 2" "table_row" should not exist

    # Number of override: 2
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I click on ".action-edit" "css_element" in the "Template 01" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_notes" "css_element"
    And I set the following fields to these values:
      | Internal notes | Demo Template 01 |
    And I press "Save changes"
    And I close all opened windows

    And I click on "#pulse-manageinstance-filter" "css_element" in the "#manage-instance-tab" "css_element"
    And I set the following fields to these values:
      | Number of overrides | 2 |
    And I click on "Filter" "button" in the "#fitem_id_submitbutton" "css_element"
    And I should see "Course 1" in the "Category 1" "table_row"
    And "Cat 1" "table_row" should not exist

    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I click on ".action-edit" "css_element" in the "Template 01" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_notes" "css_element"
    And I press "Save changes"
    And I close all opened windows

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
    And I should see "0(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "notification1" "table_row"

    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Notification1" in the "notification1C1" "table_row"
    And I should see "Template 1" in the "temp1C1" "table_row"
    And I click on ".form-autocomplete-downarrow" "css_element" in the "#fitem_id_templateid .align-items-start" "css_element"
    And "Template 1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And "Notification1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And I close all opened windows
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I click on ".action-delete" "css_element" in the "notification1" "table_row"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And "Notification1" "table_row" should not exist
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on ".action-edit" "css_element" in the "Course 1" "table_row"
    And I switch to a second window
    And I should see "Template 1" in the "temp1C1" "table_row"
    And "notification1C1" "table_row" should not exist
    And I click on ".form-autocomplete-downarrow" "css_element" in the "#fitem_id_templateid .align-items-start" "css_element"
    And "Template 1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should be visible
    And "Notification1" "text" in the "#fitem_id_templateid .form-autocomplete-suggestions" "css_element" should not be visible
    And I wait "2" seconds

  @javascript
  Scenario: Additional column added for the report source
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I should see "0(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "notification1" "table_row"

    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"

    And I navigate to "Reports > Report builder > Custom reports" in site administration
    And I click on "New report" "button"
    And I set the following fields in the "New report" "dialogue" to these values:
      | Name                  | My report     |
      | Report source         | Notification  |
      | Include default setup | 0             |
    And I click on "Save" "button" in the "New report" "dialogue"
    And I wait until the page is ready
    Then I should see "My report"
    And I click on "Template title" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds
    And I click on "Template reference" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds
    And I click on "Instance title" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds
    And I click on "Instance reference" "link" in the ".reportbuilder-sidebar-menu-cards .card-body" "css_element"
    And I wait "2" seconds

  @javascript
  Scenario: A feature to enable/disable overrides in instances
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I should see "0(0)" in the "notification1" "table_row"
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on ".action-add-instance" "css_element" in the "Course 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "1(0)" in the "notification1" "table_row"

    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "0" in the "Course 1" "table_row"
    And I click on "Select all" "link" in the "#manage-instance-tab" "css_element"
    And I click on "#bulkadd-btn" "css_element" in the ".bulkaction-group" "css_element"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I click on "Automation templates" "link" in the ".breadcrumb" "css_element"
    And I should see "4(0)" in the "temp1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on "Instance Management" "link"
    And I should see "1" in the "Course 1" "table_row"
    And I should see "1" in the "Course 2" "table_row"
    And I should see "1" in the "Course 3" "table_row"
    And I should see "1" in the "Course 4" "table_row"
    And I log out

    # Allow the instance override
    And I am on the "Course 1" course page logged in as teacher1
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I click on ".action-edit" "css_element" in the "Category 1" "table_row"
    And I switch to a second window
    And I should see "Template 1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template 01 |
    And I wait "2" seconds
    And I press "Save changes"
    And I should see "Template 01" in the "temp1C1" "table_row"
    And I close all opened windows
    And I log out

    # Allow the instance override
    And I log in as "admin"
    And I navigate to "Users > Permissions > Define roles"
    And I click on ".fa-cog" in the "tacher" "table_row"
    And I set the field "Override the automation instance" to "0"
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I navigate to "Plugins > Activity modules > Automation templates" in site administration
    And I click on ".action-edit" "css_element" in the "Notification1" "table_row"
    And I click on "Instance Management" "link"
    And I click on ".action-edit" "css_element" in the "Category 1" "table_row"
    And I switch to a second window
    And I should see "Template 1" in the "temp1C1" "table_row"
    And I click on ".action-edit" "css_element" in the "Template 1" "table_row"
    And I click on ".checkboxgroupautomation" "css_element" in the "#fitem_id_title" "css_element"
    And I set the following fields to these values:
      | Title | Template 01 |
    And I wait "2" seconds
    And I press "Save changes"
    And I should see "Template 01" in the "temp1C1" "table_row"
    And I close all opened windows