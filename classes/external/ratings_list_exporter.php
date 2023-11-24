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

namespace tool_courserating\external;

use core\external\exporter;
use renderer_base;
use tool_courserating\constants;
use tool_courserating\local\models\rating;

/**
 * Exporter for the list of ratings (used in the rating summary popup)
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ratings_list_exporter extends exporter {

    /**
     * Constructor.
     *
     * @param array $related - related objects.
     */
    public function __construct($related) {
        parent::__construct([], $related);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'limit' => PARAM_INT . '?',
            'offset' => PARAM_INT . '?',
            'courseid' => PARAM_INT . '?',
            'showempty' => PARAM_BOOL . '?',
            'withrating' => PARAM_INT . '?',
        ];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array - Keys with their types.
     */
    protected static function define_other_properties() {
        return [
            'ratings' => [
                'type' => rating_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'offset' => [
                'type' => PARAM_INT,
            ],
            'hasmore' => [
                'type' => PARAM_BOOL,
            ],
            'nextoffset' => [
                'type' => PARAM_INT,
            ],
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'systemcontextid' => [
                'type' => PARAM_INT,
            ],
            'withrating' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $courseid = $this->related['courseid'];
        $offset = $this->related['offset'] ?: 0;
        $limit = $this->related['limit'] ?: constants::REVIEWS_PER_PAGE;
        $withrating = $this->related['withrating'] ?: 0;

        $reviews = rating::get_records_select(
            'courseid = :courseid'.
            (empty($this->related['showempty']) ? ' AND hasreview = 1' : '').
            ($withrating ? ' AND rating = :rating' : ''),
            ['courseid' => $courseid, 'rating' => $withrating],
            'timemodified DESC, id DESC', '*', $offset, $limit + 1);

        $data = [
            'ratings' => [],
            'offset' => $offset,
            'hasmore' => count($reviews) > $limit,
            'nextoffset' => $offset + $limit,
            'courseid' => $courseid,
            'systemcontextid' => \context_system::instance()->id,
            'withrating' => $withrating,
        ];

        $reviews = array_slice(array_values($reviews), 0, $limit);
        foreach ($reviews as $review) {
            $data['ratings'][] = (new rating_exporter($review))->export($output);
        }

        return $data;
    }
}
