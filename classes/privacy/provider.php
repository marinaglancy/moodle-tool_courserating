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

namespace tool_courserating\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_courserating\api;
use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;
use tool_courserating\task\reindex;

/**
 * Privacy provider
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * get_metadata
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_courserating_rating',
            [
                'id' => 'privacy:metadata:tool_courserating_rating:id',
                'courseid' => 'privacy:metadata:tool_courserating_rating:cohortid',
                'userid' => 'privacy:metadata:tool_courserating_rating:userid',
                'rating' => 'privacy:metadata:tool_courserating_rating:rating',
                'review' => 'privacy:metadata:tool_courserating_rating:review',
                'hasreview' => 'privacy:metadata:tool_courserating_rating:hasreview',
                'timecreated' => 'privacy:metadata:tool_courserating_rating:timecreated',
                'timemodified' => 'privacy:metadata:tool_courserating_rating:timemodified',
            ],
            'privacy:metadata:tool_courserating_rating'
        );

        $collection->add_database_table(
            'tool_courserating_flag',
            [
                'id' => 'privacy:metadata:tool_courserating_flag:id',
                'ratingid' => 'privacy:metadata:tool_courserating_flag:ratingid',
                'userid' => 'privacy:metadata:tool_courserating_flag:userid',
                'reasoncode' => 'privacy:metadata:tool_courserating:reasoncode',
                'reason' => 'privacy:metadata:tool_courserating:reason',
                'timecreated' => 'privacy:metadata:tool_courserating:timecreated',
                'timemodified' => 'privacy:metadata:tool_courserating:timemodified',
            ],
            'privacy:metadata:tool_courserating_flag'
        );

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        return $collection;
    }

    /**
     * get_contexts_for_userid
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Own ratings.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {tool_courserating_rating} r
                   JOIN {context} ctx
                       ON ctx.instanceid = r.courseid AND ctx.contextlevel = :coursecontext
                 WHERE r.userid = :userid";

        $params = [
            'userid'        => $userid,
            'coursecontext' => CONTEXT_COURSE,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Flags for other ratings.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {tool_courserating_rating} r
                  JOIN {tool_courserating_flag} f ON f.ratingid = r.id
                   JOIN {context} ctx
                       ON ctx.instanceid = r.courseid AND ctx.contextlevel = :coursecontext
                 WHERE f.userid = :userid";

        $params = [
            'userid'        => $userid,
            'coursecontext' => CONTEXT_COURSE,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * export_user_data
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            }
        }

        if (empty($courseids)) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        [$coursesql, $courseparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        // Retrieve the tool_courserating_rating records created for the user.
        $sql = "SELECT r.id, r.userid, r.courseid, r.rating, r.review, r.hasreview, r.timecreated, r.timemodified,
                       c.shortname,
                       c.fullname
                  FROM {tool_courserating_rating} r
                  JOIN {course} c ON c.id = r.courseid
                 WHERE r.userid = :userid
                       AND c.id {$coursesql}";

        $params = ['userid' => $userid] + $courseparams;

        $ratings = $DB->get_records_sql($sql, $params);

        foreach ($ratings as $rating) {
            $subcontext = [
                get_string('pluginname', 'tool_courserating'),
                $rating->shortname,
            ];

            $context = \context_course::instance($rating->courseid);
            $writer = writer::with_context($context);

            $data = (object) [
                'shortname' => $rating->shortname,
                'fullname' => $rating->fullname,
                'rating' => $rating->rating,
                'review' => $writer->rewrite_pluginfile_urls(
                    $subcontext,
                    'tool_courserating',
                    'review',
                    $rating->id,
                    $rating->review
                ),
                'hasreview' => $rating->hasreview,
                'userid' => transform::user($rating->userid),
                'timecreated' => transform::datetime($rating->timecreated),
                'timemodified' => transform::datetime($rating->timemodified),
            ];

            $writer->export_data($subcontext, $data)
                ->export_area_files($subcontext, 'tool_courserating', 'review', $rating->id);
        }

        // Retrieve the flags that the user placed on the reviews of other users.
        $sql = "SELECT f.id, f.ratingid, f.reasoncode, f.reason, f.timecreated, f.timemodified,
                       r.courseid,
                       c.shortname
                  FROM {tool_courserating_flag} f
                  JOIN {tool_courserating_rating} r ON r.id = f.ratingid
                  JOIN {course} c ON c.id = r.courseid
                 WHERE f.userid = :userid
                       AND c.id {$coursesql}
              ORDER BY r.courseid, f.id";

        $flagspercourse = [];
        foreach ($DB->get_records_sql($sql, $params) as $flag) {
            $flagspercourse[$flag->courseid]['shortname'] = $flag->shortname;
            $flagspercourse[$flag->courseid]['flags'][] = (object) [
                'ratingid' => $flag->ratingid,
                'reasoncode' => $flag->reasoncode,
                'reason' => $flag->reason,
                'timecreated' => transform::datetime($flag->timecreated),
                'timemodified' => transform::datetime($flag->timemodified),
            ];
        }

        foreach ($flagspercourse as $courseid => $coursedata) {
            $subcontext = [
                get_string('pluginname', 'tool_courserating'),
                $coursedata['shortname'],
                get_string('privacy:flags', 'tool_courserating'),
            ];
            writer::with_context(\context_course::instance($courseid))
                ->export_data($subcontext, (object) ['flags' => $coursedata['flags']]);
        }
    }

    /**
     * delete_data_for_all_users_in_context
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel == CONTEXT_COURSE) {
            api::delete_all_data_for_course($context->instanceid);
        }
    }

    /**
     * delete_data_for_user
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            }
        }
        $userid = $contextlist->get_user()->id;
        self::delete_data_for_user_in_courses($userid, $courseids);
    }

    /**
     * delete_data_for_user_in_courses
     *
     * @param int $userid
     * @param array $courseids
     */
    protected static function delete_data_for_user_in_courses(int $userid, array $courseids) {
        global $DB;
        if (!$userid || empty($courseids)) {
            return;
        }
        [$sql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sqlrating = 'SELECT id, courseid FROM {tool_courserating_rating} WHERE courseid ' . $sql . ' AND userid = :userid';
        $params['userid'] = $userid;
        $sqlflags = 'SELECT f.id FROM {tool_courserating_flag} f JOIN {tool_courserating_rating} r ON f.ratingid = r.id
            WHERE r.courseid ' . $sql . ' AND f.userid = :userid';
        $flags = $DB->get_fieldset_sql($sqlflags, $params);
        if ($flags) {
            [$sqlf, $pf] = $DB->get_in_or_equal($flags);
            $DB->execute('DELETE FROM {tool_courserating_flag} WHERE id ' . $sqlf, $pf);
        }

        // Delete the user's ratings together with the files embedded in their reviews
        // and the flags that other users placed on them.
        $ratings = $DB->get_records_sql_menu($sqlrating, $params);
        if (!$ratings) {
            return;
        }
        $fs = get_file_storage();
        foreach ($ratings as $ratingid => $cid) {
            if ($context = \context_course::instance($cid, IGNORE_MISSING)) {
                $fs->delete_area_files($context->id, 'tool_courserating', 'review', $ratingid);
            }
        }
        [$sqlr, $pr] = $DB->get_in_or_equal(array_keys($ratings));
        $DB->delete_records_select(flag::TABLE, 'ratingid ' . $sqlr, $pr);
        $DB->delete_records_select(rating::TABLE, 'id ' . $sqlr, $pr);
        foreach (array_unique($ratings) as $cid) {
            reindex::schedule_course($cid);
        }
    }

    /**
     * get_users_in_context
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT DISTINCT r.userid
                  FROM {tool_courserating_rating} r
                 WHERE r.courseid = :courseid";
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT DISTINCT f.userid
                  FROM {tool_courserating_rating} r
                  JOIN {tool_courserating_flag} f ON f.ratingid = r.id
                 WHERE r.courseid = :courseid";
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * delete_data_for_users
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($userlist->get_context()->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $courseid = $userlist->get_context()->instanceid;
        foreach ($userlist->get_userids() as $userid) {
            self::delete_data_for_user_in_courses($userid, [$courseid]);
        }
    }
}
