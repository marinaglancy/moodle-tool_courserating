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
 * Event rating deleted
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_deleted extends \core\event\base {

    /**
     * Init
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = rating::TABLE;
    }

    /**
     * Event name
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event:rating_deleted', 'tool_courserating');
    }

    /**
     * Event description
     *
     * @return string
     */
    public function get_description(): string {
        return "User {$this->userid} has deleted course rating made by the user with id {$this->relateduserid}. ".
            "The old rating was ".$this->other['oldrating']." and the review had been flagged ".
            $this->other['flagcount']." times. Reason provided: ".s($this->other['reason']);
    }

    /**
     * Shortcut to create an instance of event
     *
     * @param \stdClass $recordold
     * @param int $flagcount
     * @param string $reason
     * @return self
     */
    public static function create_from_rating(\stdClass $recordold, int $flagcount, string $reason): self {
        /** @var self $event */
        $event = static::create([
            'objectid' => $recordold->id,
            'courseid' => $recordold->courseid,
            'context' => \context_course::instance($recordold->courseid, IGNORE_MISSING) ?? \context_system::instance(),
            'relateduserid' => $recordold->userid,
            'other' => ['oldrating' => $recordold->rating, 'flagcount' => $flagcount, 'reason' => $reason],
        ]);
        $event->add_record_snapshot($event->data['objecttable'], $recordold);
        return $event;
    }
}
