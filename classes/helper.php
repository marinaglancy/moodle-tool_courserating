<?php

namespace tool_courserating;

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
}