<?php

namespace tool_courserating;

use core\event\course_updated;

class observer {

    public static function course_updated(course_updated $event) {
        if (helper::get_setting(constants::SETTING_PERCOURSE)) {
            // We don't know exactly if the value of the custom field 'percourse' has changed.
            api::reindex($event->courseid);
        }
    }
}