<?php

namespace tool_courserating\external;

use core\external\exporter;
use renderer_base;

class stars_exporter extends exporter {

    /**
     * Constructor.
     *
     * @param float $rating
     */
    public function __construct(float $rating) {
        parent::__construct([], ['rating' => $rating]);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'rating' => PARAM_FLOAT,
        ];
    }

    /**
     * Return the list of additional properties used only for display.
     *
     * @return array - Keys with their types.
     */
    protected static function define_other_properties() {
        return [
            'staricons' => [
                'type' => [
                    'key' => ['type' => PARAM_RAW],
                    'component' => ['type' => PARAM_RAW],
                    'title' => ['type' => PARAM_RAW],
                ],
                'multiple' => true,
            ],
        ];
    }

    protected function star(float $rating) {
        if ($rating < 0.25) {
            $icon = 'star-o';
        } else if ($rating >= 0.75) {
            $icon = 'star';
        } else {
            $icon = 'star-half';
        }
        return (new \pix_icon($icon, 'rating' /* TODO */, 'tool_courserating', []))
            ->export_for_pix();
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $rating = $this->related['rating'];
        $rating = round($rating * 20) / 20;
        $icons = array_fill(0, floor($rating), $this->star(1));
        $icons[] = $this->star($rating - floor($rating));
        $icons = array_slice($icons, 0, 5);
        $icons = array_merge($icons, array_fill(0, 5 - count($icons), $this->star(0)));
        return [
            'staricons' => $icons,
        ];
    }

}