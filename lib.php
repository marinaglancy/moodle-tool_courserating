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
 * Plugin callbacks
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_courserating\external\ratings_list_exporter;

/**
 * Callback allowing to add js to $PAGE->requires
 */
function tool_courserating_before_http_headers() {
    // This is an implementation of a legacy callback that will only be called in older Moodle versions.
    // It will not be called in Moodle versions that contain the hook core\hook\output\before_http_headers,
    // instead, the callback tool_courserating\local\hooks\output\before_http_headers::callback will be executed.

    global $PAGE, $CFG;
    if (\tool_courserating\helper::course_ratings_enabled_anywhere() &&
            !in_array($PAGE->pagelayout, ['redirect', 'embedded'])) {
        // Add JS to all pages, the course ratings can be displayed on any page (for example course listings).
        $branch = $CFG->branch ?? '';
        $PAGE->requires->js_call_amd('tool_courserating/rating', 'init',
            [context_system::instance()->id, "{$branch}" < "400"]);
        if (\tool_courserating\helper::is_course_edit_page()) {
            $field = \tool_courserating\helper::get_course_rating_field();
            $PAGE->requires->js_call_amd('tool_courserating/rating', 'hideEditField',
                [$field->get('shortname')]);
        }
    }
    return null;
}

/**
 * Callback allowing to add contetnt inside the region-main, in the very end
 *
 * @return string
 */
function tool_courserating_before_footer() {
    // This is an implementation of a legacy callback that will only be called in older Moodle versions.
    // It will not be called in Moodle versions that contain the hook core\hook\output\before_footer_html_generation,
    // instead, the callback tool_courserating\local\hooks\output\before_footer_html_generation::callback will be executed.

    global $PAGE;
    $res = '';
    if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
        /** @var tool_courserating\output\renderer $output */
        $output = $PAGE->get_renderer('tool_courserating');
        if (($courseid = \tool_courserating\helper::is_course_page()) ||
            ($courseid = \tool_courserating\helper::is_single_activity_course_page())) {
            $res .= $output->course_rating_block($courseid);
        }
    }
    return $res;
}

/**
 * Callback allowing to add to <head> of the page
 *
 * @return string
 */
function tool_courserating_before_standard_html_head() {
    // This is an implementation of a legacy callback that will only be called in older Moodle versions.
    // It will not be called in Moodle versions that contain the hook core\hook\output\before_standard_head_html_generation,
    // instead, the callback tool_courserating\local\hooks\output\before_standard_head_html_generation::callback will be executed.

    $res = '';
    if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
        // Add CSS to all pages, the course ratings can be displayed on any page (for example course listings).
        $res .= '<style>' . \tool_courserating\helper::get_rating_colour_css() . '</style>';
    }
    return $res;
}

// @codingStandardsIgnoreStart
/* More callbacks that can be implemented

function tool_courserating_render_navbar_output() {
    // Added to the top navbar after messaging icon before the user picture/menu.
    return '';
}

function tool_courserating_add_htmlattributes() {
    // <html {{{ output.htmlattributes }}}>
    return [];
}

function tool_courserating_standard_after_main_region_html() {
    // Added in the very end of the page, must be floating element or otherwise it messes up layout
    return '';
}

function tool_courserating_standard_footer_html() {
    // Added after the "Reset user tour on this page" link in the popup footer
    return '';
}

function tool_courserating_before_standard_top_of_body_html() {
    // added before the <nav> element (top navbar)
    return '';
}


function tool_courserating_after_config() {
    return null;
}

function tool_courserating_after_require_login() {

}

function tool_courserating_extend_navigation_user($usernode, $user, $usercontext, $course, $coursecontext) {

}

function tool_courserating_extend_navigation_course($coursenode, $course, $coursecontext) {

}

function tool_courserating_extend_navigation_user_settings($usersetting, $user, $usercontext, $course, $coursecontext) {

}

function tool_courserating_extend_navigation_category_settings($categorynode, $catcontext) {

}

function tool_courserating_extend_navigation_frontpage($frontpage, $course, $coursecontext) {

}

function tool_courserating_user_preferences() {

}

function tool_courserating_get_course_category_contents($coursecat) {
    // To display what this category contains (on category deletion)
    return '';
}
*/
// @codingStandardsIgnoreEnd

/**
 * Fragment API callback
 *
 * @param array $args
 * @return string
 */
function tool_courserating_output_fragment_course_ratings_popup($args) {
    global $PAGE;
    if (!$courseid = clean_param($args['courseid'] ?? 0, PARAM_INT)) {
        throw new moodle_exception('missingparam', '', '', 'courseid');
    }
    \tool_courserating\permission::require_can_view_ratings($courseid);
    /** @var tool_courserating\output\renderer $output */
    $output = $PAGE->get_renderer('tool_courserating');
    return $output->course_ratings_popup($courseid);
}

