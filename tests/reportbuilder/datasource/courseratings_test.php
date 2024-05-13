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
use core_reportbuilder_generator;
use core_reportbuilder_testcase;

use tool_courserating\api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/reportbuilder/tests/helpers.php");

/**
 * Tests for reportbuilder datasource courseratings.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_courserating\reportbuilder\datasource\courseratings
 */
final class courseratings_test extends core_reportbuilder_testcase {

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
        $generator->create_rating($user2->id, $course->id, 2);

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
        $this->resetAfterTest();

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
        $this->resetAfterTest();
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

}
