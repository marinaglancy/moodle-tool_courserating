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

namespace tool_courserating\output;

use tool_courserating\api;

/**
 * Tests for report for 3.11
 *
 * TODO remove when the minimum supported version is Moodle 4.0
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_courserating\output\report311
 */
final class report311_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $course;

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
        sleep(1); // Make sure timestamp is different on the ratings.
        $rating2 = $generator->create_rating($user2->id, $course->id, 2);

        $this->setUser($user2);
        api::flag_review($rating1->get('id'));
        $this->setAdminUser();

        $this->course = $course;
    }

    /**
     * Test for report content
     *
     * @return void
     */
    public function test_content(): void {
        $this->set_up_for_test();

        $report = new report311(new \moodle_url('/admin/tool/courserating/index.php', ['id' => $this->course->id]));
        $report->setup();
        $report->query_db(50, false);

        // Analyse raw results.
        $content = $report->rawdata;
        $this->assertCount(2, $content);

        $this->assertEquals([2, 3], array_column($content, 'rating'));
        $this->assertEquals(['User2', 'User1'], array_column($content, 'firstname'));
        $this->assertEquals(['', 'hello <b>unclosed tag'], array_column($content, 'review'));
        $this->assertEquals([0, 1], array_column($content, 'flags'));

        // Analyse formatted output.
        ob_start();
        $report->build_table();
        $report->close_recordset();
        $report->finish_output();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(false, strpos($output, 'hello <b>unclosed tag'));
        $this->assertNotEmpty(strpos($output, '<div class="text_to_html">hello unclosed tag</div>'));
    }
}
