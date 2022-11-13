<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tool_courserating
 * @category    string
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrating'] = 'Leave a rating';
$string['barwithrating'] = '{$a->rating} star represent {$a->percent} of rating';
$string['cannotrate'] = 'You don\'t have permission to leave rating to this course';
$string['cannotview'] = 'You don\'t have permission to view ratings for this course';
$string['cfielddescription'] = 'Do not edit, the content will be populated automatically every time somebody leaves a rating for this course.';
$string['colorrating'] = 'Colour of the rating';
$string['colorratingconfig'] = 'This is usually slightly darker than the star colour for the best visual effect';
$string['colorstar'] = 'Colour of the stars';
$string['courserating:delete'] = 'Delete course ratings and reviews, view flagged reviews';
$string['courserating:rate'] = 'Leave a rating for the course';
$string['courserating:reports'] = 'View course ratings reports';
$string['coursereviews'] = 'Course reviews';
$string['datasource_courseratings'] = "Course ratings";
$string['deleterating'] = 'Permanently delete';
$string['deletereason'] = 'Reason for deletion';
$string['displayempty'] = 'Display no rating with gray stars';
$string['displayemptyconfig'] = 'For courses where rating is enabled but there are no ratings yet display gray stars. If not selected, such courses will have no rating displayed at all';
$string['editrating'] = 'Edit your rating';
$string['entity_rating'] = "Course rating by user";
$string['entity_summary'] = "Course rating summary";
$string['event:flag_created'] = 'Course rating flagged';
$string['event:flag_deleted'] = 'Course rating flag revoked';
$string['event:rating_created'] = 'Course rating created';
$string['event:rating_deleted'] = 'Course rating deleted';
$string['event:rating_updated'] = 'Course rating updated';
$string['flagrating'] = 'Flag';
$string['parentcss'] = 'CSS selector for parent element';
$string['parentcssconfig'] = 'Course rating will be displayed on the course page as the last child of the DOM element that matches this selector. You may need to override it if the site uses a custom theme and you want to specify a custom parent. If left empty, the default value will be used. For Moodle 4.0 the default is "#page-header", for Moodle 3.11 the default is "#page-header .card-body, #page-header #course-header, #page-header".';
$string['percourseoverride'] = 'Course overrides';
$string['percourseoverrideconfig'] = 'If enabled, a custom course field will be created that will allow to set when each individual course can be rated. The value of the setting "When can courses be rated" will be treated as the default';
$string['pluginname'] = 'Course ratings';
$string['privacy:metadata:tool_courserating:reason'] = 'Reason';
$string['privacy:metadata:tool_courserating:reasoncode'] = 'Reason code';
$string['privacy:metadata:tool_courserating:timecreated'] = 'Time created';
$string['privacy:metadata:tool_courserating:timemodified'] = 'Time modified';
$string['privacy:metadata:tool_courserating_flag'] = 'Flagged ratings';
$string['privacy:metadata:tool_courserating_flag:id'] = 'Id';
$string['privacy:metadata:tool_courserating_flag:ratingid'] = 'Rating id';
$string['privacy:metadata:tool_courserating_flag:userid'] = 'User id';
$string['privacy:metadata:tool_courserating_rating'] = 'Course ratings';
$string['privacy:metadata:tool_courserating_rating:cohortid'] = 'Course id';
$string['privacy:metadata:tool_courserating_rating:hasreview'] = 'Has review';
$string['privacy:metadata:tool_courserating_rating:id'] = 'Id';
$string['privacy:metadata:tool_courserating_rating:rating'] = 'Rating';
$string['privacy:metadata:tool_courserating_rating:review'] = 'Review';
$string['privacy:metadata:tool_courserating_rating:timecreated'] = 'Time created';
$string['privacy:metadata:tool_courserating_rating:timemodified'] = 'Time modified';
$string['privacy:metadata:tool_courserating_rating:userid'] = 'User';
$string['ratebyanybody'] = 'Students can rate the course at any time';
$string['ratebycompleted'] = 'Students can rate only after completing the course';
$string['ratebydefault'] = 'Default value is: "{$a}"';
$string['ratebynoone'] = 'Course ratings are disabled';
$string['ratedcategory'] = 'Category where course ratings are allowed';
$string['rating'] = 'Rating';
$string['rating_actions'] = "Actions";
$string['rating_hasreview'] = "Has review";
$string['rating_nofflags'] = "Number of flags";
$string['rating_rating'] = "Course rating";
$string['rating_review'] = "Review";
$string['rating_timecreated'] = "Time created";
$string['rating_timemodified'] = "Time modified";
$string['ratingasstars'] = 'Course rating as stars';
$string['ratingdeleted'] = 'Rating deleted';
$string['ratinglabel'] = 'Course rating';
$string['ratingmode'] = 'When can courses be rated';
$string['ratingmodeconfig'] = 'Additionally the capability to rate courses is checked';
$string['reindextask'] = 'Re-index course ratings';
$string['review'] = 'Review (optional)';
$string['revokeratingflag'] = 'Revoke';
$string['settingsdescription'] = 'Changing some of the settings may require re-indexing of all courses and course ratings. This will happen automatically on next cron run.';
$string['showallratings'] = 'Show all';
$string['showmorereviews'] = 'Show more';
$string['summary_avgrating'] = "Course rating";
$string['summary_cnt01'] = "Ratio of 1-star ratings";
$string['summary_cnt02'] = "Ratio of 2-star ratings";
$string['summary_cnt03'] = "Ratio of 3-star ratings";
$string['summary_cnt04'] = "Ratio of 4-star ratings";
$string['summary_cnt05'] = "Ratio of 5-star ratings";
$string['summary_cntall'] = "Number of ratings";
$string['summary_cntreviews'] = "Number of reviews";
$string['summary_ratingmode'] = "Course rating mode";
$string['summary_sumrating'] = "Total of all ratings";
$string['usehtml'] = 'Use rich text editor for reviews';
$string['usehtmlconfig'] = 'Allow students to use rich text editor for the reviews, include links and attach files.';
$string['usersflagged'] = '{$a} user(s) have flagged this review as inappropriate/offensive.';
$string['viewallreviews'] = 'View all reviews';
$string['youflagged'] = 'You have flagged this review as inappropriate/offensive.';
