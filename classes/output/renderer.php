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

namespace tool_courserating\output;

use plugin_renderer_base;
use tool_courserating\api;
use tool_courserating\constants;
use tool_courserating\external\summary_exporter;
use tool_courserating\external\ratings_list_exporter;
use tool_courserating\helper;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;
use tool_courserating\permission;

/**
 * Renderer
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Reads contents of a custom field and displays it
     *
     * @param int $courseid
     * @return string
     */
    public function cfield(int $courseid): string {
        $summary = summary::get_for_course($courseid);
        $data = (new summary_exporter(0, $summary))->export($this);
        return $this->render_from_template('tool_courserating/summary_for_cfield', $data);
    }

    /**
     * Content of a course rating summary popup
     *
     * @param int $courseid
     * @return string
     */
    public function course_ratings_popup(int $courseid): string {
        global $USER;
        $data1 = (new summary_exporter($courseid))->export($this);
        $data2 = (new ratings_list_exporter(['courseid' => $courseid]))->export($this);
        $data = (array)$data1 + (array)$data2;
        $data['canrate'] = permission::can_add_rating($courseid);
        $data['hasrating'] = $data['canrate'] && rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);
        $this->page->requires->js_call_amd('tool_courserating/rating', 'setupViewRatingsPopup', []);
        return $this->render_from_template('tool_courserating/course_ratings_popup', $data);
    }

    /**
     * Course review widget to be added to the course page
     *
     * @param int $courseid
     * @return string
     */
    public function course_rating_block(int $courseid): string {
        global $CFG, $USER;
        if (!permission::can_view_ratings($courseid)) {
            return '';
        }
        $summary = summary::get_for_course($courseid);
        $canrate = permission::can_add_rating($courseid);
        $data = (new summary_exporter(0, $summary, $canrate))->export($this);
        $data->canrate = $canrate;
        $data->hasrating = $canrate && rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);

        $branch = $CFG->branch ?? '';
        if ($parentcss = helper::get_setting(constants::SETTING_PARENTCSS)) {
            $data->parentelement = $parentcss;
        } else if ("{$branch}" === '311') {
            $data->parentelement = '#page-header .card-body, #page-header #course-header, #page-header';
        } else if ("{$branch}" >= '400') {
            $data->parentelement = '#page-header';
            $data->extraclasses = 'pb-2';
        }
        return $this->render_from_template('tool_courserating/course_rating_block', $data);
    }
}
