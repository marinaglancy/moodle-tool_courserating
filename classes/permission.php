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

use required_capability_exception;
use tool_courserating\local\models\rating;

/**
 * Permission checks
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {

    /**
     * User can view rating for the course (ratings are enabled for this course)
     *
     * @param int $courseid
     * @return bool
     */
    public static function can_view_ratings(int $courseid): bool {
        global $USER;
        if (helper::get_course_rating_mode($courseid) == constants::RATEBY_NOONE) {
            return false;
        }
        $course = get_course($courseid);
        $context = \context_course::instance($courseid);
        return \core_course_category::can_view_course_info($course) ||
            is_enrolled($context, $USER, '', true);
    }

    /**
     * Can the current user add a rating for the specified course
     *
     * Example of checking last access:
     *     $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', ['userid' => $USER->id, 'courseid' => $courseid]);
     *
     * @param int $courseid
     * @return bool
     * @throws \coding_exception
     */
    public static function can_add_rating(int $courseid): bool {
        global $CFG, $USER;
        if (!has_capability('tool/courserating:rate', \context_course::instance($courseid))) {
            return false;
        }
        $courseratingmode = helper::get_course_rating_mode($courseid);
        if ($courseratingmode == constants::RATEBY_NOONE) {
            return false;
        }
        if ($courseratingmode == constants::RATEBY_COMPLETED) {
            require_once($CFG->dirroot.'/completion/completion_completion.php');
            // The course is supposed to be marked as completed at $timeend.
            $ccompletion = new \completion_completion(['userid' => $USER->id, 'course' => $courseid]);
            return $ccompletion->is_complete();
        }
        return true;
    }

    /**
     * Does current user have capability to delete ratings
     *
     * @param int $ratingid
     * @param int|null $courseid
     * @return bool
     */
    public static function can_delete_rating(int $ratingid, ?int $courseid = null): bool {
        if (!$courseid) {
            $courseid = (new rating($ratingid))->get('courseid');
        }
        return has_capability('tool/courserating:delete', \context_course::instance($courseid));
    }

    /**
     * Can current user flag the rating
     *
     * @param int $ratingid
     * @param int|null $courseid course id if known (saves a DB query)
     * @return bool
     */
    public static function can_flag_rating(int $ratingid, ?int $courseid = null): bool {
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (!$courseid) {
            $courseid = (new rating($ratingid))->get('courseid');
        }
        return self::can_view_ratings($courseid);
    }

    /**
     * User can view the 'Course ratings' item in the course administration
     *
     * @param int $courseid
     * @return bool
     */
    public static function can_view_report(int $courseid): bool {
        if (!helper::course_ratings_enabled_anywhere()) {
            return false;
        }
        $context = \context_course::instance($courseid);
        return has_capability('tool/courserating:reports', $context);
    }

    /**
     * Check that user can view rating or throw exception
     *
     * @param int $courseid
     * @throws \moodle_exception
     */
    public static function require_can_view_ratings(int $courseid): void {
        if (!self::can_view_ratings($courseid)) {
            throw new \moodle_exception('cannotview', 'tool_courserating');
        }
    }

    /**
     * Check that user can add/change rating or throw exception
     *
     * @param int $courseid
     * @throws \moodle_exception
     */
    public static function require_can_add_rating(int $courseid): void {
        if (!self::can_add_rating($courseid)) {
            throw new \moodle_exception('cannotrate', 'tool_courserating');
        }
    }

    /**
     * Check that user can delete rating or throw exception
     *
     * @param int $ratingid
     * @param int|null $courseid
     * @throws required_capability_exception
     */
    public static function require_can_delete_rating(int $ratingid, ?int $courseid = null): void {
        if (!$courseid) {
            $courseid = (new rating($ratingid))->get('courseid');
        }
        if (!self::can_delete_rating($ratingid, $courseid)) {
            throw new required_capability_exception(\context_course::instance($courseid),
                'tool/courserating:delete', 'nopermissions', '');
        }
    }

    /**
     * Check that user can flag rating or throw exception
     *
     * @param int $ratingid
     * @param int|null $courseid
     * @throws \moodle_exception
     */
    public static function require_can_flag_rating(int $ratingid, ?int $courseid = null): void {
        if (!self::can_flag_rating($ratingid, $courseid)) {
            throw new \moodle_exception('cannotview', 'tool_courserating');
        }
    }

    /**
     * Check that user can view rating or throw exception
     *
     * @param int $courseid
     * @throws \moodle_exception
     */
    public static function require_can_view_reports(int $courseid): void {
        if (!\tool_courserating\helper::course_ratings_enabled_anywhere()) {
            // TODO create a new string, maybe link to settings for admins?
            throw new \moodle_exception('ratebynoone', 'tool_courserating');
        }
        if (!self::can_view_report($courseid)) {
            throw new required_capability_exception(\context_course::instance($courseid),
                'tool/courserating:reports', 'nopermissions', '');
        }
    }
}
