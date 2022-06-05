<?php

namespace tool_courserating;

use core\output\inplace_editable;
use core_customfield\field_controller;
use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

class api {

    public static function set_rating(int $courseid, \stdClass $data): rating {
        global $USER;
        // TODO validate rating is within limits, trim/crop review.
        $rating = $data->rating;
        if ($r = rating::get_record(['userid' => $USER->id, 'courseid' => $courseid])) {
            $review = self::prepare_review($r, $data);
            $ratingold = $r->get('rating');
            $hasreviewold = self::review_is_empty($r->get('review'));
            $r->set('rating', $rating);
            $r->set('review', $review);
            $r->save();
            $summary = summary::update_rating($courseid, $rating, self::review_is_empty($review), $ratingold, $hasreviewold);
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
            $summary = summary::add_rating($courseid, $rating, self::review_is_empty($review));
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
        $summary = summary::delete_rating($rating->get('courseid'), $record->rating, !self::review_is_empty($record->review));
        self::update_course_rating_in_custom_field($summary);

        // TODO trigger event, record reason. Send notification

        return $rating;
    }

    protected static function update_course_rating_in_custom_field(?summary $summary) {
        global $PAGE;
        if (!$summary) {
            return;
        }
        $courseid = $summary->get('courseid');
        /** @var \tool_courserating\output\renderer $output */
        $output = $PAGE->get_renderer('tool_courserating');
        $ratingstr = $output->rating_summary_for_cfield($summary);
        $f = self::get_course_rating_field();
        $handler = \core_course\customfield\course_handler::create();
        $fields = \core_customfield\api::get_instance_fields_data([$f->get('id') => $f], $courseid);
        foreach ($fields as $data) {
            if (!$data->get('id')) {
                $data->set('contextid', $handler->get_instance_context($courseid)->id);
            }
            $data->instance_form_save((object)[
                'id' => $courseid,
                'customfield_tool_courserating_editor' => ['text' => $ratingstr, 'format' => FORMAT_HTML],
            ]);
        }
    }

    protected static function review_is_empty(string $review): bool {
        $review = clean_text($review);
        $tagstostrip = ['p', 'span', 'font', 'br', 'div'];
        foreach ($tagstostrip as $tag) {
            $review = preg_replace("/<\\/?" . $tag . "\b(.|\\s)*?>/", '', $review);
        }
        return strlen(trim($review)) == 0;
    }

    protected static function prepare_review(?rating $rating, \stdClass $data): string {
        if ($rating && !self::review_is_empty($data->review_editor['text'] ?? '')) {
            $context = \context_course::instance($rating->get('courseid'));
            $data = file_postupdate_standard_editor($data, 'review', helper::review_editor_options($context), $context,
                'tool_courserating', 'review', $rating->get('id'));
            if ($data->reviewformat != FORMAT_HTML) {
                // We always store reviews as HTML, we don't even store the reviewformat field.
                // Do not apply filters now, they will be applied during display.
                return format_text($data->review, $data->reviewformat, ['filter' => false, 'context' => $context]);
            }
            return $data->review;
        } else if (!self::review_is_empty($data->review ?? '')) {
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

    /**
     * Finds a field by its shortname
     *
     * @param string $shortname
     * @return field_controller|null
     */
    public static function find_field_by_shortname(\core_course\customfield\course_handler $handler, string $shortname) : ?field_controller {
        $categories = $handler->get_categories_with_fields();
        foreach ($categories as $category) {
            foreach ($category->get_fields() as $field) {
                if ($field->get('shortname') === $shortname) {
                    return $field;
                }
            }
        }
        return null;
    }

    /**
     * Create a field if it does not exist
     *
     * @param string $shortname
     * @param string $type currently only supported 'text' and 'textarea'
     * @param null|string $displayname
     * @param bool $visible
     * @param null|string $previewvalue
     * @param array $config additional field configuration, for example, for date - includetime
     * @return field_controller|null
     */
    public static function ensure_field_exists(string $shortname, string $type = 'text', string $displayname = '',
                                        bool $visible = false, ?string $previewvalue = null, array $config = []) : ?field_controller {
        $handler = \core_course\customfield\course_handler::create();
        if ($field = self::find_field_by_shortname($handler, $shortname)) {
            return $field;
        }

        $categories = $handler->get_categories_with_fields();
        if (empty($categories)) {
            $categoryid = $handler->create_category();
            $category = \core_customfield\category_controller::create($categoryid);
        } else {
            $category = reset($categories);
        }

        if ($type !== 'textarea') {
            $type = 'text';
        }

        try {
            $config = ['visible' => $visible, 'previewvalue' => $previewvalue] + $config;
            $record = (object)['type' => $type, 'shortname' => $shortname, 'name' => $displayname ?: $shortname,
                'descriptionformat' => FORMAT_HTML, 'configdata' => json_encode($config)];
            $field = \core_customfield\field_controller::create(0, $record, $category);
        } catch (\moodle_exception $e) {
            return null;
        }

        $handler->save_field_configuration($field, $record);

        return self::find_field_by_shortname($handler, $shortname);
    }

    public static function get_course_rating_field() {
        return self::ensure_field_exists('tool_courserating', 'textarea', get_string('ratinglabel', 'tool_courserating'), true);
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
}
