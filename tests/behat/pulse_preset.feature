@mod @mod_pulse

Feature: Preset create pulse with custom params.
  In order to check pulse presets created with custom configs
  As a teacher
  I should create pulse activity using presets.

  Background: Insert demo presets.
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Test     | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | student   | User 1   | student1@test.com |
      | teacher  | Teacher   | User 1   | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    Given I log in as "admin"
    And I create demo presets
    And I log out

  @javascript
  Scenario: Presets list should shown on create pulse module form.
    Given I log in as "teacher"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    Then I should see "Welcome Message" in the ".preset-title" "css_element"
    When I click on ".pulse-usepreset" "css_element"
    And I should see "Welcome Message" in the ".modal-header .modal-title" "css_element"
    And I press "Apply and Save"
    Then I should see "Welcome to the course!"

  @javascript
  Scenario: Preset apply and save with custom config params.
    Given I log in as "teacher"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    Then I should see "Welcome Message" in the ".preset-title" "css_element"
    When I click on ".pulse-usepreset" "css_element"
    And I should see "Welcome Message" in the ".modal-header .modal-title" "css_element"
    And I set the field "id_preseteditor_introeditor" to "Preset using configurable params"
    And I press "Apply and Save"
    Then I should see "Preset using configurable params"

  @javascript
  Scenario: Preset apply and customize with custom config params.
    Given I log in as "teacher"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    Then I should see "Welcome Message" in the ".preset-title" "css_element"
    When I click on ".pulse-usepreset" "css_element"
    And I should see "Welcome Message" in the ".modal-header .modal-title" "css_element"
    And I set the field with xpath "//div[@class='preset-config-params']//input[@id='id_name']" to "Customize preset"
    And I set the field "id_preseteditor_introeditor" to "Pulse created using apply and customize - custom value"
    And I press "Apply and Customize"
    And I wait "3" seconds
    Then ".modal-body" "css_element" should not be visible
    Then the field "id_name" matches value "Customize preset"
    And the field "id_introeditor" matches value "Pulse created using apply and customize - custom value"
    And I press "Save and return to course"
    Then I should see "Pulse created using apply and customize - custom value"
