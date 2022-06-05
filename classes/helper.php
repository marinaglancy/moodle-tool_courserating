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
        if ($PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/view.php' &&
            ($courseid = $PAGE->url->get_param('id'))) {
            return $courseid;
        }
        return 0;
    }

    public static function is_course_listing_page(): bool {
        global $PAGE, $CFG;
        return $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/index.php' ||
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/course/search.php' ||
            $PAGE->url->out_omit_querystring() === $CFG->wwwroot . '/search/index.php';
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
}