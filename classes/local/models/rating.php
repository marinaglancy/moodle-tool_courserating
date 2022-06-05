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
}