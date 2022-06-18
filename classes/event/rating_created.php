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

namespace tool_courserating\event;

use tool_courserating\local\models\rating;

/**
 * Event rating created
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_created extends \core\event\base {

    /**
     * Init
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = rating::TABLE;
    }

    /**
     * Event name
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event:rating_created', 'tool_courserating');
    }

    /**
     * Event description
     *
     * @return string
     */
    public function get_description(): string {
        return "User {$this->relateduserid} has rated the course with ".$this->other['rating']." stars";
    }

    /**
     * Shortcut to create an instance of event
     *
     * @param rating $object
     * @return self
     */
    public static function create_from_rating(rating $object): self {
        /** @var self $event */
        $event = static::create([
            'objectid' => $object->get('id'),
            'courseid' => $object->get('courseid'),
            'relateduserid' => $object->get('userid'),
            'context' => \context_course::instance($object->get('courseid')),
            'other' => ['rating' => $object->get('rating')],
        ]);
        $event->add_record_snapshot($event->data['objecttable'], $object->to_record());
        return $event;
    }
}
