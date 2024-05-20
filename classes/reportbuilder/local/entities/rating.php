<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_courserating\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\database;
use lang_string;
use core_reportbuilder\local\report\column;
use stdClass;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use tool_courserating\helper;
use tool_courserating\permission;

/**
 * Reportbuilder entity representing table tool_courserating_rating.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating extends base {
    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['tool_courserating_rating' => 'tool_courserating_rating'];
    }

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return array_keys($this->get_default_table_aliases());
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_rating', 'tool_courserating');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;
        $tablealias = $this->get_table_alias('tool_courserating_rating');
        $columns = [];

        // Rating column.
        $columns[] = (new column(
            'rating',
            new lang_string('rating_rating', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.rating, {$tablealias}.id")
            ->set_is_sortable(true)
            ->set_callback(static function($rating, $row) {
                // TODO MDL-76199 not currently possible to set custom callbacks for AVG() that should display float.
                if (empty($row->id)) {
                    // This is aggregation - display as float, it works for AVG but not for MIN/MAX/SUM unfortunately.
                    return helper::format_avgrating($row->rating);
                } else {
                    // This is a non-aggregated value - display as integer.
                    return $rating ?? '';
                }
            });

        // Rating column.
        $columns[] = (new column(
            'stars',
            new lang_string('ratingasstars', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT) // TODO MDL-76199 this is actually integer but otherwise AVG aggregation rounds it.
            ->add_fields("{$tablealias}.rating")
            ->set_disabled_aggregation(['sum']) // Not possible to set different callback to SUM(), so we have to disable it.
            ->set_is_sortable(true)
            ->add_callback(static function($avgrating, $r) {
                return helper::stars((float)$avgrating);
            });

        // Review column.
        $reviewfield = "{$tablealias}.review";
        if ($DB->get_dbfamily() === 'oracle') {
            $reviewfield = $DB->sql_order_by_text($reviewfield, 1024);
        }
        $columns[] = (new column(
            'review',
            new lang_string('rating_review', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$reviewfield}", 'review')
            ->add_fields("{$tablealias}.id, {$tablealias}.courseid")
            ->set_callback([helper::class, 'format_review']);

        // Hasreview column.
        $columns[] = (new column(
            'hasreview',
            new lang_string('rating_hasreview', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_fields("{$tablealias}.hasreview")
            ->set_is_sortable(true)
            ->add_callback(static function($value, stdClass $row): ?string {
                return is_null($value) ? null : format::boolean_as_text((int)$value);
            });

        // Timecreated column.
        $columns[] = (new column(
            'timecreated',
            new lang_string('rating_timecreated', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([helper::class, 'format_date']);

        // Timemodified column.
        $columns[] = (new column(
            'timemodified',
            new lang_string('rating_timemodified', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback([helper::class, 'format_date']);

        // Number of flags column.
        $a = database::generate_alias();
        $column = (new column(
            'flags',
            new lang_string('rating_nofflags', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("(SELECT count(1) FROM {tool_courserating_flag} {$a} WHERE {$a}.ratingid = {$tablealias}.id)", 'flags')
            ->set_is_sortable(true);
        if (in_array($DB->get_dbfamily(), ['mssql', 'oracle'])) {
            $column->set_disabled_aggregation(['avg', 'groupconcat', 'count', 'countdistinct',
                'groupconcatdistinct', 'max', 'min', 'sum', ]);
            $column->set_groupby_sql("{$tablealias}.id");
        }
        $columns[] = $column;

        // Actions column.
        $columns[] = (new column(
            'actions',
            new lang_string('rating_actions', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.id, {$tablealias}.courseid")
            ->set_disabled_aggregation_all()
            ->set_callback([helper::class, 'format_actions']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('tool_courserating_rating');
        $filters = [];

        // Rating filter.
        $filters[] = (new filter(
            number::class,
            'rating',
            new lang_string('rating_rating', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.rating"
        ))
            ->add_joins($this->get_joins());

        // Review filter.
        $filters[] = (new filter(
            text::class,
            'review',
            new lang_string('rating_review', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.review"
        ))
            ->add_joins($this->get_joins());

        // Hasreview filter.
        $filters[] = (new filter(
            boolean_select::class,
            'hasreview',
            new lang_string('rating_hasreview', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.hasreview"
        ))
            ->add_joins($this->get_joins());

        // Timecreated filter.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('rating_timecreated', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        // Timemodified filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('rating_timemodified', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        // Number of flags filter.
        $a = database::generate_alias();
        $filters[] = (new filter(
            number::class,
            'flags',
            new lang_string('rating_nofflags', 'tool_courserating'),
            $this->get_entity_name(),
            "(SELECT count(1) FROM {tool_courserating_flag} {$a} WHERE {$a}.ratingid = {$tablealias}.id)"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
