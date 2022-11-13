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

use tool_courserating\api;
use tool_courserating\helper;
use tool_courserating\local\models\rating;

/**
 * Generator
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_courserating_generator extends testing_module_generator {

    /**
     * Set course rating mode
     *
     * @param int $courseid
     * @param int $mode
     */
    public function set_course_rating_mode(int $courseid, int $mode) {
        if ($data = helper::get_course_rating_enabled_data_in_cfield($courseid)) {
            $data->instance_form_save((object)[
                'id' => $courseid,
                $data->get_form_element_name() => $mode,
            ]);
            api::reindex($courseid);
        }
    }

    /**
     * Set courserating config and reindex
     *
     * @param string $name
     * @param mixed $value
     */
    public function set_config(string $name, $value) {
        set_config($name, $value, 'tool_courserating');
        api::reindex();
    }

    /**
     * Clear custom field cache
     *
     * Unfortunately we can not call the proper method from behat:
     * \core_course\customfield\course_handler::reset_caches()
     *
     * @return void
     */
    public function clear_course_custom_field_cache() {
        $reflection = new \ReflectionProperty(\core_course\customfield\course_handler::class, 'singleton');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);
    }

    /**
     * Create rating
     *
     * @param int $userid
     * @param int $courseid
     * @param int $rating
     * @param string $review
     * @return rating
     */
    public function create_rating(int $userid, int $courseid, int $rating, string $review = '') {
        $this->clear_course_custom_field_cache();
        $formdata = (object)[
            'review_editor' => ['text' => $review ?? '', 'format' => FORMAT_HTML],
            'review' => $review ?? '',
            'rating' => $rating,
        ];
        return api::set_rating($courseid, $formdata, $userid);
    }
}
