<?php

namespace tool_courserating\external;

use core\external\exporter;
use renderer_base;
use tool_courserating\local\models\summary;
use tool_courserating\output\renderer;

class course_ratings_summary extends exporter {

    /**
     * Constructor.
     *
     * @param array $related - related objects.
     */
    public function __construct(int $courseid) {
        parent::__construct([], ['courseid' => $courseid]);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'courseid' => PARAM_INT,
        ];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array - Keys with their types.
     */
    protected static function define_other_properties() {
        return [
            'avgrating' => ['type' => PARAM_RAW],
            'stars' => ['type' => PARAM_RAW],
            'lines' => [
                'type' => [
                    'star' => ['type' => PARAM_RAW],
                    'percent' => ['type' => PARAM_RAW],
                ],
                'multiple' => true,
            ],
            'courseid' => ['type' => PARAM_INT],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $PAGE;
        $renderer = ($output instanceof renderer) ? $output : $PAGE->get_renderer('tool_courserating');
        $courseid = $this->related['courseid'];
        $summary = summary::get_record(['courseid' => $courseid]);
        $data = [
            'avgrating' => $summary->get('cntall') ? sprintf("%.1f", $summary->get('avgrating')) : '-',
            'stars' => $renderer->stars($summary->get('avgrating')),
            'lines' => [],
            'courseid' => $courseid,
        ];
        foreach ([5,4,3,2,1] as $line) {
            $percent = $summary->get('cntall') ? round(100 * $summary->get('cnt0' . $line) / $summary->get('cntall')) : 0;
            $data['lines'][] = ['star' => $renderer->stars($line), 'percent' =>  $percent . '%'];
        }

        return $data;
    }
}