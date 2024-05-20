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

namespace tool_courserating\local\models;

/**
 * Model for rating table
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating extends \core\persistent {

    /** @var string Table name */
    public const TABLE = 'tool_courserating_rating';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
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

    /**
     * Checks if review is actually empty (i.e. empty <p> or just newlines is not considered a content)
     *
     * @param string $review
     * @return bool
     */
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
