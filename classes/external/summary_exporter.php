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
use tool_courserating\helper;
use tool_courserating\local\models\summary;
use tool_courserating\permission;

/**
 * Exporter for rating summary (how many people gave 5 stars, etc)
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary_exporter extends exporter {

    /** @var summary */
    protected $summary;

    /**
     * Constructor.
     *
     * @param int $courseid
     * @param summary|null $summary
     * @param bool $overridedisplayempty
     */
    public function __construct(int $courseid, ?summary $summary = null, bool $overridedisplayempty = false) {
        if (!$summary) {
            $records = summary::get_records(['courseid' => $courseid]);
            if (count($records)) {
                $summary = reset($records);
            } else {
                $summary = new summary(0, (object)['courseid' => $courseid]);
            }
        }
        $this->summary = $summary;
        parent::__construct([], ['overridedisplayempty' => $overridedisplayempty]);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'overridedisplayempty' => 'bool',
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
            'stars' => ['type' => stars_exporter::read_properties_definition()],
            'lines' => [
                'type' => [
                    'rating' => ['type' => PARAM_INT],
                    'star' => ['type' => stars_exporter::read_properties_definition()],
                    'percent' => ['type' => PARAM_RAW],
                ],
                'multiple' => true,
            ],
            'courseid' => ['type' => PARAM_INT],
            'cntall' => ['type' => PARAM_INT],
            'displayempty' => ['type' => PARAM_INT],
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
        $avgrating = $summary->get('cntall') ? $summary->get('avgrating') : 0;
        $data = [
            'avgrating' => $summary->get('cntall') ? sprintf("%.1f", $summary->get('avgrating')) : '-',
            'cntall' => $summary->get('cntall'),
            'stars' => (new stars_exporter($avgrating))->export($output),
            'lines' => [],
            'courseid' => $courseid,
            'displayempty' => !empty($this->related['overridedisplayempty'])
                || helper::get_setting(constants::SETTING_DISPLAYEMPTY),
        ];
        foreach ([5, 4, 3, 2, 1] as $line) {
            $percent = $summary->get('cntall') ? round(100 * $summary->get('cnt0' . $line) / $summary->get('cntall')) : 0;
            $data['lines'][] = [
                'rating' => $line,
                'star' => (new stars_exporter($line))->export($output),
                'percent' => $percent . '%',
            ];
        }

        return $data;
    }
}
