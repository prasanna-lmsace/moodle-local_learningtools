@ltool @ltool_note

Feature: Check the Note ltool add/edit delete and list viewes.
  In order to check ltools features works
  As a admin
  I should manage subplugins order and enable/disable plugins.

  Background: Create users to check the visbility.
    Given the following "users" exist:
      | username | firstname | lastname | email              |
      | student1 | Student   | User 1   | student1@test.com  |
      | teacher1 | Teacher   | User 1   | teacher1@test.com  |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student        |
      | teacher1 | C1 | editingteacher |

  # @javascript
  # Scenario: Create multiple notes in a page.
  #   Given I log in as "student1"
  #   And I click on FAB button
  #   And I click on "#ltnoteinfo" "css_element"
  #   Then I should see "Take notes"
  #   And I set the following fields to these values:
  #   | ltnoteeditor | Test note 1 |
  #   And I press "Save changes"
  #   Then I should see "Notes added successfully"
  #   And I should see "1" in the "#ltnote-action" "css_element"
  #   Then I click on "#ltnoteinfo" "css_element"
  #   And I should see "Test note 1" in the ".list-context-existnotes" "css_element"
  #   And I set the following fields to these values:
  #   | ltnoteeditor | Test note 2 |
  #   And I press "Save changes"
  #   Then I should see "Notes added successfully"
  #   And I should see "2" in the "#ltnote-action" "css_element"
  #   Then I click on "#ltnoteinfo" "css_element"

  @javascript
  Scenario: Create and test the list of notes.
    Given I log in as "student1"
    And I click on FAB button
    And I click on "#ltnoteinfo" "css_element"
    Then I should see "Take notes"
    And I set the following fields to these values:
    | ltnoteeditor | Test note 1 |
    And I press "Save changes"
    # Add second note.
    And I click on "#ltnoteinfo" "css_element"
    Then I should see "Take notes"
    And I set the following fields to these values:
    | ltnoteeditor | Test note 2 |
    And I press "Save changes"
    Then I follow "Profile" in the user menu
    And I click on "Notes" "link"
    # Then I click on "#notessorttype" "css_element"
    Then I should see "Test note 1"
    And I should see "Test note 2"
    When I click on "#notessorttype" "css_element"
    Then "Test note 1" "text" should appear after "Test note 2" "text"

  # @javascript
  # Scenario: 