<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_courserating;

use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use core\hook\output\before_footer_html_generation;

use context_course;
use context_system;
use stdClass;

/**
 * Tool courserating plugin hook listener
 *
 * @package     tool_courserating
 * @copyright   2024 Marina Glancy <marina.glancy@gmail.com>
 * @author      Renaat Debleu <info@eWallah.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Callback for the before_http_headers.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
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
    }

    /**
     * Callback for the before_http_head_html_genaration.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
            // Add CSS to all pages, the course ratings can be displayed on any page (for example course listings).
            $hook->add_html('<style>' . \tool_courserating\helper::get_rating_colour_css() . '</style>');
        }
    }

    /**
     * Callback for the before_footer_html_genaration.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;
        if (\tool_courserating\helper::course_ratings_enabled_anywhere()) {
            /** @var tool_courserating\output\renderer $output */
            $output = $PAGE->get_renderer('tool_courserating');
            if (($courseid = \tool_courserating\helper::is_course_page()) ||
                ($courseid = \tool_courserating\helper::is_single_activity_course_page())) {
                $hook->add_html($output->course_rating_block($courseid));
            }
        }
    }
}
