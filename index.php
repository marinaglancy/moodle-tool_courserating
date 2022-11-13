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
 * Course ratings report for the course
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$courseid = required_param('id', PARAM_INT);

require_course_login($courseid);
\tool_courserating\permission::require_can_view_reports($courseid);
$PAGE->set_url(new moodle_url('/admin/tool/courserating/index.php', ['id' => $courseid]));
$PAGE->set_title($COURSE->shortname . ': ' . get_string('pluginname', 'tool_courserating'));
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_courserating'));

if (core_component::get_component_directory('core_reportbuilder')) {
    $report = \core_reportbuilder\system_report_factory::create(
        \tool_courserating\reportbuilder\local\systemreports\course_ratings_report::class,
        context_course::instance($courseid),
        '', '', 0, ['courseid' => $courseid]);

    echo $report->output();
} else {
    // TODO remove when the minimum supported version is Moodle 4.0.
    $table = new \tool_courserating\output\report311($PAGE->url);
    $table->out(50, true);
}

echo $OUTPUT->footer();
