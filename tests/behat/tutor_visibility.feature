@local @local_dttutor
Feature: Where the floating tutor button appears
  In order to ask about my course without leaving the page I am reading
  As a student
  I need the tutor button on the pages of my course and nowhere else

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
    And the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 1       |

  @MDL-E2E-001
  Scenario: An enrolled student finds the button on the course page
    When I am on the "Course 1" course page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should exist

  @MDL-E2E-001
  Scenario: The teacher of the course also finds the button
    When I am on the "Course 1" course page logged in as "teacher1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should exist

  @MDL-E2E-001
  Scenario: The button follows the student into an activity of the course
    Given the following "activity" exists:
      | activity | page        |
      | course   | C1          |
      | name     | Study notes |
      | idnumber | page1       |
    When I am on the "Study notes" "page activity" page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should exist

  @MDL-E2E-001
  Scenario: The button stays out of the pages of a quiz
    Given the following "activity" exists:
      | activity | quiz        |
      | course   | C1          |
      | name     | Final quiz  |
      | idnumber | quiz1       |
    When I am on the "Final quiz" "quiz activity" page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should not exist

  @MDL-E2E-001
  Scenario: The button does not reach pages outside a course
    When I log in as "student1"
    And I am on site homepage
    Then "[data-action='tutor-ia-toggle']" "css_element" should not exist

  @MDL-E2E-001
  Scenario: The button disappears when the chat is switched off for the site
    Given the following config values are set as admin:
      | enabled | 0 | local_dttutor |
    When I am on the "Course 1" course page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should not exist

  @MDL-E2E-001
  Scenario: The button disappears when the tutor is switched off for the course
    Given the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 0       |
    When I am on the "Course 1" course page logged in as "student1"
    Then "[data-action='tutor-ia-toggle']" "css_element" should not exist
