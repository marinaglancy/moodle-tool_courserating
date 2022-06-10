<?php

namespace tool_courserating;

use core_customfield\data_controller;
use core_customfield\field_controller;

class helper {
    function wordings() {
        // Udemy
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
    }

    public static function is_course_page(): int {
        global $PAGE, $CFG;
        if ($PAGE->course && $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/view.php') {
            return $PAGE->course->id;
        }
        return 0;
    }

    public static function is_single_activity_course_page(): int {
        global $PAGE, $CFG;
        if ($PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->course->format === 'singleactivity' &&
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/mod/' . $PAGE->cm->modname . '/view.php') {
            return $PAGE->course->id;
        }
        return 0;
    }

    public static function is_course_listing_page(): bool {
        global $PAGE, $CFG;
        return $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/index.php' ||
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/search.php' ||
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/search/index.php';
    }

    public static function course_ratings_enabled_anywhere(): bool {
        if (self::get_setting(constants::SETTING_RATEDCOURSES) == constants::RATEBY_NOONE &&
                !self::get_setting(constants::SETTING_PERCOURSE)) {
            return false;
        }
        return true;
    }

    public static function can_add_rating(int $courseid): bool {
        global $DB, $USER;
        $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', ['userid' => $USER->id, 'courseid' => $courseid]);
        return (bool)$lastaccess;
    }

    public static function review_editor_options(\context $context) {
        global $CFG;
        return [
            'subdirs' => 0,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'changeformat' => 0,
            'context' => $context,
        ];
    }

    public static function get_setting(string $name) {
        $value = get_config('tool_courserating', $name);
        static $defaults = [
            constants::SETTING_STARCOLOR => constants::SETTING_STARCOLOR_DEFAULT,
            constants::SETTING_RATINGCOLOR => constants::SETTING_RATINGCOLOR_DEFAULT,
            constants::SETTING_DISPLAYEMPTY => false,
            constants::SETTING_PERCOURSE => false,
        ];
        if (!isset($value) && array_key_exists($name, $defaults)) {
            // Can only happen if there is unfinished upgrade.
            return $defaults[$name];
        }

        if ($name === constants::SETTING_DISPLAYEMPTY || $name === constants::SETTING_PERCOURSE) {
            return !empty($value);
        }
        if ($name === constants::SETTING_STARCOLOR || $name === constants::SETTING_RATINGCOLOR) {
            $color = strtolower($value ?? '');
            return (preg_match('/^#[a-f0-9]{6}$/', $color)) ? $color : $defaults[$name];
        }
        if ($name === constants::SETTING_RATEDCOURSES) {
            static $available = [constants::RATEBY_NOONE, constants::RATEBY_ANYTIME, constants::RATEBY_COMPLETED];
            return in_array($value, $available) ? $value : $defaults[$name];
        }
        return $value;
    }

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
    protected static function find_custom_field_by_shortname(string $shortname) : ?field_controller {
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
     * @param null|string $displayname
     * @param array $config additional field configuration, for example, options for 'select' element
     * @param string $description
     * @return field_controller|null
     */
    protected static function create_custom_field(string $shortname, string $type = 'text', ?\lang_string $displayname = null,
                                               array $config = [], string $description = '') : ?field_controller {
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

    public static function get_course_rating_field(): ?field_controller {
        $shortname = 'tool_courserating';
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

    public static function get_course_rating_enabled_field(): ?field_controller {
        $shortname = 'tool_courserating_'.constants::SETTING_PERCOURSE;
        $field = self::find_custom_field_by_shortname($shortname);
        if (!self::get_setting(constants::SETTING_PERCOURSE)) {
            if ($field) {
                $field->get_handler()->delete_field_configuration($field);
            }
            return null;
        }

        $options = constants::rated_courses_options();
        $description = get_string('ratebydefault', 'tool_courserating',
            $options[self::get_setting(constants::SETTING_RATEDCOURSES)]);
        $field = $field ?? self::create_custom_field($shortname,
            'select',
            new \lang_string('ratedcourses', 'tool_courserating'),
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

    public static function get_course_rating_data_in_cfield(int $courseid): ?data_controller {
        return self::get_custom_field_data($courseid, 'tool_courserating');
    }

    public static function get_course_rating_enabled_data_in_cfield(int $courseid): ?data_controller {
        return self::get_custom_field_data($courseid, 'tool_courserating_'.constants::SETTING_PERCOURSE);
    }

    public static function get_course_rating_mode(int $courseid): int {
        $mode = self::get_setting(constants::SETTING_RATEDCOURSES);
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
}