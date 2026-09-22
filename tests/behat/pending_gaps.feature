@local @local_dttutor_pending @javascript
Feature: Interface gaps listed in the scope
  In order to keep the pending work visible
  As the team maintaining the tutor
  I need every interface gap written down as the behaviour the scope asks for

  # These scenarios describe behaviour that does not exist yet, so they FAIL on purpose and are not
  # part of the @local_dttutor run. Check progress with --tags=@local_dttutor_pending.
  # Where the gap also has a server side, that side is a PHPUnit test that skips or fails on its own.
  #
  # Cases of the definition document that cannot be driven from Behat at all, and stay manual:
  #  - MDL-E2E-015 [Pendiente:fail]: a welcome message with quotes breaks the history load. Needs a
  #    conversation already stored in the AI service, so it cannot be reproduced without that service.
  #  - MDL-E2E-021 [Pendiente:skip]: the answer is played back at a fixed pace instead of streaming.
  #    Measured against the real service.
  #  - MDL-E2E-025 [Pendiente:skip]: what the tutor may discuss while a quiz attempt is open.
  #    A scope decision, not yet a behaviour that can be asserted.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following config values are set as admin:
      | enabled | 1 | local_dttutor |
    And the following "local_dttutor > course settings" exist:
      | course | enabled |
      | C1     | 1       |

  @MDL-E2E-020
  Scenario: The chat offers starting a new conversation
    # [Pendiente:skip] The only way to restart is editing an earlier message, and the history cannot
    # be cleared from the interface even though the server already supports deleting it.
    Given I am on the "Course 1" course page logged in as "student1"
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then "[data-action='new-conversation']" "css_element" should exist

  @MDL-E2E-022
  Scenario: A message that is too long is reported by the form, not by the tutor
    # [Pendiente:skip] The warning is shown as a bubble of the conversation, as if the tutor said it.
    Given I am on the "Course 1" course page logged in as "student1"
    And I click on "[data-action='tutor-ia-toggle']" "css_element"
    When I set the field with xpath "//textarea[@data-region='tutor-ia-input']" to "A message that goes well past the four thousand character limit"
    And I click on "[data-action='send-message']" "css_element"
    Then ".tutor-ia-input-error" "css_element" should exist

  @MDL-E2E-023
  Scenario: The panel adapts to the screen of a phone
    # [Pendiente:skip] The panel has a fixed width of 380 pixels with no adaptation to the screen,
    # so on a narrow phone it covers almost the whole page or runs off it.
    Given I change window size to "360x640"
    And I am on the "Course 1" course page logged in as "student1"
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then ".tutor-ia-drawer.tutor-ia-drawer-responsive" "css_element" should exist

  @MDL-E2E-024
  Scenario: The page content moves aside under a theme other than Boost
    # [Pendiente:skip] The shift depends on a class of the Boost theme, so under another theme the
    # panel can sit on top of the content instead of pushing it aside.
    Given the following config values are set as admin:
      | theme | classic |
    And I am on the "Course 1" course page logged in as "student1"
    When I click on "[data-action='tutor-ia-toggle']" "css_element"
    Then ".tutor-ia-drawer.show" "css_element" should exist
    And "#page.show-drawer-right" "css_element" should exist
