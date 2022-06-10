<?php

namespace tool_courserating;

use core\event\course_deleted;
use core\event\course_updated;
use tool_courserating\local\models\summary;

class observer {

    public static function course_updated(course_updated $event) {
        if (helper::get_setting(constants::SETTING_PERCOURSE)) {
            $summary = summary::get_for_course($event->courseid);
            if ($summary->get('ratingmode') != helper::get_course_rating_mode($event->courseid)) {
                // Rating mode has changed for this course.
                api::reindex($event->courseid);
            }
        }
    }

    public static function course_deleted(course_deleted $event) {
        api::delete_all_data_for_course($event->courseid);
    }
}
