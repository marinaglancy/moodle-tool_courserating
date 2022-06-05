<?php

namespace tool_courserating\external;

use core\external\exporter;
use renderer_base;
use tool_courserating\local\models\summary;

class summary_exporter extends exporter {

    /** @var summary */
    protected $summary;

    /**
     * Constructor.
     *
     * @param array $related - related objects.
     */
    public function __construct(int $courseid, ?summary $summary = null) {
        if (!$summary) {
            $records = summary::get_records(['courseid' => $courseid]);
            if (count($records)) {
                $summary = reset($records);
            } else {
                $summary = new summary(0, (object)['courseid' => $courseid]);
            }
        }
        $this->summary = $summary;
        parent::__construct([], []);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array - Keys with their types.
     */
    protected static function define_other_properties() {
        return [
            'avgrating' => ['type' => PARAM_RAW],
            'stars' => ['type' => stars_exporter::read_properties_definition()],
            'lines' => [
                'type' => [
                    'star' => ['type' => stars_exporter::read_properties_definition()],
                    'percent' => ['type' => PARAM_RAW],
                ],
                'multiple' => true,
            ],
            'courseid' => ['type' => PARAM_INT],
            'cntall' => ['type' => PARAM_INT],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $summary = $this->summary;
        $courseid = $summary->get('courseid');
        $data = [
            'avgrating' => $summary->get('cntall') ? sprintf("%.1f", $summary->get('avgrating')) : '-',
            'cntall' => $summary->get('cntall'),
            'stars' => (new stars_exporter($summary->get('avgrating')))->export($output),
            'lines' => [],
            'courseid' => $courseid,
        ];
        foreach ([5,4,3,2,1] as $line) {
            $percent = $summary->get('cntall') ? round(100 * $summary->get('cnt0' . $line) / $summary->get('cntall')) : 0;
            $data['lines'][] = [
                'star' => (new stars_exporter($line))->export($output),
                'percent' =>  $percent . '%',
            ];
        }

        return $data;
    }
}