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

use core_customfield\data_controller;
use core_customfield\field_controller;
use tool_courserating\external\stars_exporter;

/**
 * Additional helper functions
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Temporary function
     *
     * @return void
     */
    private function wordings() {
        // @codingStandardsIgnoreStart
        // Udemy.
        'You\'ve finished the last lesson in this course! Would you like to leave a review?';
        [
            1 => 'Awful, not what I was expecting at all',
            1.5 => 'Awful / Poor',
            2 => 'Poor, pretty disappointed',
            2.5 => 'Poor / Average',
            3 => 'Average, could be better',
            3.5 => 'Average / Good',
            4 => 'Good, what I expected',
            4.5 => 'Good / Amazing',
            5 => 'Amazing, above expectations',
        ];
        'Tell us about your own personal experience taking this course. Was it a good match for you?';

        'Report'; 'Report abuse';
        'Flagged content is reviewed by Udemy staff to determine whether it violates Terms of Service or Community Guidelines. If you have a question or technical issue, please contact our Support team here.';
        'Issue type';
        [
            'Inappropriate Course Content',
            'Inappropriate Behavior',
            'Udemy Policy Violation',
            'Spammy Content',
            'Other',
        ];
        'Issue details';
        // @codingStandardsIgnoreEnd
    }

    /**
     * Checks if we are on a main course page
     *
     * @return int
     */
    public static function is_course_page(): int {
        global $PAGE, $CFG;
        if ($PAGE->course && $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/view.php') {
            return $PAGE->course->id;
        }
        return 0;
    }

    /**
     * Checks if we are on a main page of single-activity course
     *
     * @return int
     */
    public static function is_single_activity_course_page(): int {
        global $PAGE, $CFG;
        if ($PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->course->format === 'singleactivity' &&
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/mod/' . $PAGE->cm->modname . '/view.php') {
            return $PAGE->course->id;
        }
        return 0;
    }

    /**
     * Checks if we are on a course edit page
     *
     * @return int
     */
    public static function is_course_edit_page(): int {
        global $PAGE, $CFG;
        if ($PAGE->course && $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/edit.php') {
            return $PAGE->course->id;
        }
        return 0;
    }

    /**
     * Are course ratings enabled (or could be enabled) in any courses? Do we need to have a course rating field
     *
     * @return bool
     */
    public static function course_ratings_enabled_anywhere(): bool {
        if (self::get_setting(constants::SETTING_RATINGMODE) == constants::RATEBY_NOONE &&
                !self::get_setting(constants::SETTING_PERCOURSE)) {
            return false;
        }
        return true;
    }

    /**
     * Options for the review editor form element
     *
     * @param \context $context
     * @return array
     */
    public static function review_editor_options(\context $context) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/formslib.php');
        return [
            'subdirs' => 0,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'changeformat' => 0,
            'context' => $context,
        ];
    }

    /**
     * Retrieve and clean plugin setting
     *
     * @param string $name
     * @return bool|mixed|object|string
     */
    public static function get_setting(string $name) {
        $value = get_config('tool_courserating', $name);
        static $defaults = [
            constants::SETTING_STARCOLOR => constants::SETTING_STARCOLOR_DEFAULT,
            constants::SETTING_RATINGCOLOR => constants::SETTING_RATINGCOLOR_DEFAULT,
            constants::SETTING_DISPLAYEMPTY => false,
            constants::SETTING_PERCOURSE => false,
            constants::SETTING_RATINGMODE => constants::RATEBY_ANYTIME,
            constants::SETTING_USEHTML => false,
        ];
        if (!isset($value) && array_key_exists($name, $defaults)) {
            // Can only happen if there is unfinished upgrade.
            return $defaults[$name];
        }

        if ($name === constants::SETTING_DISPLAYEMPTY || $name === constants::SETTING_PERCOURSE
                || $name === constants::SETTING_USEHTML) {
            return !empty($value);
        }
        if ($name === constants::SETTING_STARCOLOR || $name === constants::SETTING_RATINGCOLOR) {
            $color = strtolower($value ?? '');
            return (preg_match('/^#[a-f0-9]{6}$/', $color)) ? $color : $defaults[$name];
        }
        if ($name === constants::SETTING_RATINGMODE) {
            static $available = [constants::RATEBY_NOONE, constants::RATEBY_ANYTIME, constants::RATEBY_COMPLETED];
            return in_array($value, $available) ? $value : $defaults[$name];
        }
        return $value;
    }

    /**
     * CSS for the stars colors to be added to the page
     *
     * @return string
     */
    public static function get_rating_colour_css() {
        return '.tool_courserating-stars { color: '.self::get_setting(constants::SETTING_STARCOLOR).'; }'."\n".
            '.tool_courserating-ratingcolor { color: '.self::get_setting(constants::SETTING_RATINGCOLOR).';}'."\n".
            '.tool_courserating-norating .tool_courserating-stars { color: '.constants::COLOR_GRAY.';}'."\n".
            '.tool_courserating-barcolor { background-color: '.self::get_setting(constants::SETTING_STARCOLOR).';}'."\n";
    }

    /**
     * Finds a field by its shortname
     *
     * @param string $shortname
     * @return field_controller|null
     */
    protected static function find_custom_field_by_shortname(string $shortname): ?field_controller {
        $handler = \core_course\customfield\course_handler::create();
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
     * Create a custom course field if it does not exist
     *
     * @param string $shortname
     * @param string $type i.e. 'textarea', 'select', 'text
     * @param null|\lang_string $displayname
     * @param array $config additional field configuration, for example, options for 'select' element
     * @param string $description
     * @return field_controller|null
     */
    protected static function create_custom_field(string $shortname, string $type = 'text', ?\lang_string $displayname = null,
                                               array $config = [], string $description = ''): ?field_controller {
        $handler = \core_course\customfield\course_handler::create();
        $categories = $handler->get_categories_with_fields();
        if (empty($categories)) {
            $categoryid = $handler->create_category();
            $category = \core_customfield\category_controller::create($categoryid);
        } else {
            $category = reset($categories);
        }

        $config += [
            'defaultvalue' => '',
            'defaultvalueformat' => 1,
            'visibility' => \core_course\customfield\course_handler::VISIBLETOALL,
            'required' => 0,
            'uniquevalues' => 0,
            'locked' => 0,
        ];
        $record = (object)[
            'type' => $type,
            'shortname' => $shortname,
            'name' => $displayname ? (string)$displayname : $shortname,
            'descriptionformat' => FORMAT_HTML,
            'description' => $description,
            'configdata' => json_encode($config),
        ];

        try {
            $field = \core_customfield\field_controller::create(0, $record, $category);
        } catch (\moodle_exception $e) {
            return null;
        }

        $handler->save_field_configuration($field, $record);

        // Fetch the field again because the categories cache was rebuilt.
        return self::find_custom_field_by_shortname($shortname);
    }

    /**
     * Retrieve course custom field responsible for storing course ratings, create if not found
     *
     * @return field_controller|null
     */
    public static function get_course_rating_field(): ?field_controller {
        $shortname = constants::CFIELD_RATING;
        $field = self::find_custom_field_by_shortname($shortname);

        if (!self::course_ratings_enabled_anywhere()) {
            if ($field) {
                $field->get_handler()->delete_field_configuration($field);
            }
            return null;
        }

        return $field ?? self::create_custom_field($shortname,
            'textarea',
            new \lang_string('ratinglabel', 'tool_courserating'),
            ['locked' => 1],
            get_string('cfielddescription', 'tool_courserating'));
    }

    /**
     * Retrieve course custom field responsible for configuring per-course course rating mode, create if needed
     *
     * @return field_controller|null
     */
    public static function get_course_rating_mode_field(): ?field_controller {
        $shortname = constants::CFIELD_RATINGMODE;
        $field = self::find_custom_field_by_shortname($shortname);
        if (!self::get_setting(constants::SETTING_PERCOURSE)) {
            if ($field) {
                $field->get_handler()->delete_field_configuration($field);
            }
            return null;
        }

        $options = constants::rated_courses_options();
        $description = get_string('ratebydefault', 'tool_courserating',
            $options[self::get_setting(constants::SETTING_RATINGMODE)]);
        $field = $field ?? self::create_custom_field($shortname,
            'select',
            new \lang_string('ratingmode', 'tool_courserating'),
            [
                'visibility' => \core_course\customfield\course_handler::NOTVISIBLE,
                'options' => join("\n", $options),
            ],
            $description);
        if ($field && $field->get('description') !== $description) {
            $field->set('description', $description);
            $field->save();
        }
        return $field;
    }

    /**
     * Delete all course custom fields created by this plugin (on uninstall)
     *
     * @return void
     */
    public static function delete_all_custom_fields() {
        $shortname = constants::CFIELD_RATINGMODE;
        if ($field = self::find_custom_field_by_shortname($shortname)) {
            $field->get_handler()->delete_field_configuration($field);
        }
        $shortname = constants::CFIELD_RATING;
        if ($field = self::find_custom_field_by_shortname($shortname)) {
            $field->get_handler()->delete_field_configuration($field);
        }
    }

    /**
     * Retireve data stored in a course custom field
     *
     * @param int $courseid
     * @param string $shortname
     * @return data_controller|null
     */
    protected static function get_custom_field_data(int $courseid, string $shortname): ?data_controller {
        if ($f = self::find_custom_field_by_shortname($shortname)) {
            $fields = \core_customfield\api::get_instance_fields_data([$f->get('id') => $f], $courseid);
            foreach ($fields as $data) {
                if (!$data->get('id')) {
                    $data->set('contextid', \context_course::instance($courseid)->id);
                }
                return $data;
            }
        }
        return null;
    }

    /**
     * Retrieve data stored in a course rating course custom field
     *
     * @param int $courseid
     * @return data_controller|null
     */
    public static function get_course_rating_data_in_cfield(int $courseid): ?data_controller {
        return self::get_custom_field_data($courseid, constants::CFIELD_RATING);
    }

    /**
     * Retireve data stored in a rating mode custom course field
     *
     * @param int $courseid
     * @return data_controller|null
     */
    public static function get_course_rating_enabled_data_in_cfield(int $courseid): ?data_controller {
        return self::get_custom_field_data($courseid, constants::CFIELD_RATINGMODE);
    }

    /**
     * Calculate the rating mode for a specific course
     *
     * @param int $courseid
     * @return int
     */
    public static function get_course_rating_mode(int $courseid): int {
        $mode = self::get_setting(constants::SETTING_RATINGMODE);
        if (self::get_setting(constants::SETTING_PERCOURSE)) {
            if ($data = self::get_course_rating_enabled_data_in_cfield($courseid)) {
                $modecourse = (int)$data->get('intvalue');
                if (array_key_exists($modecourse, constants::rated_courses_options())) {
                    // Value is overridden for this course.
                    return $modecourse;
                }
            }
        }
        return $mode;
    }

    /**
     * Formatter for average rating
     *
     * @param float|null $avgrating
     * @param string $default
     * @return string
     */
    public static function format_avgrating(?float $avgrating, string $default = ''): string {
        return $avgrating ? sprintf("%.1f", $avgrating) : $default;
    }

    /**
     * Formatter for stars
     *
     * @param float|null $avgrating
     * @param \renderer_base|null $output
     * @return string
     */
    public static function stars(?float $avgrating, ?\renderer_base $output = null): string {
        global $PAGE;
        if (!$avgrating) {
            return '';
        }
        $output = $output ?? $PAGE->get_renderer('tool_courserating');
        return $output->render_from_template('tool_courserating/stars',
            (new stars_exporter($avgrating))->export($output));
    }

    /**
     * Formatter for date
     * @param int $value
     * @return string
     */
    public static function format_date($value): string {
        return $value ? userdate($value, get_string('strftimedatetimeshort', 'core_langconfig')) : '';
    }

    /**
     * Formatter for review
     *
     * @param string $value
     * @param \stdClass $row
     * @return string
     */
    public static function format_review($value, \stdClass $row): string {
        if (empty($row->id) || !strlen($row->review ?? '')) {
            return '';
        }
        $context = !empty($row->courseid) ? \context_course::instance($row->courseid) : \context_system::instance();
        $formatparams = [
            'options' => [],
            'striplinks' => true,
            'component' => 'tool_courserating',
            'filearea' => 'review',
            'itemid' => $row->id,
            'context' => $context,
        ];
        if (self::get_setting(constants::SETTING_USEHTML)) {
            list($text, $format) = external_format_text($row->review, FORMAT_HTML, $formatparams['context'],
                $formatparams['component'], $formatparams['filearea'], $formatparams['itemid'], $formatparams['options']);
            return $text;
        } else {
            return format_text(clean_param($row->review, PARAM_TEXT), FORMAT_MOODLE, ['context' => $context]);
        }
    }

    /**
     * Actions column
     *
     * @param int $id
     * @param \stdClass $row
     * @return string
     */
    public static function format_actions($id, $row): string {
        if (!$id || !permission::can_delete_rating($id, $row->courseid)) {
            return '';
        }
        return "<span data-for=\"tool_courserating-rbcell\" data-ratingid=\"$id\">".
            "<a href=\"#\" data-action=\"tool_courserating-delete-rating\" data-ratingid=\"$id\">".
            get_string('deleterating', 'tool_courserating')."</a></span>";
    }

    /**
     * Format individual student rating in the course report
     *
     * @param int $rating
     * @param \stdClass $row
     * @return string
     */
    public static function format_rating_in_course_report($rating, $row): string {
        if (!$rating) {
            return '';
        }
        return \html_writer::span(
            self::stars((float)$rating).
            \html_writer::span($rating, 'tool_courserating-ratingcolor ml-2'),
            'tool_courserating-reportrating');
    }

    /**
     * Format flags count in course report
     *
     * @param int|null $nofflags
     * @param \stdClass $row
     * @return string
     */
    public static function format_flags_in_course_report(?int $nofflags, \stdClass $row): string {
        return $nofflags ? "<span class=\"badge badge-warning\">$nofflags</span>" : '';
    }
}
