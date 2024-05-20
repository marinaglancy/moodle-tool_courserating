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

use tool_courserating\constants;
use tool_courserating\helper;

/**
 * Model for summary table
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary extends \core\persistent {

    /** @var string Table name */
    public const TABLE = 'tool_courserating_summary';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        $props = [
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'cntall' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'avgrating' => [
                'type' => PARAM_FLOAT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'sumrating' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'cntreviews' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'ratingmode' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
        for ($i = 1; $i <= 10; $i++) {
            $props[self::cntkey($i)] = [
                'type' => PARAM_INT,
                'default' => 0,
            ];
        }
        return $props;
    }

    /**
     * Retrieve summary for the specified course, insert in DB if does not exist
     *
     * @param int $courseid
     * @return summary
     */
    public static function get_for_course(int $courseid): summary {
        if ($summary = self::get_record(['courseid' => $courseid])) {
            return $summary;
        } else {
            $summary = new static(0, (object)[
                'courseid' => $courseid,
                'ratingmode' => helper::get_course_rating_mode($courseid),
            ]);
            $summary->save();
            return $summary;
        }
    }

    /**
     * Field name for the counter of rating $i
     *
     * @param int $i
     * @return string
     */
    protected static function cntkey(int $i) {
        $i = min(max(1, $i), 10);
        return 'cnt' . str_pad($i, 2, "0", STR_PAD_LEFT);
    }

    /**
     * Update summary after a rating was added
     *
     * @param int $courseid
     * @param rating $rating
     * @return static
     */
    public static function add_rating(int $courseid, rating $rating): self {
        if (!$record = self::get_record(['courseid' => $courseid])) {
            $record = new self(0, (object)['courseid' => $courseid]);
        }
        $record->set('cntall', $record->get('cntall') + 1);
        $record->set('sumrating', $record->get('sumrating') + $rating->get('rating'));
        $record->set('avgrating', 1.0 * $record->get('sumrating') / $record->get('cntall'));
        if ($rating->get('hasreview')) {
            $record->set('cntreviews', $record->get('cntreviews') + 1);
        }
        $record->set(self::cntkey($rating->get('rating')), $record->get(self::cntkey($rating->get('rating'))) + 1);
        $record->save();
        return $record;
    }

    /**
     * Update summary after a rating was updated
     *
     * @param int $courseid
     * @param rating $rating
     * @param \stdClass $oldrecord
     * @return static|null
     */
    public static function update_rating(int $courseid, rating $rating, \stdClass $oldrecord): ?self {
        $ratingold = $oldrecord->rating;
        $summary = self::get_for_course($courseid);
        if (!$summary->get('cntall') || !$summary->get(self::cntkey($ratingold))) {
            // Sanity check, did not pass, recalculate all.
            return $summary->recalculate();
        }
        if ($rating == $ratingold && $rating->get('hasreview') == $oldrecord->hasreview) {
            // Rating did not change.
            return null;
        }
        $summary->set('cntreviews', $summary->get('cntreviews') + $rating->get('hasreview') - $oldrecord->hasreview);
        if ($rating != $ratingold) {
            $summary->set('sumrating', $summary->get('sumrating') + $rating->get('rating') - $ratingold);
            $summary->set(self::cntkey($ratingold), $summary->get(self::cntkey($ratingold)) - 1);
            $summary->set(self::cntkey($rating->get('rating')), (int)$summary->get(self::cntkey($rating->get('rating'))) + 1);
            $summary->set('avgrating', 1.0 * $summary->get('sumrating') / $summary->get('cntall'));
        }
        $summary->save();
        return $summary;
    }

    /**
     * Update summary when it has to be empty - reset all counter fields
     *
     * @return void
     * @throws \coding_exception
     */
    public function reset_all_counters() {
        foreach (['cntall', 'sumrating', 'cntreviews'] as $key) {
            $this->set($key, 0);
        }
        $this->set('avgrating', null);
        for ($i = 1; $i <= 10; $i++) {
            $this->set(self::cntkey($i), 0);
        }
    }

    /**
     * Recalculate summary for the specific course
     *
     * @return $this|null
     */
    public function recalculate(): ?self {
        global $DB;
        if ($this->get('ratingmode') == constants::RATEBY_NOONE) {
            $this->reset_all_counters();
            $this->save();
            return $this;
        }

        $isempty = $DB->sql_isempty('', 'review', false, true);
        $sql = 'SELECT COUNT(id) AS cntall,
               SUM(rating) AS sumrating,
               SUM(CASE WHEN '.$isempty.' THEN 0 ELSE 1 END) as cntreviews,
               SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as cnt01,
               SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as cnt02,
               SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as cnt03,
               SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as cnt04,
               SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as cnt05,
               SUM(CASE WHEN rating = 6 THEN 1 ELSE 0 END) as cnt06,
               SUM(CASE WHEN rating = 7 THEN 1 ELSE 0 END) as cnt07,
               SUM(CASE WHEN rating = 8 THEN 1 ELSE 0 END) as cnt08,
               SUM(CASE WHEN rating = 9 THEN 1 ELSE 0 END) as cnt09,
               SUM(CASE WHEN rating = 10 THEN 1 ELSE 0 END) as cnt10
            FROM {tool_courserating_rating} r
            WHERE r.courseid = :courseid
        ';
        $params = ['courseid' => $this->get('courseid')];
        $result = $DB->get_record_sql($sql, $params);

        if (!$result->cntall) {
            $this->reset_all_counters();
        } else {
            $keys = ['cntall', 'sumrating', 'cntreviews'];
            for ($i = 1; $i <= 10; $i++) {
                $keys[] = self::cntkey($i);
            }
            foreach ($keys as $key) {
                $this->set($key, $result->$key ?? 0);
            }
            $this->set('avgrating', 1.0 * $this->get('sumrating') / $this->get('cntall'));
        }
        $this->save();
        return $this;
    }

    /**
     * Recalculate summary after an individual rating was deleted
     *
     * @param \stdClass $rating snapshot of the rating record before it was deleted
     * @return static|null
     */
    public static function delete_rating(\stdClass $rating): ?self {
        $ratingold = $rating->rating;
        $hasreviewold = $rating->hasreview;
        $record = self::get_for_course($rating->courseid);
        if ($record->get('cntall') <= 1 || !$record->get(self::cntkey($ratingold))) {
            // Sanity check did not pass or no more ratings left, recalculate all.
            return $record->recalculate();
        }
        if ($hasreviewold && $record->get('cntreviews') > 0) {
            $record->set('cntreviews', $record->get('cntreviews') - 1);
        }
        $record->set('cntall', $record->get('cntall') - 1);
        $record->set('sumrating', $record->get('sumrating') - $ratingold);
        $record->set(self::cntkey($ratingold), $record->get(self::cntkey($ratingold)) - 1);
        $record->set('avgrating', 1.0 * $record->get('sumrating') / $record->get('cntall'));
        $record->save();
        return $record;
    }
}
