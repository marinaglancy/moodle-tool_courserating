<?php

namespace tool_courserating\local\models;

class rating extends \core\persistent {

    /** @var string Table name */
    public const TABLE = 'tool_courserating_rating';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() : array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'rating' => [
                'type' => PARAM_INT,
            ],
            'review' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'hasreview' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Context of this rating
     *
     * @return \context_course
     */
    public function get_context(): \context_course {
        return \context_course::instance($this->get('courseid'));
    }

    public static function review_is_empty(string $review): bool {
        $review = clean_text($review);
        $tagstostrip = ['p', 'span', 'font', 'br', 'div'];
        foreach ($tagstostrip as $tag) {
            $review = preg_replace("/<\\/?" . $tag . "\b(.|\\s)*?>/", '', $review);
        }
        return strlen(trim($review)) == 0;
    }

    /**
     * Magic method to set review
     *
     * @param string $value
     */
    protected function set_review($value) {
        $this->raw_set('review', $value);
        $this->raw_set('hasreview', (int)(!self::review_is_empty(($value))));
    }
}