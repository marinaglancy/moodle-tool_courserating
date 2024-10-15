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

// Check the global setting for hiding usernames
$hideusername_scope = get_config('tool_courserating', 'hideusername_scope');

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

// Only show the "Hide Usernames for this Course" block if the global setting is set to 'percourse'
if ($hideusername_scope === 'percourse') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if the 'hideusernamecourse' checkbox was set; default to 0 if not.
        $hideusernamecourse = optional_param('hideusernamecourse', 0, PARAM_BOOL);
        
        // Save the per-course setting with the course ID as part of the key
        set_config('hide_username_course_' . $courseid, $hideusernamecourse, 'tool_courserating');

        // Show a success message
        \core\notification::success(get_string('setting_updated', 'tool_courserating'));
    }

    // Get the current per-course setting value
    $hideusernamecourse = get_config('tool_courserating', 'hide_username_course_' . $courseid);
    ?>

    <!-- Add the toggle form below the ratings table -->
    <form method="post" action="">
        <div class="course-rating-settings mb-4">
            <h3 class="mt-5 mb-3"><?php echo get_string('hideusername_course', 'tool_courserating'); ?></h3>

            <div class="form-check mb-4 ml-3">
                <input class="form-check-input" type="checkbox" name="hideusernamecourse" id="hideusernamecourse" value="1" <?php echo $hideusernamecourse ? 'checked' : ''; ?>>
                <label class="form-check-label" for="hideusernamecourse">
                    <?php echo get_string('hideusername_course_desc', 'tool_courserating'); ?>
                </label>
            </div>
        </div>

        <div class="form-submit">
            <input type="submit" class="btn btn-primary" value="<?php echo get_string('savechanges', 'admin'); ?>">
        </div>
    </form>

    <?php
}

echo $OUTPUT->footer();