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

use core\output\inplace_editable;
use tool_courserating\event\flag_created;
use tool_courserating\event\flag_deleted;
use tool_courserating\event\rating_created;
use tool_courserating\event\rating_deleted;
use tool_courserating\event\rating_updated;
use tool_courserating\external\summary_exporter;
use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

/**
 * Methods to add/remove ratings
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Add or update user rating
     *
     * @param int $courseid
     * @param \stdClass $data
     * @param int $userid - only for phpunit and behat tests
     * @return rating
     */
    public static function set_rating(int $courseid, \stdClass $data, int $userid = 0): rating {
        global $USER;
        // TODO $userid can only be used in phpunit and behat.
        $userid = $userid ?: $USER->id;
        // TODO validate rating is within limits, trim/crop review.
        $rating = $data->rating;
        $ratingold = rating::get_record(['userid' => $userid, 'courseid' => $courseid]);
        if ($ratingold) {
            $oldrecord = $ratingold->to_record();
            $r = $ratingold;
            $review = self::prepare_review($r, $data);
            $r->set('rating', $rating);
            $r->set('review', $review);
            $r->save();
            $summary = summary::update_rating($courseid, $r, $oldrecord);
        } else {
            $r = new rating(0, (object)[
                'userid' => $userid,
                'courseid' => $courseid,
                'rating' => $rating,
            ]);
            $r->set('review', self::prepare_review(null, $data));
            $r->save();
            $review = self::prepare_review($r, $data);
            if ($review !== $r->get('review')) {
                $r->set('review', $review);
                $r->save();
            }
            $summary = summary::add_rating($courseid, $r);
        }
        self::update_course_rating_in_custom_field($summary);

        if ($ratingold) {
            rating_updated::create_from_rating($r, $oldrecord)->trigger();
        } else {
            rating_created::create_from_rating($r)->trigger();
        }

        return $r;
    }

    /**
     * Delete rating and review made by somebody else
     *
     * @param int $ratingid
     * @param string|null $reason
     * @return rating|null
     */
    public static function delete_rating(int $ratingid, string $reason = ''): ?rating {
        global $DB;
        if (!$rating = rating::get_record(['id' => $ratingid])) {
            return null;
        }
        $flagcount = $DB->count_records(flag::TABLE, ['ratingid' => $ratingid]);
        $record = $rating->to_record();
        $rating->delete();
        if ($context = \context_course::instance($record->courseid, IGNORE_MISSING)) {
            // Sometimes it might be called after course is deleted.
            get_file_storage()->delete_area_files($context->id, 'tool_courserating', 'review', $record->id);
        }
        if ($flagcount) {
            $DB->delete_records(flag::TABLE, ['ratingid' => $record->id]);
        }
        $summary = summary::delete_rating($record);
        self::update_course_rating_in_custom_field($summary);

        rating_deleted::create_from_rating($record, $flagcount, $reason)->trigger();

        return $rating;
    }

    /**
     * Update content of the course custom field that displays the rating
     *
     * @param summary|null $summary
     * @return void
     */
    protected static function update_course_rating_in_custom_field(?summary $summary) {
        global $PAGE;
        if (!$summary || !helper::get_course_rating_field()) {
            return;
        }
        $courseid = $summary->get('courseid');

        if ($summary->get('ratingmode') == constants::RATEBY_NOONE) {
            $ratingstr = '';
        } else {
            /** @var \tool_courserating\output\renderer $output */
            $output = $PAGE->get_renderer('tool_courserating');
            $data = (new summary_exporter(0, $summary))->export($output);
            $ratingstr = $output->render_from_template('tool_courserating/summary_for_cfield', $data);
        }

        if ($data = helper::get_course_rating_data_in_cfield($courseid)) {
            $data->instance_form_save((object)[
                'id' => $courseid,
                $data->get_form_element_name() => ['text' => $ratingstr, 'format' => FORMAT_HTML],
            ]);
        }
    }

    /**
     * Prepare review for storing (store files, convert to html)
     *
     * @param rating|null $rating
     * @param \stdClass $data
     * @return string
     */
    protected static function prepare_review(?rating $rating, \stdClass $data): string {
        $usehtml = helper::get_setting(constants::SETTING_USEHTML);
        if ($rating && $usehtml && !rating::review_is_empty($data->review_editor['text'] ?? '')) {
            $context = \context_course::instance($rating->get('courseid'));
            $data = file_postupdate_standard_editor($data, 'review', helper::review_editor_options($context), $context,
                'tool_courserating', 'review', $rating->get('id'));
            if ($data->reviewformat != FORMAT_HTML) {
                // We always store reviews as HTML, we don't even store the reviewformat field.
                // Do not apply filters now, they will be applied during display.
                return format_text($data->review, $data->reviewformat, ['filter' => false, 'context' => $context]);
            }
            return $data->review;
        } else if (!$usehtml && !rating::review_is_empty($data->review ?? '')) {
            return $data->review;
        } else {
            return '';
        }
    }

    /**
     * Prepare review to be displayed in a form (copy files to draft area)
     *
     * @param int $courseid
     * @return array|array[]
     */
    public static function prepare_rating_for_form(int $courseid): array {
        global $USER;
        $rv = [
            'review_editor' => ['text' => '', 'format' => FORMAT_HTML],
            'review' => '',
        ];
        if ($rating = rating::get_record(['userid' => $USER->id, 'courseid' => $courseid])) {
            $data = $rating->to_record();
            $rv['rating'] = $data->rating;
            if (helper::get_setting(constants::SETTING_USEHTML)) {
                $data->reviewformat = FORMAT_HTML;
                $context = \context_course::instance($courseid);
                $data = file_prepare_standard_editor($data, 'review', helper::review_editor_options($context), $context,
                    'tool_courserating', 'review', $data->id);
                $rv['review_editor'] = $data->review_editor;
            } else {
                $rv['review'] = clean_param($data->review, PARAM_TEXT);
            }
            return $rv;
        }
        return $rv;
    }

    /**
     * Flag somebody else's review
     *
     * @param int $ratingid
     * @return flag|null
     */
    public static function flag_review(int $ratingid): ?flag {
        global $USER;
        $flag = flag::get_records(['ratingid' => $ratingid, 'userid' => $USER->id]);
        if ($flag) {
            return null;
        }
        $rating = new rating($ratingid);
        $flag = new flag(0, (object)['userid' => $USER->id, 'ratingid' => $ratingid]);
        $flag->save();

        flag_created::create_from_flag($flag, $rating)->trigger();
        return $flag;
    }

    /**
     * Revoke a flag on somebody else's review
     *
     * @param int $ratingid
     * @return flag|null
     */
    public static function revoke_review_flag(int $ratingid): ?flag {
        global $USER;
        $flags = flag::get_records(['ratingid' => $ratingid, 'userid' => $USER->id]);
        $flag = reset($flags);
        if (!$flag) {
            return null;
        }
        $rating = new rating($ratingid);
        $oldrecord = $flag->to_record();
        $flag->delete();
        flag_deleted::create_from_flag($oldrecord, $rating)->trigger();
        return $flag;
    }

    /**
     * Get the flag
     *
     * @param int $ratingid
     * @param bool|null $hasflag
     * @return inplace_editable
     */
    public static function get_flag_inplace_editable(int $ratingid, ?bool $hasflag = null): inplace_editable {
        global $USER;

        if (!permission::can_flag_rating($ratingid)) {
            return new inplace_editable('tool_courserating', 'flag', $ratingid, false, '', 0, '');
        }

        if ($hasflag === null) {
            $hasflag = flag::count_records(['ratingid' => $ratingid, 'userid' => $USER->id]) > 0;
        }
        $displayvalue = $hasflag ? get_string('revokeratingflag', 'tool_courserating') :
            get_string('flagrating', 'tool_courserating');
        $edithint = $displayvalue;
        $r = new inplace_editable('tool_courserating', 'flag', $ratingid, true, $displayvalue,
            $hasflag ? 1 : 0, $edithint);
        $r->set_type_toggle([0, 1]);
        return $r;
    }

    /**
     * Re-index all courses, update ratings in the summary table and custom fields
     *
     * @param int $courseid
     * @return void
     */
    public static function reindex(int $courseid = 0) {
        global $DB, $SITE;

        $percourse = helper::get_setting(constants::SETTING_PERCOURSE);
        $ratingfield = helper::get_course_rating_field();
        $ratingmodefield = helper::get_course_rating_mode_field();

        if (!$ratingfield) {
            return;
        }

        $fields = 'c.id as courseid, d.value as cfield, s.cntall as summarycntall, s.ratingmode as summaryratingmode,
               (select count(1) from {tool_courserating_rating} r where r.courseid=c.id) as actualcntall ';
        $join = 'from {course} c
            left join {tool_courserating_summary} s on s.courseid = c.id
            left join {customfield_field} f on f.shortname = :field1
            left join {customfield_data} d on d.fieldid = f.id and d.instanceid = c.id ';
        $params = [
            'field1' => $ratingfield->get('shortname'),
            'siteid' => $SITE->id ?? SITEID,
        ];

        if ($percourse && $ratingmodefield) {
            // Each course may override whether course ratings are enabled.
            $fields .= ', dr.intvalue as rateby';
            $join .= ' left join {customfield_field} fr on fr.shortname = :field2
            left join {customfield_data} dr on dr.fieldid = fr.id and dr.instanceid = c.id';
            $params['field2'] = $ratingmodefield->get('shortname');
        }

        $sql = "SELECT $fields $join WHERE c.id <> :siteid ";
        if ($courseid) {
            $sql .= " AND c.id = :courseid ";
            $params['courseid'] = $courseid;
        } else {
            $sql .= " ORDER BY c.id DESC";
        }

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $record->actualratingmode = helper::get_setting(constants::SETTING_RATINGMODE);
            if ($percourse && $record->rateby && array_key_exists($record->rateby, constants::rated_courses_options())) {
                $record->actualratingmode = $record->rateby;
            }
            self::reindex_course($record);
        }
    }

    /**
     * Re-index individual course
     *
     * @param \stdClass $data contains fields: courseid, cfield, summarycntall, actualcntall
     *     where cfield is the actual value stored in the "course rating" custom course field,
     *     summarycntall - the field tool_courserating_summary.cntall that corresponds to this course,
     *     summaryratingmode - the field tool_courserating_summary.ratingmode that corresponds to this course,
     *     actualcntall - the actual count of ratings for this course (count(*) from tool_courserating_rating)
     *     actualratingmode - what actually must be the rating mode of this course
     */
    protected static function reindex_course(\stdClass $data) {
        $mustbeempty = $data->actualratingmode == constants::RATEBY_NOONE
            || (!$data->actualcntall && !helper::get_setting(constants::SETTING_DISPLAYEMPTY));

        if ($data->summaryratingmode != $data->actualratingmode) {
            // Rating mode for this course has changed.
            $summary = summary::get_for_course($data->courseid);
            $summary->set('ratingmode', $data->actualratingmode);
            if ($data->actualratingmode == constants::RATEBY_NOONE) {
                $summary->reset_all_counters();
            }
            $summary->save();
        }

        if ($mustbeempty) {
            // Course rating should not be displayed at all.
            if (!empty($data->cfield)) {
                $summary = $summary ?? summary::get_for_course($data->courseid);
                self::update_course_rating_in_custom_field($summary);
            }
        } else {
            // Update summary and cfield with the data.
            $summary = $summary ?? summary::get_for_course($data->courseid);
            $summary->recalculate();
            self::update_course_rating_in_custom_field($summary);
        }
    }

    /**
     * Completely delete all data related to a course (i.e. when course is deleted)
     *
     * @param int $courseid
     * @return void
     */
    public static function delete_all_data_for_course(int $courseid) {
        global $DB;
        $DB->execute('DELETE from {'.flag::TABLE.'} WHERE ratingid IN (SELECT id FROM {'.
            rating::TABLE.'} WHERE courseid = ?)', [$courseid]);
        $DB->delete_records(rating::TABLE, ['courseid' => $courseid]);
        $DB->delete_records(summary::TABLE, ['courseid' => $courseid]);
    }
}
