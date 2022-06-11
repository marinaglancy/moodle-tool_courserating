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

use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;

/**
 * Event flag deleted
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flag_deleted extends \core\event\base {

    /**
     * Init
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = flag::TABLE;
    }

    /**
     * Event name
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event:flag_deleted', 'tool_courserating');
    }

    /**
     * Event description
     *
     * @return string
     */
    public function get_description(): string {
        return "User {$this->userid} has revoked their flag on the course rating made by the user with id {$this->relateduserid}";
    }

    /**
     * Shortcut to create an instance of event
     *
     * @param \stdClass $object
     * @param rating $rating
     * @return self
     */
    public static function create_from_flag(\stdClass $object, rating $rating): self {
        /** @var self $event */
        $event = static::create([
            'objectid' => $object->id,
            'courseid' => $rating->get('courseid'),
            'relateduserid' => $rating->get('userid'),
            'context' => \context_course::instance($rating->get('courseid')),
            'other' => ['ratingid' => $object->id],
        ]);
        $event->add_record_snapshot($event->data['objecttable'], $object);
        $event->add_record_snapshot(rating::TABLE, $rating->to_record());
        return $event;
    }
}
