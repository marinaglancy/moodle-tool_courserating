@tool @tool_courserating @javascript
Feature: Testing course settings overrides in tool_courserating

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

  Scenario: When admin enables course overrides, teacher can change settings per course
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I should not see "When can courses be rated"
    And I should not see "Accompanying rating with a review"
    And I log out
    And I log in as "admin"
    And I navigate to "Courses > Course ratings" in site administration
    And I set the field "Course overrides" to "1"
    And I press "Save changes"
    And I run all adhoc tasks
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I wait "1" seconds
    And I expand all fieldsets
    And I should see "When can courses be rated"
    And I should see "Accompanying rating with a review"
    And I log out
    And I log in as "admin"
    And I navigate to "Courses > Course ratings" in site administration
    And I set the field "Course overrides" to "0"
    And I press "Save changes"
    And I run all adhoc tasks
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I wait "1" seconds
    And I expand all fieldsets
    And I should not see "When can courses be rated"
    And I should not see "Accompanying rating with a review"
    And I log out

  Scenario: Course settings overrule the site settings
    And I log in as "admin"
    And I navigate to "Courses > Course ratings" in site administration
    And I set the field "Course overrides" to "1"
    And I press "Save changes"
    And I run all adhoc tasks
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I wait "1" seconds
    And I expand all fieldsets
    And I set the field "Accompanying rating with a review" to "No text reviews allowed"
    And I press "Save and display"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And "2.7" "text" should exist in the "#page-header .tool_courserating-ratings" "css_element"
    And I follow "Edit your rating"
    And I click on ".tool_courserating-form-stars-group .stars-4" "css_element"
    And I should not see "Review" in the "Leave a rating" "dialogue"
    And I press "Save changes"
    And "3.0" "text" should exist in the "#page-header .tool_courserating-ratings" "css_element"
