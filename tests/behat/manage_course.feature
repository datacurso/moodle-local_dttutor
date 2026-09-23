@local @local_dttutor
Feature: Managing the tutor inside a course
  In order to decide whether my students have the tutor
  As a teacher
  I need a page in my course to switch it on and off

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | enabled | 1 | local_dttutor |

  @MDL-E2E-009
  Scenario: The page warns that the students are not seeing the chat
    When I am on the "C1" "local_dttutor > course management" page logged in as "teacher1"
    Then I should see "AI Tutor Status"
    And I should see "The AI Tutor is currently disabled for this course. Students will not see the chat interface."
    And I should see "Help"

  @MDL-E2E-009
  Scenario: The warning disappears once the tutor is switched on
    Given the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 1       |
    When I am on the "C1" "local_dttutor > course management" page logged in as "teacher1"
    Then I should not see "Students will not see the chat interface."
    And the field "Enable AI Tutor for this course" matches value "1"

  @MDL-E2E-009 @javascript
  Scenario: Switching the tutor on reaches the students of the course
    Given I am on the "C1" "local_dttutor > course management" page logged in as "teacher1"
    When I set the field "Enable AI Tutor for this course" to "1"
    And I wait "2" seconds
    And I log out
    And I am on the "Course 1" course page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should exist

  @MDL-E2E-009 @javascript
  Scenario: Switching the tutor off takes the chat away from the students
    Given the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 1       |
    And I am on the "C1" "local_dttutor > course management" page logged in as "teacher1"
    When I set the field "Enable AI Tutor for this course" to "0"
    And I wait "2" seconds
    And I log out
    And I am on the "Course 1" course page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should not exist
