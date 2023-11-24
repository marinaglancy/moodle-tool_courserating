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

namespace tool_courserating\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\report;
use core_reportbuilder\manager;
use tool_courserating\reportbuilder\local\entities\summary;
use tool_courserating\reportbuilder\local\entities\rating;
use core_reportbuilder\local\entities\user;

/**
 * Reportbuilder datasource courseratings.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseratings extends datasource {
    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('datasource_courseratings', 'tool_courserating');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        global $CFG;
        $courseentity = new course();
        $coursetablealias = $courseentity->get_table_alias('course');
        $this->set_main_table('course', $coursetablealias);
        $this->add_entity($courseentity);
        $paramsiteid = database::generate_param_name();
        $this->add_base_condition_sql("{$coursetablealias}.id != :{$paramsiteid}", [$paramsiteid => SITEID]);

        // Join the coursecategory entity.
        if ($CFG->version > 2022110000) {
            // Course category entity was renamed in 4.1.
            $coursecategoryentity = new \core_course\reportbuilder\local\entities\course_category();
        } else {
            $coursecategoryentity = new \core_course\local\entities\course_category();
        }
        $coursecategorytablealias = $coursecategoryentity->get_table_alias('course_categories');
        $coursecategoryjoin = "LEFT JOIN {course_categories} {$coursecategorytablealias}
                               ON {$coursecategorytablealias}.id = {$coursetablealias}.category";
        $this->add_entity($coursecategoryentity->add_join($coursecategoryjoin));

        // Join the summary entity.
        $summaryentity = new summary();
        $summarytablealias = $summaryentity->get_table_alias('tool_courserating_summary');
        $summaryjoin = "LEFT JOIN {tool_courserating_summary} {$summarytablealias}
                               ON {$summarytablealias}.courseid = {$coursetablealias}.id";
        $this->add_entity($summaryentity->add_join($summaryjoin));

        // Join the rating entity.
        $ratingentity = new rating();
        $ratingtablealias = $ratingentity->get_table_alias('tool_courserating_rating');
        $ratingjoin = "LEFT JOIN {tool_courserating_rating} {$ratingtablealias}
                               ON {$ratingtablealias}.courseid = {$coursetablealias}.id";
        $this->add_entity($ratingentity->add_join($ratingjoin));

        // Join the user entity.
        $userentity = new user();
        $usertablealias = $userentity->get_table_alias('user');
        $userjoin = "LEFT JOIN {user} {$usertablealias}
                               ON {$usertablealias}.id = {$ratingtablealias}.userid";
        $this->add_entity($userentity->add_join($ratingjoin)->add_join($userjoin));

        // Add all columns from entities to be available in custom reports.
        $this->add_columns_from_entity($courseentity->get_entity_name());
        $this->add_columns_from_entity($coursecategoryentity->get_entity_name());
        $this->add_columns_from_entity($summaryentity->get_entity_name());
        $this->add_columns_from_entity($ratingentity->get_entity_name());
        $this->add_columns_from_entity($userentity->get_entity_name());

        // Add all filters from entities to be available in custom reports.
        $this->add_filters_from_entity($courseentity->get_entity_name());
        $this->add_filters_from_entity($coursecategoryentity->get_entity_name());
        $this->add_filters_from_entity($summaryentity->get_entity_name());
        $this->add_filters_from_entity($ratingentity->get_entity_name());
        $this->add_filters_from_entity($userentity->get_entity_name());

        // Add all conditions from entities to be available in custom reports.
        $this->add_conditions_from_entity($courseentity->get_entity_name());
        $this->add_conditions_from_entity($coursecategoryentity->get_entity_name());
        $this->add_conditions_from_entity($summaryentity->get_entity_name());
        $this->add_conditions_from_entity($ratingentity->get_entity_name());
        $this->add_conditions_from_entity($userentity->get_entity_name());
    }

    /**
     * Return the columns that will be added to the report as part of default setup
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'course:coursefullnamewithlink',
            'course_category:name',
            'summary:avgrating',
            'summary:stars',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'course:courseselector',
        ];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'summary:ratingmode',
        ];
    }

    /**
     * Set default columns and the sortorder
     */
    public function add_default_columns(): void {
        parent::add_default_columns();

        $persistent = $this->get_report_persistent();
        $report = manager::get_report_from_persistent($persistent);
        foreach ($report->get_active_columns() as $column) {
            if ($column->get_unique_identifier() === 'course:coursefullnamewithlink') {
                report::toggle_report_column_sorting($persistent->get('id'), $column->get_persistent()->get('id'), true);
            }
        }
    }

    /**
     * Add default conditions and their values
     */
    public function add_default_conditions(): void {
        parent::add_default_conditions();
        $this->set_condition_values([
            'summary:ratingmode_operator' => select::NOT_EQUAL_TO,
            'summary:ratingmode_value' => 0,
        ]);
    }
}
