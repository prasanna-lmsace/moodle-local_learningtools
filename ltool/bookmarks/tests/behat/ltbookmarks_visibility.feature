@ltool @ltool_note

Feature: Check the Bookmarks ltool add/edit delete and list viewes.
  In order to check ltools bookmark features works
  As a studnet
  I should add and remove bookmarks of any page.

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
  # Scenario: Make bookmark the page.
  #   Given I log in as "student1"
  #   And I click on FAB button
  #   And I click on "#ltbookmarkinfo" "css_element"
  #   Then I should see "This page bookmarked successfully"
  #   And ".fa-bookmark.marked" "css_element" should exist
  #   Then I click on "#ltbookmarkinfo" "css_element"
  #   And I should see "This page bookmarked removed"
  #   And ".fa-bookmark.marked" "css_element" should not exist

  @javascript
  Scenario: Create and test the list of bookmarks.
    Given I log in as "student1"
    And I click on FAB button
    And I click on "#ltbookmarkinfo" "css_element"
    Then I should see "This page bookmarked successfully"
    # Add second note.
    When I click on "Site home" "link"
    And I click on FAB button
    And I click on "#ltbookmarkinfo" "css_element"
    Then I should see "This page bookmarked successfully"
    # List page.
    Then I follow "Profile" in the user menu
    And I click on "Bookmarks" "link"
    # Then I click on "#notessorttype" "css_element"
    Then I should see "user" in the ".category-item" "css_element"
    And I should see "Top" in the ".course-element" "css_element"
    When I click on "#bookmarkssorttype" "css_element"
    Then "user" "text" should appear before "Acceptance test site" "text"