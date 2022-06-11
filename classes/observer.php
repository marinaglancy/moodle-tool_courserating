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

namespace tool_courserating;

use core\event\course_created;
use core\event\course_deleted;
use core\event\course_updated;
use tool_courserating\local\models\summary;

/**
 * Events ovserver
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Observer for course_updated event
     *
     * @param course_updated $event
     */
    public static function course_updated(course_updated $event) {
        if (helper::get_setting(constants::SETTING_PERCOURSE)) {
            $summary = summary::get_for_course($event->courseid);
            if ($summary->get('ratingmode') != helper::get_course_rating_mode($event->courseid)) {
                // Rating mode has changed for this course.
                api::reindex($event->courseid);
            }
        }
    }

    /**
     * Observer for course_deleted event
     *
     * @param course_deleted $event
     */
    public static function course_deleted(course_deleted $event) {
        api::delete_all_data_for_course($event->courseid);
    }

    /**
     * Observer for course_created event
     *
     * @param course_created $event
     */
    public static function course_created(course_created $event) {
        api::reindex($event->courseid);
    }
}
