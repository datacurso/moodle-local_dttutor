@local @local_dttutor
Feature: Global configuration of the tutor
  In order to give the tutor the identity and the placement my institution wants
  As an administrator
  I need one settings page with the avatar and the customisation of the tutor

  Background:
    Given the following config values are set as admin:
      | enabled | 1 | local_dttutor |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > Tutor AI" in site administration

  @MDL-E2E-011
  Scenario: The settings page groups the avatar and the customisation of the tutor
    Then I should see "Tutor-AI avatar"
    And I should see "Tutor Customization"
    And I should see "Welcome message"
    And I should see "Send the student's grades to the AI tutor"

  @MDL-E2E-011
  Scenario Outline: The gallery offers the avatar <avatar>
    Then "[data-value='<avatar>']" "css_element" should exist

    Examples:
      | avatar |
      | 01     |
      | 02     |
      | 03     |
      | 04     |
      | 05     |
      | 06     |
      | 07     |
      | 08     |
      | 09     |
      | 10     |

  @MDL-E2E-011
  Scenario: Sending the grades of the student is off until the administrator turns it on
    Then the field "Send the student's grades to the AI tutor" matches value "0"

  @MDL-E2E-012
  Scenario: The position configurator offers its live preview
    Then I should see "Avatar position"
    And ".position-preview" "css_element" should exist
    And "#preview-avatar" "css_element" should exist
    And "#coords-display" "css_element" should exist
