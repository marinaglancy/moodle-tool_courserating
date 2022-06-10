<?php

namespace tool_courserating\local\models;

use tool_courserating\constants;
use tool_courserating\helper;

class summary extends \core\persistent {

    /** @var string Table name */
    public const TABLE = 'tool_courserating_summary';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() : array {
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
                'default' => 0,
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

    public static function get_for_course(int $courseid) {
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

    protected static function cntkey(int $i) {
        $i = min(max(1, $i), 10);
        return 'cnt' . str_pad($i, 2, "0", STR_PAD_LEFT);
    }

    public static function add_rating(int $courseid, int $rating, bool $hasreview): self {
        if (!$record = self::get_record(['courseid' => $courseid])) {
            $record = new self(0, (object)['courseid' => $courseid]);
        }
        $record->set('cntall', $record->get('cntall') + 1);
        $record->set('sumrating', $record->get('sumrating') + $rating);
        $record->set('avgrating', 1.0 * $record->get('sumrating') / $record->get('cntall'));
        if ($hasreview) {
            $record->set('cntreviews', $record->get('cntreviews') + 1);
        }
        $record->set(self::cntkey($rating), $record->get(self::cntkey($rating)) + 1);
        $record->save();
        return $record;
    }

    public static function update_rating(int $courseid, int $rating, bool $hasreview, int $ratingold, bool $hasreviewold): ?self {
        if (!($record = self::get_record(['courseid' => $courseid])) || !$record->get('cntall') || !$record->get(self::cntkey($ratingold))) {
            return self::get_for_course($courseid)->recalculate();
        }
        if ($rating == $ratingold && $hasreview == $hasreviewold) {
            // Rating did not change.
            return null;
        }
        if ($hasreview != $hasreviewold) {
            $record->set('cntreviews', $record->get('cntreviews') + ($hasreview ? 1 : -1));
        }
        if ($rating != $ratingold) {
            $record->set('sumrating', $record->get('sumrating') + $rating - $ratingold);
            $record->set(self::cntkey($ratingold), $record->get(self::cntkey($ratingold)) - 1);
            $record->set(self::cntkey($rating), (int)$record->get(self::cntkey($rating)) + 1);
            $record->set('avgrating', 1.0 * $record->get('sumrating') / $record->get('cntall'));
        }
        $record->save();
        return $record;
    }

    public function reset_all_counters() {
        foreach (['cntall', 'avgrating', 'sumrating', 'cntreviews'] as $key) {
            $this->set($key, 0);
        }
        for ($i = 1; $i <= 10; $i++) {
            $this->set(self::cntkey($i), 0);
        }
    }

    public function recalculate(): ?self {
        global $DB;
        if ($this->get('ratingmode') == constants::RATEBY_NOONE) {
            $this->reset_all_counters();
            $this->save();
            return $this;
        }

        $sqllen = $DB->sql_length('review');
        $sql = 'SELECT COUNT(id) AS cntall,
               SUM(rating) AS sumrating,
               SUM(CASE WHEN ('.$sqllen.') > 0 THEN 1 ELSE 0 END) as cntreviews,
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
                $key[] = self::cntkey($i);
            }
            foreach ($keys as $key) {
                $this->set($key, $result->$key ?? 0);
            }
            $this->set('avgrating', 1.0 * $this->get('sumrating') / $this->get('cntall'));
        }
        $this->save();
        return $this;
    }

    public static function delete_rating(int $courseid, int $ratingold, bool $hasreviewold): ?self {
        if (!($record = self::get_record(['courseid' => $courseid])) || !$record->get('cntall') || !$record->get(self::cntkey($ratingold))) {
            return null;
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