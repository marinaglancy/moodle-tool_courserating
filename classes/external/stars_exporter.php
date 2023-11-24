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

/**
 * Exporter for 5-star rating
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stars_exporter extends exporter {

    /**
     * Constructor.
     *
     * @param float|null $rating
     */
    public function __construct(?float $rating) {
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

    /**
     * One star
     *
     * @param float $rating
     * @return array
     */
    protected function star(float $rating) {
        if ($rating < 0.25) {
            $icon = 'star-o';
        } else if ($rating >= 0.75) {
            $icon = 'star';
        } else {
            $icon = 'star-half';
        }
        return (new \pix_icon($icon, '', 'tool_courserating', []))
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
