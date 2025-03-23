@mod @mod_pulse @pulseaddon @pulseaddon_preset

Feature: Create pulse using preset with pro features.
  In order to check pulse presets created with custom configs
  As a teacher
  I should create pulse activity using presets.

  Background: Insert demo presets.
    Given the following "courses" exist:
        | fullname| shortname | category |
        | Test | C1 | 0 |
    And the following "users" exist:
        | username | firstname | lastname | email |
        | student1 | student | User 1 | student1@test.com |
        | teacher | Teacher | User 1 | teacher1@test.com |
    And the following "course enrolments" exist:
        | user | course | role |
        | teacher | C1 | editingteacher |
        | student1 | C1 | student |
    Given I log in as "admin"
    And I create demo presets

  @javascript @_file_upload
  Scenario: Created preset should displayed in preset list.
    Given I navigate to "Plugins > Activity modules > Pulse > Manage Presets" in site administration
    And I press "Create Preset"
    Then "form.create-preset-form" "css_element" should be visible
    And I set the following fields to these values:
      | Title | Demo Preset 3 |
      | Description | Preset added using create preset form in pulse pro |
      | Instruction | Using pulse pro features you can create more custom presets for pulse |
      | Preset icon | fa-newspaper |
      | Configurable Params | Reaction > Type, Reaction > Location, First reminder > Notification subject, Second reminder > Notification subject |
      | Display this preset in list | 1 |
      | Preset template order | 1 |
    And I upload "/mod/pulse/addons/preset/assets/preset-demo-3.mbz" file to "Preset Template" filemanager
    And I press "Save changes"
    Then I should see "Pulse presets created successfully"
    And I should see "Demo Preset 3" in the "#list-pulse-presets_r0_c0" "css_element"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    Then I should see "Demo Preset 3" in the ".preset-title" "css_element"
    When I click on ".pulse-usepreset" "css_element"
    And I should see "Reaction > Type" in the ".modal-body .preset-config-params" "css_element"
    And I should see "First reminder > Notification subject" in the ".modal-body .preset-config-params" "css_element"
    And I should see "Second reminder > Notification subject" in the ".modal-body .preset-config-params" "css_element"
    Then I set the field with xpath "//div[@class='preset-config-params']//select[@id='id_options_reactiontype']" to "Rate"
    And I set the field with xpath "//div[@class='preset-config-params']//select[@id='id_options_reactiondisplay']" to "Both"
    And I press "Apply and Save"
    Then I should see "Like" in the ".pulse-completion-btn" "css_element"

  @javascript
  Scenario: Enable and disable preset visiblity.
    Given I navigate to "Plugins > Activity modules > Pulse > Manage Presets" in site administration
    And I click on "Enabled" "text" in the "#list-pulse-presets_r0_c4" "css_element"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    And I should not see "Welcome Message" in the ".preset-title" "css_element"
    And I navigate to "Plugins > Activity modules > Pulse > Manage Presets" in site administration
    And I click on "Disabled" "text" in the "#list-pulse-presets_r0_c4" "css_element"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    And I should see "Welcome Message" in the ".preset-title" "css_element"

  @javascript
  Scenario: Delete preset.
    Given I navigate to "Plugins > Activity modules > Pulse > Manage Presets" in site administration
    And I click on "a.action-icon[href*=\"action=delete\"]" "css_element" in the "#list-pulse-presets_r0_c6" "css_element"
    Then ".modal-dialog" "css_element" should be visible
    And I should see "Are you sure! do you want to delete the selected preset." in the ".modal-body" "css_element"
    When I click on "button.btn-primary" "css_element" in the ".modal-footer" "css_element"
    And I should see "Preset deleted successfully"
    Then I should not see "Demo pro preset 1"
    And I am on "Test" course homepage with editing mode on
    And I click on "Add an activity or resource" "button"
    And I click on "Pulse" "link"
    And I should not see "Demo pro preset 1" in the ".preset-title" "css_element"
