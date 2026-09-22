@local @local_dttutor @javascript
Feature: Opening and closing the chat panel
  In order to ask the tutor about my course
  As a student
  I need to open the panel, see who is answering me and close it again

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Grace     | Hopper   | student1@example.com |
      | teacher1 | Ada       | Lovelace | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | enabled        | 1                                                  | local_dttutor |
      | tutorname      | Tutor of {coursename}                              | local_dttutor |
      | welcomemessage | Hello {firstname}, I am {teachername}. Ask me away. | local_dttutor |
    And the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 1       |

  @MDL-E2E-002 @MDL-E2E-014
  Scenario: The panel opens with the configured identity and the welcome message
    Given I am on the "Course 1" course page logged in as "student1"
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then ".tutor-ia-drawer.show" "css_element" should exist
    And I should see "Tutor of Course 1" in the ".tutor-ia-drawer-header" "css_element"
    And I should see "Student" in the ".tutor-ia-role" "css_element"
    And I should see "Hello Grace, I am Ada Lovelace. Ask me away." in the ".tutor-ia-drawer" "css_element"

  @MDL-E2E-002
  Scenario: The panel closes with its own button
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    And ".tutor-ia-drawer.show" "css_element" should exist
    When I click on ".tutor-ia-close-button" "css_element"
    Then ".tutor-ia-drawer.show" "css_element" should not exist

  @MDL-E2E-002
  Scenario: The panel closes with a second click on the floating button
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    And ".tutor-ia-drawer.show" "css_element" should exist
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then ".tutor-ia-drawer.show" "css_element" should not exist

  @MDL-E2E-002
  Scenario: The panel closes with the escape key
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    And ".tutor-ia-drawer.show" "css_element" should exist
    When I press the escape key
    Then ".tutor-ia-drawer.show" "css_element" should not exist

  @MDL-E2E-002
  Scenario: The header tells a teacher apart from a student
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then I should see "Teacher" in the ".tutor-ia-role" "css_element"

  @MDL-UNIT-011
  Scenario: An empty message is never sent
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    When I click on "[data-action='send-message']" "css_element"
    Then ".tutor-ia-message.user" "css_element" should not exist

  @MDL-E2E-002
  Scenario: The chat panel and the messaging drawer are not open at the same time
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    And ".tutor-ia-drawer.show" "css_element" should exist
    When I open messaging
    Then ".tutor-ia-drawer.show" "css_element" should not exist
