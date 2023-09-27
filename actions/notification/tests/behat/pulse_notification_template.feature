@mod @mod_pulse @mod_pulse_automation @mod_pulse_automation_template @pulseactions @pulseaction_notification_template
Feature: Configuring the pulseaction_notification plugin on the "Automation template" page, applying different configurations to the notification
  In order to use the features
  As admin
  I need to be able to configure the pulse automation template

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "course" exist:
      | fullname    | shortname | category |
      | Course 1    | C1        | 0        |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    |
      | book       | Test book name         | Test book description         | C1     | book1       |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | student | User 1 | student1@test.com |
      | teacher1 | teacher | User 1 | teacher1@test.com |
    And the following "course enrolments" exist:
      | user     | course | role |
      | student1 | C1     | student |
      | teacher1 | C1     | teacher |
    And the following "permission overrides" exist:
      | capability                                    | permission | role      | contextlevel | reference |
      | pulseaction/notification:receivenotification  | Allow      | teacher   | System       |           |

  @javascript
  Scenario: Create notification template and instance
    Given I log in as "admin"
    And I navigate to automation templates
    And I create pulse notification template "WELCOME MESSAGE" "WELCOMEMESSAGE_" to these values:
      | Sender     | Course teacher   |
      | Recipients | Student          |
      | Subject     | Welcome to {Site_FullName} |
      | Header content | Hi {User_firstname} {User_lastname}, <br> Welcome to learning portal of {Site_FullName} |
      | Footer content | Copyright @ 2023 {Site_FullName} |
    Then I should see "Automation templates"
    And I should see "WELCOME MESSAGE" in the "pulse_automation_template" "table"
    And I navigate to course "Course 1" automation instances
    And I create pulse notification instance "WELCOME MESSAGE" "COURSE_1" to these values:
    | Recipients | Student          |
    And I should see "WELCOMEMESSAGE_COURSE_1" in the "pulse_automation_template" "table"
    And I click on ".action-report" "css_element" in the "WELCOMEMESSAGE_COURSE_1" "table_row"
    And I switch to a second window
    Then ".reportbuilder-report" "css_element" should exist
    And the following should exist in the "reportbuilder-table" table:
    | Full name       | Subject                              | Status |
    | student User 1  | Welcome to Acceptance test site      | Queued |

  @javascript
  Scenario: Override notification template
    Given I log in as "admin"
    And I navigate to automation templates
    And I create pulse notification template "WELCOME MESSAGE" "WELCOMEMESSAGE_" to these values:
      | Sender     | Course teacher   |
      | Recipients | Student          |
      | Subject     | Welcome to {Site_FullName} |
      | Header content | Hi {User_firstname} {User_lastname}, <br> Welcome to learning portal of {Site_FullName} |
      | Footer content | Copyright @ 2023 {Site_FullName} |
    Then I should see "Automation templates"
    And I should see "WELCOME MESSAGE" in the "pulse_automation_template" "table"
    And I navigate to course "Course 1" automation instances
    And I create pulse notification instance "WELCOME MESSAGE" "COURSE_1" to these values:
      | override[pulsenotification_subject] | 1                  |
      | Subject           | Welcome to learning portal {Site_FullName}  |
    And I should see "WELCOMEMESSAGE_COURSE_1" in the "pulse_automation_template" "table"
    And I click on ".action-report" "css_element" in the "WELCOMEMESSAGE_COURSE_1" "table_row"
    And I switch to a second window
    Then ".reportbuilder-report" "css_element" should exist
    And the following should exist in the "reportbuilder-table" table:
    | Full name       | Subject                              | Status |
    | student User 1  | Welcome to learning portal Acceptance test site | Queued |

  @javascript
  Scenario: Preview the notification content
    Given I log in as "admin"
    And I initiate new automation template to these values:
      | Title     | WELCOME MESSAGE |
      | Reference | WELCOMEMESSAGE_ |
    And I set previous automation template notification to these values:
      | Sender     | Course teacher   |
      | Recipients | Student          |
      | Subject     | Welcome to {Site_FullName} |
      | Header content | Hi {User_firstname} {User_lastname}, <br> Welcome to {Site_FullName} |
      | Footer content | <p> Copyright @ 2023 {Site_FullName} </p> |
    And I press "Preview"
    And I should see "Preview" in the ".modal-header .modal-title" "css_element"
    Then I set the field "userselector" to "student User 1"
    And I should see "Hi student User 1," in the ".modal .modal-body" "css_element"
    And I should see "Welcome to Acceptance test site" in the ".modal .modal-body" "css_element"
    And I should see "Copyright @ 2023 Acceptance test site" in the ".modal .modal-body" "css_element"
    Then I click on ".close" "css_element" in the ".modal-header" "css_element"
    And I press "Save changes"
    Then I should see "Automation templates"
    And I should see "WELCOME MESSAGE" in the "pulse_automation_template" "table"
    And I navigate to course "Course 1" automation instances
    And I initiate new automation instance of template "WELCOME MESSAGE" to these values:
      | Reference | COURSE_1         |
    And I set previous automation template notification to these values:
      | Recipients | Student         |
    And I press "Preview"
    And I should see "Preview" in the ".modal-header .modal-title" "css_element"
    Then I set the field "userselector" to "student User 1"
    And I should see "Hi student User 1," in the ".modal .modal-body" "css_element"
    And I should see "Welcome to Acceptance test site" in the ".modal .modal-body" "css_element"
    And I should see "Copyright @ 2023 Acceptance test site" in the ".modal .modal-body" "css_element"
    Then I click on ".close" "css_element" in the ".modal-header" "css_element"
    Then I set the field "Dynamic content" to "Test book name"
    And I set the field "Content type" to "Description"
    And I press "Preview"
    And I should see "Test book description" in the ".modal-body .no-overflow" "css_element"

  @javascript
  Scenario: Set delayed notification
    Given I log in as "admin"
    And I navigate to automation templates
    And I create pulse notification template "WELCOME MESSAGE" "WELCOMEMESSAGE_" to these values:
      | Sender     | Course teacher   |
      | Recipients | Student          |
      | Subject     | Welcome to {Site_FullName} |
      | Header content | Hi {User_firstname} {User_lastname}, <br> Welcome to learning portal of {Site_FullName} |
      | Footer content | Copyright @ 2023 {Site_FullName} |
      | Delay | After |
      | pulsenotification_delayduration[number]  | 10 |
    Then I should see "Automation templates"
    And I should see "WELCOME MESSAGE" in the "pulse_automation_template" "table"
    And I navigate to course "Course 1" automation instances
    And I create pulse notification instance "WELCOME MESSAGE" "COURSE_1" to these values:
      | override[pulsenotification_subject] | 1                  |
      | Subject           | Welcome to learning portal {Site_FullName}  |
    And I should see "WELCOMEMESSAGE_COURSE_1" in the "pulse_automation_template" "table"
    And I click on ".action-report" "css_element" in the "WELCOMEMESSAGE_COURSE_1" "table_row"
    And I switch to a second window
    Then ".reportbuilder-report" "css_element" should exist
    And the following should exist in the "reportbuilder-table" table:
    | Full name       | Subject                                         | Status | Time created                    | Scheduled time                          |
    | student User 1  | Welcome to learning portal Acceptance test site | Queued | ##now##%A, %d %B %Y, %I:%M %p## | ##+10 minutes##%A, %d %B %Y, %I:%M %p## |

  @javascript
  Scenario Outline: Pulse action Notification: Send notification for different role users
    Given I log in as "admin"
    And I navigate to automation templates
    And I create pulse notification template "WELCOME MESSAGE" "WELCOMEMESSAGE_" to these values:
      | Sender         | Custom                           |
      | Sender email   | test@test.com                    |
      | Recipients     | <recipient>                      |
      | Subject        | Welcome to {Site_FullName}       |
      | Header content | Hi {User_firstname} {User_lastname}, <br> Welcome to learning portal of {Site_FullName} |
    Then I should see "Automation templates"
    And I should see "WELCOME MESSAGE" in the "pulse_automation_template" "table"
    And I navigate to course "Course 1" automation instances
    # Demo content.
    And I create pulse notification instance "WELCOME MESSAGE" "COURSE_1" to these values:
      | Recipients | Student                         |
    And I should see "WELCOMEMESSAGE_COURSE_1" in the "pulse_automation_template" "table"
    And I click on ".action-report" "css_element" in the "WELCOMEMESSAGE_COURSE_1" "table_row"
    And I switch to a second window
    Then ".reportbuilder-report" "css_element" should exist
    And the following <studentshouldorshouldnot> exist in the "reportbuilder-table" table:
    | Full name       | Status |
    | student User 1  | Queued |
    And the following <teachershouldorshouldnot> exist in the "reportbuilder-table" table:
    | Full name       | Status |
    | teacher User 1  | Queued |

    Examples:
      | recipient | studentshouldorshouldnot | teachershouldorshouldnot |
      | Student   | should                   | should not               |
      | Teacher   | should not               | should                   |