/**
 * Fragment API callback
 *
 * @param array $args
 * @return string
 */
function tool_courserating_output_fragment_cfield($args) {
    global $PAGE;
    if (!$courseid = clean_param($args['courseid'] ?? 0, PARAM_INT)) {
        throw new moodle_exception('missingparam', '', '', 'courseid');
    }
    \tool_courserating\permission::require_can_view_ratings($courseid);
    /** @var tool_courserating\output\renderer $output */
    $output = $PAGE->get_renderer('tool_courserating');
    return $output->cfield($courseid);
}

/**
 * Fragment API callback
 *
 * @param array $args
 * @return string
 */
function tool_courserating_output_fragment_course_ratings_summary($args) {
    global $PAGE;
    if (!$courseid = clean_param($args['courseid'] ?? 0, PARAM_INT)) {
        throw new moodle_exception('missingparam', '', '', 'courseid');
    }
    \tool_courserating\permission::require_can_view_ratings($courseid);
    /** @var tool_courserating\output\renderer $output */
    $output = $PAGE->get_renderer('tool_courserating');
    $data = (new \tool_courserating\external\summary_exporter($courseid))->export($output);
    return $output->render_from_template('tool_courserating/course_ratings_summary', $data);
}

/**
 * Fragment API callback
 *
 * @param array $args
 * @return string
 */
function tool_courserating_output_fragment_rating_flag($args) {
    global $PAGE;
    /** @var tool_courserating\output\renderer $output */
    $output = $PAGE->get_renderer('tool_courserating');

    if (!$ratingid = clean_param($args['ratingid'] ?? 0, PARAM_INT)) {
        throw new moodle_exception('missingparam', '', '', 'ratingid');
    }

    $rating = new \tool_courserating\local\models\rating($args['ratingid']);
    \tool_courserating\permission::require_can_view_ratings($rating->get('courseid'));
    $data = (array)(new \tool_courserating\external\rating_exporter($rating))->export($output);
    return $output->render_from_template('tool_courserating/rating_flag', $data['ratingflag']);
}

/**
 * Map icons for font-awesome themes.
 */
function tool_courserating_get_fontawesome_icon_map() {
    return [
        'tool_courserating:star' => 'fa-star',
        'tool_courserating:star-o' => 'fa-star-o',
        'tool_courserating:star-half' => 'fa-star-half-full',
    ];
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable|void
 */
function tool_courserating_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG;
    require_once($CFG->dirroot . '/lib/externallib.php');
    \external_api::validate_context(context_system::instance());
    if ($itemtype === 'flag') {
        \tool_courserating\permission::require_can_flag_rating($itemid);
        if ($newvalue) {
            \tool_courserating\api::flag_review($itemid);
        } else {
            \tool_courserating\api::revoke_review_flag($itemid);
        }
        return \tool_courserating\api::get_flag_inplace_editable($itemid);
    }
}

/**
 * Fragment API callback
 *
 * @param array $args
 * @return string
 */
function tool_courserating_output_fragment_course_reviews($args) {
    global $PAGE;
    $args = [
        'courseid' => clean_param($args['courseid'] ?? 0, PARAM_INT),
        'offset' => clean_param($args['offset'] ?? 0, PARAM_INT),
        'withrating' => clean_param($args['withrating'] ?? 0, PARAM_INT),
    ];
    if (!$args['courseid']) {
        throw new moodle_exception('missingparam', '', '', 'courseid');
    }
    \tool_courserating\permission::require_can_view_ratings($args['courseid']);
    /** @var tool_courserating\output\renderer $output */
    $output = $PAGE->get_renderer('tool_courserating');
    $data = (new ratings_list_exporter($args))->export($output);
    return $output->render_from_template('tool_courserating/course_ratings_popup_reviews', $data);
}

/**
 * Serves the files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool|void false if file not found, does not return if found - just send the file
 */
function tool_courserating_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if (!\tool_courserating\helper::get_setting(\tool_courserating\constants::SETTING_USEHTML)) {
        return false;
    }
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }
    \tool_courserating\permission::require_can_view_ratings($context->instanceid);

    if ($filearea !== 'review') {
        return false;
    }
    $itemid = array_shift($args);

    $rating = \tool_courserating\local\models\rating::get_record(['courseid' => $context->instanceid, 'id' => $itemid]);
    if (!$rating) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/tool_courserating/$filearea/$itemid/$relativepath";
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    // Set security posture for in-browser display.
    if (!$forcedownload) {
        header("Content-Security-Policy: default-src 'none'; img-src 'self'");
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add 'Course ratings' to the course administration menu
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function tool_courserating_extend_navigation_course(\navigation_node $navigation, \stdClass $course, \context $context) {
    if (!\tool_courserating\permission::can_view_report($course->id)) {
        return;
    }
    $url = new moodle_url('/admin/tool/courserating/index.php', ['id' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'tool_courserating'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );
}
