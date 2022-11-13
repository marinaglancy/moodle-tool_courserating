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

namespace tool_courserating\output;

use tool_courserating\helper;
use tool_courserating\permission;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Course ratings list for Moodle 3.11 (before report builder)
 *
 * TODO remove when the minimum supported version is Moodle 4.0.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report311 extends \table_sql {
    /** @var \context */
    protected $context;

    /**
     * Constructor for the task_log table.
     *
     * @param \moodle_url $url
     */
    public function __construct(\moodle_url $url) {
        parent::__construct('courseratings');
        $this->define_baseurl($url);
        $courseid = $url->get_param('id');

        $this->context = \context_course::instance($courseid);
        $userfieldsapi = \core_user\fields::for_identity($this->context, false)->with_userpic();
        $userfields = $userfieldsapi->get_sql('u', false, '', 'userid', false)->selects;
        $extrauserfields = $userfieldsapi->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);
        $fields = $userfields . ',
             tool_courserating_rating.timemodified AS timemodified,
             tool_courserating_rating.rating AS rating,
             tool_courserating_rating.review AS review,
             (SELECT count(1) FROM {tool_courserating_flag} f WHERE f.ratingid = tool_courserating_rating.id) AS flags,
             tool_courserating_rating.id AS id,
             tool_courserating_rating.courseid AS courseid';

        $columns = $headers = [];
        $columns[] = 'fullname';
        $headers[] = get_string('fullname');
        foreach ($extrauserfields as $extrafield) {
            $columns[] = $extrafield;
            $headers[] = \core_user\fields::get_display_name($extrafield);
        }
        $columns = array_merge($columns, ['timemodified', 'rating', 'review', 'flags', 'actions']);
        $headers = array_merge($headers, [
            get_string('rating_timemodified', 'tool_courserating'),
            get_string('rating_rating', 'tool_courserating'),
            get_string('rating_review', 'tool_courserating'),
            get_string('rating_nofflags', 'tool_courserating'),
            get_string('rating_actions', 'tool_courserating'),
        ]);

        $from = '{tool_courserating_rating} tool_courserating_rating
            JOIN {user} u ON u.id = tool_courserating_rating.userid';

        $where = 'tool_courserating_rating.courseid = :rbparam0';
        $params = ['rbparam0' => $courseid];

        $this->set_sql($fields, $from, $where, $params);
        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->no_sorting('actions');
        $this->no_sorting('review');

        $this->sortable(true, 'timemodified', SORT_DESC);
    }

    /**
     * Magic formatter for the column actions
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row) {
        return helper::format_actions($row->id ?? 0, $row);
    }

    /**
     * Magic formatter for the column timemodified
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timemodified($row) {
        return helper::format_date($row->timemodified);
    }

    /**
     * Magic formatter for the column review
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_review($row) {
        return helper::format_review($row->review, $row);
    }

    /**
     * Magic formatter for the column rating
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_rating($row) {
        return helper::format_rating_in_course_report($row->rating, $row);
    }

    /**
     * Magic formatter for the column flags
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_flags($row) {
        return helper::format_flags_in_course_report($row->flags, $row);
    }

    /**
     * Get the column fullname value.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        global $OUTPUT;
        $row = fullclone($row);
        $row->id = $row->userid;
        if ($this->download) {
            return parent::col_fullname($row);
        }
        return $OUTPUT->user_picture($row, ['courseid' => $this->context->instanceid, 'includefullname' => true]);
    }
}
