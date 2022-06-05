<?php

namespace tool_courserating;

class permission {

    public static function can_view_ratings(int $courseid): bool {
        return true; // TODO
    }

    public static function can_add_rating(int $courseid): bool {
        return true; // TODO
    }

    public static function can_delete_rating(int $ratingid, ?int $courseid = null): bool {
        return true; // TODO
    }

    public static function can_flag_rating(int $ratingid, ?int $courseid = null): bool {
        return true; // TODO
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
