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
      | manager1 | Manager   | 1        | manager1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: Rating a course as a student
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Leave a rating"
    And I click on ".tool_courserating-form-stars-group .stars-3" "css_element"
    And I press "Save changes"
    And I should see "3.0" in the ".tool_courserating-widget" "css_element"
    And I should see "(1)" in the ".tool_courserating-widget" "css_element"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "3.0" in the ".tool_courserating-widget" "css_element"
    And I should see "(1)" in the ".tool_courserating-widget" "css_element"
    And I follow "Leave a rating"
    And I click on ".tool_courserating-form-stars-group .stars-4" "css_element"
    And I press "Save changes"
    And I should see "3.5" in the ".tool_courserating-widget" "css_element"
    And I click on "(2)" "text" in the ".tool_courserating-widget" "css_element"
    And I follow "View all reviews"
    And I should see "3.5" in the "Course reviews" "dialogue"

  Scenario: Flagging course ratings as a student
    Given the following "tool_courserating > ratings" exist:
      | user     | course | rating | review |
      | student1 | C1     | 3      | abcdef |
      | student2 | C1     | 4      | hello  |
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "3.5" in the ".tool_courserating-widget" "css_element"
    And I should see "(2)" in the ".tool_courserating-widget" "css_element"
    And I click on ".tool_courserating-ratings" "css_element"
    And I follow "View all reviews"
    And I should see "abcdef" in the "Student 1" "tool_courserating > Review"
    And I click on "Flag" "link" in the "Student 1" "tool_courserating > Review"
    And I should see "You have flagged this review as inappropriate/offensive." in the "Student 1" "tool_courserating > Review"
    And I should not see "user(s) have flagged this review as inappropriate/offensive."
    And I should not see "Permanently delete"
    And I should not see "You have flagged this review as inappropriate/offensive." in the "Student 2" "tool_courserating > Review"
    And I log out
    And I log in as "manager1"
    And I am on course index
    And I click on ".tool_courserating-ratings" "css_element" in the "Course 1" "tool_courserating > Coursebox"
    And "Flag" "link" should exist in the "Student 1" "tool_courserating > Review"
    And I should see "1 user(s) have flagged this review as inappropriate/offensive." in the "Student 1" "tool_courserating > Review"
    And I should see "Permanently delete" in the "Student 1" "tool_courserating > Review"
    And I should not see "user(s) have flagged this review as inappropriate/offensive." in the "Student 2" "tool_courserating > Review"

  Scenario: Viewing course ratings as a non-logged in user
    Given the following "tool_courserating > ratings" exist:
      | user     | course | rating | review |
      | student1 | C1     | 3      | abcdef |
      | student2 | C1     | 4      | hello  |
    And the following config values are set as admin:
      | frontpage         | 7,6 |                |
      | frontpageloggedin | 7,6 |                |
    And I am on site homepage
    And I should see "3.5" in the "Course 1" "tool_courserating > Coursebox"
    And I should see "(2)" in the "Course 1" "tool_courserating > Coursebox"
    And I click on ".tool_courserating-ratings" "css_element" in the "Course 1" "tool_courserating > Coursebox"
    And I should see "3.5" in the "Course reviews" "dialogue"
    And I should see "abcdef" in the "Course reviews" "dialogue"
    And I should see "hello" in the "Course reviews" "dialogue"
    And I should not see "Flag"
    And I click on "Close" "button" in the "Course reviews" "dialogue"
