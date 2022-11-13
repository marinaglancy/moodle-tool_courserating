@tool @tool_courserating @javascript
Feature: Viewing and managing course ratings as a teacher and manager

  Background:
    Given the following "courses" exist:
      | fullname | shortname | numsections |
      | Course 1 | C1        | 1           |
      | Course 2 | C2        | 1           |
      | Course 3 | C3        | 1           |
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | manager1 | Manager   | 1        | manager1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "tool_courserating > ratings" exist:
      | user     | course | rating | review |
      | student1 | C1     | 3      | abcdef |
      | student2 | C1     | 4      | hello  |
      | student3 | C1     | 1      |        |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: Removing reviews as manager through reviews popup
    When I log in as "manager1"
    And I am on "Course 1" course homepage
    And I click on "2.7" "text" in the "#page-header .tool_courserating-ratings" "css_element"
    And I should see "abcdef" in the "Student 1" "tool_courserating > Review"
    And I click on "Flag" "link" in the "Student 1" "tool_courserating > Review"
    And I should see "You have flagged this review as inappropriate/offensive." in the "Student 1" "tool_courserating > Review"
    And I should see "1 user(s) have flagged this review as inappropriate/offensive."
    And I should see "Permanently delete"
    And I click on "Close" "button" in the ".modal-dialog" "css_element"
    And I click on "2.7" "text" in the "#page-header .tool_courserating-ratings" "css_element"
    And I click on "Permanently delete" "link" in the "Student 1" "tool_courserating > Review"
    And I set the field "Reason for deletion" to "go away"
    And I press "Save changes"
    And I should see "hello" in the "Student 2" "tool_courserating > Review"
    And I should not see "Student 1"
    And I should not see "2.7"
    And I should see "2.5" in the "[data-purpose='average-rating']" "css_element"
    And I click on "Close" "button" in the ".modal-dialog" "css_element"
    And I should not see "2.7"
    And I should see "2.5" in the "#page-header" "css_element"

  Scenario: Removing reviews as manager through the course report
    When I log in as "manager1"
    And I am on "Course 1" course homepage
    And I navigate to "Course ratings" in current page administration
    And I click on "Permanently delete" "link" in the "Student 3" "table_row"
    And I set the field "Reason for deletion" to "go away"
    And I press "Save changes"
    And I should see "Rating deleted" in the "Student 3" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "Course ratings" in current page administration
    And I should see "Student 1"
    And I should not see "Student 3"

  Scenario: Viewing reviews as teacher through the course report
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Course ratings" in current page administration
    And I should see "abcdef" in the "Student 1" "table_row"
    And I should see "hello" in the "Student 2" "table_row"
    And I should not see "Permanently delete"

  Scenario: Creating course rating report in report builder
    Given reportbuilder is available for tool_courserating
    Given I log in as "admin"
    And I change window size to "large"
    When I navigate to "Reports > Report builder > Custom reports" in site administration
    And I click on "New report" "button"
    And I set the following fields in the "New report" "dialogue" to these values:
      | Name                  | My report      |
      | Report source         | Course ratings |
      | Include default setup | 1              |
    And I click on "Save" "button" in the "New report" "dialogue"
    And I click on "Switch to preview mode" "button"
    Then I should see "My report"
    And the following should exist in the "reportbuilder-table" table:
      | Course full name with link | Course rating |
      | Course 1                   | 2.7           |

  Scenario: Deleting ratings from the custom report in report builder
    Given reportbuilder is available for tool_courserating
    Given I log in as "admin"
    When I navigate to "Reports > Report builder > Custom reports" in site administration
    And I click on "New report" "button"
    And I set the following fields in the "New report" "dialogue" to these values:
      | Name                  | My report      |
      | Report source         | Course ratings |
      | Include default setup | 0              |
    And I click on "Save" "button" in the "New report" "dialogue"
    And I click on "Add column 'Course full name with link'" "link"
    And I click on "Add column 'Full name with link'" "link"
    And I click on "Add column 'Review'" "link"
    And I click on "Add column 'Actions'" "link"
    And I click on "Switch to preview mode" "button"
    And I click on "Permanently delete" "link" in the "Student 3" "table_row"
    And I set the field "Reason for deletion" to "go away"
    And I press "Save changes"
    And I should see "Rating deleted" in the "Student 3" "table_row"
    And I press "Switch to edit mode"
    And I click on "Switch to preview mode" "button"
    And I should see "Student 1"
    And I should not see "Student 3"
