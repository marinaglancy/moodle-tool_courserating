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

use core_reportbuilder\manager;
use core_reportbuilder\local\helpers\aggregation;
use core_reportbuilder\local\helpers\report;
use core_reportbuilder\table\custom_report_table_view;
use tool_courserating\api;

/**
 * Tests for reportbuilder datasource courseratings.
 *
 * TODO when the minimum supported version is Moodle 4.0 - change this class to extend
 * {@see core_reportbuilder_testcase} and remove the component check and the methods copied from that class
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_courserating\reportbuilder\datasource\courseratings
 */
final class courseratings_test extends \advanced_testcase {

    /**
     * setUp
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        set_config(\tool_courserating\constants::SETTING_RATINGMODE,
            \tool_courserating\constants::RATEBY_ANYTIME, 'tool_courserating');
    }

    /**
     * Set up for test
     */
    protected function set_up_for_test() {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User1']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'User2']);
        /** @var \tool_courserating_generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('tool_courserating');
        $rating1 = $generator->create_rating($user1->id, $course->id, 3, 'hello <b>unclosed tag');
        usleep(1000); // Make sure timestamp is different on the ratings.
        $rating2 = $generator->create_rating($user2->id, $course->id, 2);

        $this->setUser($user2);
        api::flag_review($rating1->get('id'));
        $this->setAdminUser();
    }

    /**
     * Stress test datasource
     *
     * In order to execute this test PHPUNIT_LONGTEST should be defined as true in phpunit.xml or directly in config.php
     */
    public function test_stress_datasource(): void {
        if (!\core_component::get_component_directory('core_reportbuilder')) {
            // TODO remove when the minimum supported version is Moodle 4.0.
            $this->markTestSkipped('Report builder not found');
        }
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->set_up_for_test();

        $this->datasource_stress_test_columns(courseratings::class);
        $this->datasource_stress_test_columns_aggregation(courseratings::class);
        $this->datasource_stress_test_conditions(courseratings::class, 'course:coursefullnamewithlink');
    }

    /**
     * Test for report content
     *
     * @return void
     */
    public function test_content(): void {
        if (!\core_component::get_component_directory('core_reportbuilder')) {
            // TODO remove when the minimum supported version is Moodle 4.0.
            $this->markTestSkipped('Report builder not found');
        }
        $this->set_up_for_test();

        /** @var \core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report(['name' => 'CR', 'source' => courseratings::class]);

        // Add more columns.
        $c = $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'user:firstname']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'rating:rating']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'rating:review']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'rating:flags']);
        // Add flags column twice to check how different DB engines handle it.
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'rating:flags']);
        report::toggle_report_column_sorting($report->get('id'), $c->get('id'), true);

        // Analyse results.
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(3, $content);

        $this->assertEquals(['2.5', '2.5', ''], array_column($content, 'c2_avgrating'));
        $this->assertEquals(['User1', 'User2', null], array_column($content, 'c4_firstname'));
        $this->assertEquals(['<div class="text_to_html">hello unclosed tag</div>', '', null],
            array_column($content, 'c6_review'));
        $this->assertEquals([1, 0, 0], array_column($content, 'c7_flags'));
        $this->assertEquals([1, 0, 0], array_column($content, 'c8_flags'));
    }

    /**
     * Retrieve content for given report as array of report data
     *
     * TODO remove when the minimum supported version is Moodle 4.0 and parent class is core_reportbuilder_testcase
     *
     * @param int $reportid
     * @param int $pagesize
     * @return array[]
     */
    protected function get_custom_report_content(int $reportid, int $pagesize = 30): array {
        $records = [];

        // Create table instance.
        $table = custom_report_table_view::create($reportid);
        $table->setup();
        $table->query_db($pagesize, false);

        // Extract raw data.
        foreach ($table->rawdata as $record) {
            $records[] = $table->format_row($record);
        }

        $table->close_recordset();

        return $records;
    }

    /**
     * Stress test a report source by iterating over all it's columns and asserting we can create a report for each
     *
     * TODO remove when the minimum supported version is Moodle 4.0 and parent class is core_reportbuilder_testcase
     *
     * @param string $source
     */
    protected function datasource_stress_test_columns(string $source): void {

        /** @var \core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');

        $report = $generator->create_report(['name' => 'Stress columns', 'source' => $source, 'default' => 0]);
        $instance = manager::get_report_from_persistent($report);

        // Iterate over each available column, ensure each works correctly independent of any others.
        $columnidentifiers = array_keys($instance->get_columns());
        foreach ($columnidentifiers as $columnidentifier) {
            $column = report::add_report_column($report->get('id'), $columnidentifier);

            // We are only asserting the report returns content without errors, not the content itself.
            try {
                $content = $this->get_custom_report_content($report->get('id'));
                $this->assertNotEmpty($content);
            } catch (\Throwable $exception) {
                $this->fail("Error for column '{$columnidentifier}': " . $exception->getMessage());
            }

            report::delete_report_column($report->get('id'), $column->get('id'));
        }
    }

    /**
     * Stress test a report source by iterating over all columns and asserting we can create a report while aggregating each
     *
     * TODO remove when the minimum supported version is Moodle 4.0 and parent class is core_reportbuilder_testcase
     *
     * @param string $source
     */
    protected function datasource_stress_test_columns_aggregation(string $source): void {

        /** @var \core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');

        $report = $generator->create_report(['name' => 'Stress aggregation', 'source' => $source, 'default' => 0]);
        $instance = manager::get_report_from_persistent($report);

        // Add every column.
        $columnidentifiers = array_keys($instance->get_columns());
        foreach ($columnidentifiers as $columnidentifier) {
            report::add_report_column($report->get('id'), $columnidentifier);
        }

        // Now iterate over each column, and apply all suitable aggregation types.
        foreach ($instance->get_active_columns() as $column) {
            $aggregations = aggregation::get_column_aggregations($column->get_type(), $column->get_disabled_aggregation());
            foreach (array_keys($aggregations) as $aggregation) {
                $column->get_persistent()->set('aggregation', $aggregation)->update();

                // We are only asserting the report returns content without errors, not the content itself.
                try {
                    $content = $this->get_custom_report_content($report->get('id'));
                    $this->assertNotEmpty($content);
                } catch (\Throwable $exception) {
                    $this->fail("Error for column '{$column->get_unique_identifier()}' with aggregation '{$aggregation}': " .
                        $exception->getMessage());
                }
            }

            // Reset the column aggregation.
            $column->get_persistent()->set('aggregation', null)->update();
        }
    }

    /**
     * Stress test a report source by iterating over all it's conditions and asserting we can create a report using each
     *
     * TODO remove when the minimum supported version is Moodle 4.0 and parent class is core_reportbuilder_testcase
     *
     * @param string $source
     * @param string $columnidentifier Should be a simple column, with as few fields and joins as possible, ideally selected
     *      from the base table itself
     */
    protected function datasource_stress_test_conditions(string $source, string $columnidentifier): void {

        /** @var \core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');

        $report = $generator->create_report(['name' => 'Stress conditions', 'source' => $source, 'default' => 0]);
        $instance = manager::get_report_from_persistent($report);

        // Add single column only (to ensure no conditions have reliance on any columns).
        report::add_report_column($report->get('id'), $columnidentifier);

        // Iterate over each available condition, ensure each works correctly independent of any others.
        $conditionidentifiers = array_keys($instance->get_conditions());
        foreach ($conditionidentifiers as $conditionidentifier) {
            $condition = report::add_report_condition($report->get('id'), $conditionidentifier);
            $conditioninstance = $instance->get_condition($condition->get('uniqueidentifier'));

            /** @var \core_reportbuilder\local\filters\base $conditionclass */
            $conditionclass = $conditioninstance->get_filter_class();

            // Set report condition values in order to activate it.
            $conditionvalues = $conditionclass::create($conditioninstance)->get_sample_values();
            if (empty($conditionvalues)) {
                debugging("Missing sample values from filter '{$conditionclass}'", DEBUG_DEVELOPER);
            }
            $instance->set_condition_values($conditionvalues);

            // We are only asserting the report returns content without errors, not the content itself.
            try {
                $content = $this->get_custom_report_content($report->get('id'));
                $this->assertIsArray($content);
            } catch (\Throwable $exception) {
                $this->fail("Error for condition '{$conditionidentifier}': " . $exception->getMessage());
            }

            report::delete_report_condition($report->get('id'), $condition->get('id'));
        }
    }
}
