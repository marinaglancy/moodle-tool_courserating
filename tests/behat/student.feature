@tool @tool_courserating @javascript
Feature: Viewing and adding course ratings as a student

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
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  Scenario: Rating a course as a student
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Rate this course"
    And I click on ".tool_courserating-form-stars-group .stars-3" "css_element"
    And I press "Save changes"
    And I should see "3.0" in the ".tool_courserating-widget" "css_element"
    And I should see "(1)" in the ".tool_courserating-widget" "css_element"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "3.0" in the ".tool_courserating-widget" "css_element"
    And I should see "(1)" in the ".tool_courserating-widget" "css_element"
    And I follow "Rate this course"
    And I click on ".tool_courserating-form-stars-group .stars-4" "css_element"
    And I press "Save changes"
    And I should see "3.5" in the ".tool_courserating-widget" "css_element"
    And I click on "(2)" "text" in the ".tool_courserating-widget" "css_element"
    And I should see "3.5" in the "Course reviews" "dialogue"
