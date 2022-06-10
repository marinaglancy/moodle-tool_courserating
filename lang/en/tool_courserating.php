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

$string['addrating'] = 'Rate this course';
$string['cfielddescription'] = 'Do not edit, the content will be populated automatically every time somebody leaves a rating for this course.';
$string['colorrating'] = 'Colour of the rating';
$string['colorratingconfig'] = 'This is usually slightly darker than the star colour for the best visual effect';
$string['colorstar'] = 'Colour of the stars';
$string['courserating:delete'] = 'Delete course ratings and reviews, view flagged reviews';
$string['courserating:rate'] = 'Leave a rating for the course';
$string['coursereviews'] = 'Course reviews';
$string['deleterating'] = 'Permanently delete';
$string['deletereason'] = 'Reason for deletion';
$string['displayempty'] = 'Display no rating with gray stars';
$string['displayemptyconfig'] = 'For courses where rating is enabled but there are no ratings yet display gray stars. If not selected, such courses will have no rating displayed at all';
$string['flagrating'] = 'Flag';
$string['percourseoverride'] = 'Course overrides';
$string['percourseoverrideconfig'] = 'If enabled, a custom course field will be created that will allow to set when each individual course can be rated. The value above will be treated as the default';
$string['pluginname'] = 'Course ratings';
$string['ratebyanybody'] = 'Students can rate the course at any time';
$string['ratebycompleted'] = 'Students can rate only after completing the course';
$string['ratebydefault'] = 'Default value is: "{$a}"';
$string['ratebynoone'] = 'Course ratings are disabled';
$string['ratedcategory'] = 'Category where course ratings are allowed';
$string['rating'] = 'Rating';
$string['ratinglabel'] = 'Course rating';
$string['ratingmode'] = 'When can courses be rated';
$string['ratingmodeconfig'] = 'Additionally the capability to rate courses is checked';
$string['reindextask'] = 'Re-index course ratings';
$string['review'] = 'Review (optional)';
$string['revokeratingflag'] = 'Revoke';
$string['settingsdescription'] = 'Changing some of the settings may require re-indexing of all courses and course ratings. This will happen automatically on next cron run.';
$string['usersflagged'] = '{$a} user(s) have marked this review as inappropriate/offensive.';
$string['youflagged'] = 'You have flagged this review as inappropriate/offensive.';
