<?php

namespace tool_courserating;

use tool_courserating\local\models\rating;

class permission {

    public static function can_view_ratings(int $courseid): bool {
        global $USER;
        $course = get_course($courseid);
        $context = \context_course::instance($courseid);
        return (\core_course_category::can_view_course_info($course) ||
            is_enrolled($context, $USER, '', true)) &&
            helper::get_course_rating_mode($courseid) != constants::RATEBY_NOONE;
    }

    public static function can_add_rating(int $courseid): bool {
        if  (!has_capability('tool/courserating:rate', \context_course::instance($courseid))) {
            return false;
        }
        $courserateby = helper::get_course_rating_mode($courseid);
        if ($courserateby == constants::RATEBY_NOONE) {
            return false;
        }
        // TODO check completion
        return true;
    }

    public static function can_delete_rating(int $ratingid, ?int $courseid = null): bool {
        if (!$courseid) {
            $courseid = (new rating($ratingid))->get('courseid');
        }
        return has_capability('tool/courserating:delete', \context_course::instance($courseid));
    }

    public static function can_flag_rating(int $ratingid, ?int $courseid = null): bool {
        return self::can_view_ratings($courseid);
    }

    public static function require_can_view_ratings(int $courseid): void {
        if (!self::can_view_ratings($courseid)) {
            throw new \moodle_exception('oops'); // TODO
        }
    }

    public static function require_can_add_rating(int $courseid): void {
        if (!self::can_add_rating($courseid)) {
            throw new \moodle_exception('You don\'t have permission to leave rating to this course'); // TODO
        }
    }

    public static function require_can_delete_rating(int $ratingid, ?int $courseid = null): void {
        if (!self::can_delete_rating($ratingid, $courseid)) {
            throw new \moodle_exception('oops'); // TODO
        }
    }

    public static function require_can_flag_rating(int $ratingid, ?int $courseid = null): void {
        if (!self::can_flag_rating($ratingid, $courseid)) {
            throw new \moodle_exception('oops'); // TODO
        }
    }
}
