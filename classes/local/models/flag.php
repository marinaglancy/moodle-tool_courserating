<?php

namespace tool_courserating\local\models;

class flag extends \core\persistent {

    /** @var string Table name */
    public const TABLE = 'tool_courserating_flag';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() : array {
        return [
            'ratingid' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'reasoncode' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'reason' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
        ];
    }

}