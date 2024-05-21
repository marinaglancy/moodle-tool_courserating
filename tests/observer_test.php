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

namespace tool_courserating;

use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

/**
 * Tests for permission class
 *
 * @package     tool_courserating
 * @covers      \tool_courserating\permission
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer_test extends \advanced_testcase {

    /**
     * Set up
     */
    public function setUp(): void {
        $this->resetAfterTest();
        set_config(\tool_courserating\constants::SETTING_RATINGMODE,
            \tool_courserating\constants::RATEBY_ANYTIME, 'tool_courserating');
    }

    /**
     * Generator
     *
     * @return \tool_courserating_generator
     */
    protected function get_generator(): \tool_courserating_generator {
        /** @var \tool_courserating_generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('tool_courserating');
        return $generator;
    }

    public function test_course_updated(): void {
        $course = $this->getDataGenerator()->create_course();

        $this->get_generator()->set_config(constants::SETTING_PERCOURSE, 1);
        $this->get_generator()->set_config(constants::SETTING_RATINGMODE, constants::RATEBY_NOONE);

        $this->assertEquals(constants::RATEBY_NOONE, summary::get_for_course($course->id)->get('ratingmode'));
        update_course((object)['id' => $course->id, 'customfield_'.constants::CFIELD_RATINGMODE => constants::RATEBY_ANYTIME]);
        $this->assertEquals(constants::RATEBY_ANYTIME, summary::get_for_course($course->id)->get('ratingmode'));
    }

    public function test_course_deleted(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $rating = api::set_rating($course->id, (object)['rating' => 5]);

        $this->setUser($user2);
        api::flag_review($rating->get('id'));

        delete_course($course->id, false);
        $this->assertEmpty($DB->get_records(summary::TABLE, ['courseid' => $course->id]));
        $this->assertEmpty($DB->get_records(rating::TABLE, []));
        $this->assertEmpty($DB->get_records(flag::TABLE, []));
    }

    public function test_course_created(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $this->assertNotEmpty($DB->get_records(summary::TABLE, ['courseid' => $course->id]));
    }
}
