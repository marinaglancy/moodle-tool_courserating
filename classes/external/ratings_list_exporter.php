<?php

namespace tool_courserating\external;

use core\external\exporter;
use renderer_base;
use tool_courserating\constants;
use tool_courserating\local\models\rating;

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
            'hasmore' => [
                'type' => PARAM_BOOL
            ],
            'nextoffset' => [
                'type' => PARAM_INT
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

        $reviews = rating::get_records_select(
            'courseid = :courseid AND hasreview = 1',
            ['courseid' => $courseid],
            'timecreated DESC, id DESC', '*', $offset, $limit + 1);

        $data = [
            'ratings' => [],
            'nextoffset' => $offset + $limit,
            'hasmore' => count($reviews) > $limit,
        ];

        $reviews = array_slice(array_values($reviews), 0, $limit);
        foreach ($reviews as $review) {
            $data['ratings'][] = (new rating_exporter($review))->export($output);
        }

        return $data;
    }
}