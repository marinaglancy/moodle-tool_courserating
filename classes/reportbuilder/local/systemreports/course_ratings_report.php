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

namespace tool_courserating\reportbuilder\local\systemreports;

use context_system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use stdClass;
use tool_courserating\helper;
use tool_courserating\permission;
use tool_courserating\reportbuilder\local\entities\rating;

/**
 * Config changes system report class implementation
 *
 * @package    tool_courserating
 * @copyright  2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_ratings_report extends system_report {

    /**
     * Get course id
     *
     * @return mixed
     */
    protected function get_course_id() {
        return $this->get_context()->instanceid;
    }

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $ratingentity = new rating();
        $ratingtablealias = $ratingentity->get_table_alias('tool_courserating_rating');
        $paramcourseid = database::generate_param_name();
        $this->add_base_condition_sql("{$ratingtablealias}.courseid = :{$paramcourseid}",
            [$paramcourseid => $this->get_course_id()]);

        $this->set_main_table('tool_courserating_rating', $ratingtablealias);
        $this->add_entity($ratingentity);

        // We can join the "user" entity to our "main" entity using standard SQL JOIN.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = {$ratingtablealias}.userid")
        );

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Set if report can be downloaded.
        $this->set_downloadable(true, get_string('pluginname', 'tool_courserating'));
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return permission::can_view_report($this->get_course_id());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $columns = [
            'user:fullnamewithpicturelink',
            'rating:timemodified',
            'rating:rating',
            'rating:review',
            'rating:flags',
        ];
        if (permission::can_delete_rating(0, $this->get_course_id())) {
            $columns[] = 'rating:actions';
        }

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('rating:timemodified', SORT_DESC);

        // Custom callbacks.
        if ($column = $this->get_column('rating:rating')) {
            $column->set_callback([helper::class, 'format_rating_in_course_report']);
        }
        if ($column = $this->get_column('rating:flags')) {
            $column->set_callback([helper::class, 'format_flags_in_course_report']);
        }

    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'rating:rating',
            'rating:hasreview',
            'rating:review',
            'rating:flags',
        ];

        $this->add_filters_from_entities($filters);
    }
}
