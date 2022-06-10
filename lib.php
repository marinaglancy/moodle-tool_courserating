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

/**
 * Callback allowing to add js to $PAGE->requires
 */
function tool_courserating_before_http_headers() {
    global $PAGE;
    if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
        $PAGE->requires->js_call_amd('tool_courserating/rating', 'init', [context_system::instance()->id]);
    }
    return null;
}

/**
 * Callback allowing to add contetnt inside the region-main, in the very end
 *
 * @return string
 */
function tool_courserating_before_footer() {
    global $PAGE;
    $res = '';
    if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
        /** @var tool_courserating\output\renderer $output */
        $output = $PAGE->get_renderer('tool_courserating');
        if (($courseid = \tool_courserating\helper::is_course_page()) ||
            ($courseid = \tool_courserating\helper::is_single_activity_course_page())) {
            $res .= $output->course_rating_block($courseid);
        }
        $res .= '<style>'.\tool_courserating\helper::get_rating_colour_css().'</style>';
    }
    return $res;
}

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

function tool_courserating_before_standard_html_head() {
    // Can add meta tags here
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
 * @return \core\output\inplace_editable
 */
function tool_courserating_inplace_editable($itemtype, $itemid, $newvalue) {
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
