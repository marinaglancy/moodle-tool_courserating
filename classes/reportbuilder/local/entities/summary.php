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
use core_reportbuilder\local\helpers\format;
use lang_string;
use core_reportbuilder\local\report\column;
use stdClass;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use tool_courserating\constants;
use tool_courserating\helper;

/**
 * Reportbuilder entity representing table tool_courserating_summary.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary extends base {
    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['tool_courserating_summary' => 'tool_courserating_summary'];
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
        return new lang_string('entity_summary', 'tool_courserating');
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
        $tablealias = $this->get_table_alias('tool_courserating_summary');
        $columns = [];

        // Cntall column.
        $columns[] = (new column(
            'cntall',
            new lang_string('summary_cntall', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.cntall")
            ->set_is_sortable(true);

        // Avgrating column.
        $columns[] = (new column(
            'avgrating',
            new lang_string('summary_avgrating', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$tablealias}.avgrating")
            ->set_is_sortable(true)
            ->add_callback(static function($value, stdClass $row): ?string {
                return helper::format_avgrating($value);
            });

        $columns[] = (new column(
            'stars',
            new lang_string('ratingasstars', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$tablealias}.avgrating")
            ->set_disabled_aggregation(['sum'])
            ->set_is_sortable(true)
            ->add_callback(static function($avgrating, $r) {
                return helper::stars((float)$avgrating);
            });

        // Cntreviews column.
        $columns[] = (new column(
            'cntreviews',
            new lang_string('summary_cntreviews', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.cntreviews")
            ->set_is_sortable(true);

        // Cnt01-Cnt05 columns.
        for ($i = 1; $i <= 5; $i++) {
            // phpcs:disable Squiz.PHP.CommentedOutCode.Found
            // Mdlcode assume: $i ['1', '2', '3', '4', '5'].
            $fld = "cnt0{$i}";
            $columns[] = (new column(
                $fld,
                // phpcs:disable Squiz.PHP.CommentedOutCode.Found
                // Mdlcode assume-next-line: $fld ['cnt01', 'cnt02', 'cnt03', 'cnt04', 'cnt05'] .
                new lang_string('summary_'.$fld, 'tool_courserating'),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->set_type(column::TYPE_FLOAT)
                ->add_field("CASE WHEN {$tablealias}.cntall > 0 THEN ".
                    "100.0*{$tablealias}.{$fld}/{$tablealias}.cntall ELSE NULL END", "p")
                ->set_is_sortable(true)
                ->set_groupby_sql("{$tablealias}.cntall, {$tablealias}.{$fld}")
                ->set_callback(function ($value, $row) {
                    return ($row->p === null) ? null : format::percent($value);
                });

        }

        // Ratingmode column.
        $columns[] = (new column(
            'ratingmode',
            new lang_string('summary_ratingmode', 'tool_courserating'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.ratingmode")
            ->set_is_sortable(true)
            ->set_callback(static function($v) {
                return is_null($v) ? null : (constants::rated_courses_options()[$v] ?? null);
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('tool_courserating_summary');
        $filters = [];

        // Cntall filter.
        $filters[] = (new filter(
            number::class,
            'cntall',
            new lang_string('summary_cntall', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.cntall"
        ))
            ->add_joins($this->get_joins());

        // Avgrating filter.
        $filters[] = (new filter(
            number::class,
            'avgrating',
            new lang_string('summary_avgrating', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.avgrating"
        ))
            ->add_joins($this->get_joins());

        // Sumrating filter.
        $filters[] = (new filter(
            number::class,
            'sumrating',
            new lang_string('summary_sumrating', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.sumrating"
        ))
            ->add_joins($this->get_joins());

        // Cntreviews filter.
        $filters[] = (new filter(
            number::class,
            'cntreviews',
            new lang_string('summary_cntreviews', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.cntreviews"
        ))
            ->add_joins($this->get_joins());

        // Cnt01 filter.
        $filters[] = (new filter(
            number::class,
            'cnt01',
            new lang_string('summary_cnt01', 'tool_courserating'),
            $this->get_entity_name(),
            "CASE WHEN {$tablealias}.cntall > 0 THEN 100.0*{$tablealias}.cnt01/{$tablealias}.cntall ELSE NULL END"
        ))
            ->add_joins($this->get_joins());

        // Cnt02 filter.
        $filters[] = (new filter(
            number::class,
            'cnt02',
            new lang_string('summary_cnt02', 'tool_courserating'),
            $this->get_entity_name(),
            "CASE WHEN {$tablealias}.cntall > 0 THEN 100.0*{$tablealias}.cnt02/{$tablealias}.cntall ELSE NULL END"
        ))
            ->add_joins($this->get_joins());

        // Cnt03 filter.
        $filters[] = (new filter(
            number::class,
            'cnt03',
            new lang_string('summary_cnt03', 'tool_courserating'),
            $this->get_entity_name(),
            "CASE WHEN {$tablealias}.cntall > 0 THEN 100.0*{$tablealias}.cnt03/{$tablealias}.cntall ELSE NULL END"
        ))
            ->add_joins($this->get_joins());

        // Cnt04 filter.
        $filters[] = (new filter(
            number::class,
            'cnt04',
            new lang_string('summary_cnt04', 'tool_courserating'),
            $this->get_entity_name(),
            "CASE WHEN {$tablealias}.cntall > 0 THEN 100.0*{$tablealias}.cnt04/{$tablealias}.cntall ELSE NULL END"
        ))
            ->add_joins($this->get_joins());

        // Cnt05 filter.
        $filters[] = (new filter(
            number::class,
            'cnt05',
            new lang_string('summary_cnt05', 'tool_courserating'),
            $this->get_entity_name(),
            "CASE WHEN {$tablealias}.cntall > 0 THEN 100.0*{$tablealias}.cnt05/{$tablealias}.cntall ELSE NULL END"
        ))
            ->add_joins($this->get_joins());

        // Ratingmode filter.
        $filters[] = (new filter(
            select::class,
            'ratingmode',
            new lang_string('summary_ratingmode', 'tool_courserating'),
            $this->get_entity_name(),
            "{$tablealias}.ratingmode"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                return constants::rated_courses_options();
            });

        return $filters;
    }
}
