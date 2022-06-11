<?php

namespace tool_courserating;

use core\output\inplace_editable;
use core_customfield\field_controller;
use tool_brickfield\local\tool\errors;
use tool_courserating\external\rating_exporter;
use tool_courserating\external\summary_exporter;
use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

class api {

    public static function set_rating(int $courseid, \stdClass $data): rating {
        global $USER;
        // TODO validate rating is within limits, trim/crop review.
        $rating = $data->rating;
        if ($r = rating::get_record(['userid' => $USER->id, 'courseid' => $courseid])) {
            $oldrecord = $r->to_record();
            $review = self::prepare_review($r, $data);
            $r->set('rating', $rating);
            $r->set('review', $review);
            $r->save();
            $summary = summary::update_rating($courseid, $r, $oldrecord);
        } else {
            $r = new rating(0, (object)[
                'userid' => $USER->id,
                'courseid' => $courseid,
                'rating' => $rating,
                'review' => self::prepare_review(null, $data),
            ]);
            $r->save();
            $review = self::prepare_review($r, $data);
            if ($review !== $r->get('review')) {
                $r->set('review', $review);
                $r->save();
            }
            $summary = summary::add_rating($courseid, $r);
        }
        self::update_course_rating_in_custom_field($summary);
        // TODO trigger event.

        return $r;
    }

    public static function delete_rating(int $ratingid, ?string $reason = null): ?rating {
        global $DB;
        if (!$rating = rating::get_record(['id' => $ratingid])) {
            return null;
        }
        $record = $rating->to_record();
        $rating->delete();
        if ($context = \context_course::instance($record->courseid, IGNORE_MISSING)) {
            // Sometimes it might be called after course is deleted.
            get_file_storage()->delete_area_files($context->id, 'tool_courserating', 'review', $record->id);
        }
        $DB->delete_records(flag::TABLE, ['ratingid' => $record->id]);
        $summary = summary::delete_rating($record);
        self::update_course_rating_in_custom_field($summary);

        // TODO trigger event, record reason. Send notification

        return $rating;
    }

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

    protected static function prepare_review(?rating $rating, \stdClass $data): string {
        if ($rating && !rating::review_is_empty($data->review_editor['text'] ?? '')) {
            $context = \context_course::instance($rating->get('courseid'));
            $data = file_postupdate_standard_editor($data, 'review', helper::review_editor_options($context), $context,
                'tool_courserating', 'review', $rating->get('id'));
            if ($data->reviewformat != FORMAT_HTML) {
                // We always store reviews as HTML, we don't even store the reviewformat field.
                // Do not apply filters now, they will be applied during display.
                return format_text($data->review, $data->reviewformat, ['filter' => false, 'context' => $context]);
            }
            return $data->review;
        } else if (!rating::review_is_empty($data->review ?? '')) {
            return $data->review;
        } else {
            return '';
        }
    }

    public static function prepare_rating_for_form(int $courseid): array {
        global $USER;
        if ($rating = rating::get_record(['userid' => $USER->id, 'courseid' => $courseid])) {
            $data = $rating->to_record();
            $data->reviewformat = FORMAT_HTML;
            $context = \context_course::instance($courseid);
            $data = file_prepare_standard_editor($data, 'review', helper::review_editor_options($context), $context,
                'tool_courserating', 'review', $data->id);
            return [
                'rating' => $data->rating,
                'review_editor' => $data->review_editor,
            ];
        } else {
            return [
                'review' => ['text' => '', 'format' => FORMAT_HTML],
            ];
        }
    }

    public static function flag_review(int $ratingid): ?flag {
        global $USER;
        $flag = flag::get_records(['ratingid' => $ratingid, 'userid' => $USER->id]);
        if ($flag) {
            return null;
        }
        $flag = new flag(0, (object)['userid' => $USER->id, 'ratingid' => $ratingid]);
        $flag->save();
        // TODO event.
        return $flag;
    }

    public static function revoke_review_flag(int $ratingid): ?flag {
        global $USER;
        $flags = flag::get_records(['ratingid' => $ratingid, 'userid' => $USER->id]);
        $flag = reset($flags);
        if (!$flag) {
            return null;
        }
        $flag->delete();
        // TODO event.
        return $flag;
    }

    public static function get_flag_inplace_editable(int $ratingid, ?bool $hasflag = null): inplace_editable {
        global $USER;

        if ($hasflag === null) {
            $hasflag = flag::count_records(['ratingid' => $ratingid, 'userid' => $USER->id]) > 0;
        }
        $displayvalue = $hasflag ? get_string('revokeratingflag', 'tool_courserating') :
            get_string('flagrating', 'tool_courserating');
        $edithint = $displayvalue;
        $r = new inplace_editable('tool_courserating', 'flag', $ratingid, true, $displayvalue,
            $hasflag ? 1 : 0, $edithint);
        $r->set_type_toggle(array(0, 1));
        return $r;
    }

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
        $join = 'from mdl_course c
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
            $join .= ' left join mdl_customfield_field fr on fr.shortname = :field2
            left join mdl_customfield_data dr on dr.fieldid = fr.id and dr.instanceid = c.id';
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
     * @param int $courseratingmode the actual rating mode for this course
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

    public static function delete_all_data_for_course(int $courseid) {
        global $DB;
        $DB->execute('DELETE from {'.flag::TABLE.'} WHERE ratingid IN (SELECT id FROM {'.
            rating::TABLE.'} WHERE courseid = ?)', [$courseid]);
        $DB->delete_records(summary::TABLE, ['courseid' => $courseid]);
        $DB->delete_records(rating::TABLE, ['courseid' => $courseid]);
    }
}
