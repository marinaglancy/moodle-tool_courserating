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

/**
 * Tests for permission class
 *
 * @package     tool_courserating
 * @covers      \tool_courserating\permission
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class permission_test extends \advanced_testcase {

    /**
     * setUp
     */
    protected function setUp(): void {
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

    public function test_can_view(): void {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $cat = $this->getDataGenerator()->create_category();
        $userrole = $DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('moodle/category:viewcourselist', CAP_PROHIBIT,
            $userrole, \context_coursecat::instance($cat->id)->id, true);
        $course4 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $user = $this->getDataGenerator()->create_user();

        // User can normally view ratings because they can see courses in the course list.
        // Course4 is in a category that prevents viewing. However if user is enrolled in this course they can view ratings.
        $this->setUser($user);
        $this->assertTrue(permission::can_view_ratings($course1->id));
        $this->assertFalse(permission::can_view_ratings($course4->id));
        $this->getDataGenerator()->enrol_user($user->id, $course4->id, 'student');
        $this->assertTrue(permission::can_view_ratings($course4->id));

        // Disable ratings everywhere.
        $this->get_generator()->set_config(constants::SETTING_RATINGMODE, constants::RATEBY_NOONE);
        $this->assertFalse(permission::can_view_ratings($course1->id));

        // Allow to override ratings per course.
        $this->get_generator()->set_config(constants::SETTING_PERCOURSE, 1);
        $this->get_generator()->set_course_rating_mode($course2->id, constants::RATEBY_ANYTIME);
        $this->assertTrue(permission::can_view_ratings($course2->id));

        $this->get_generator()->set_course_rating_mode($course3->id, constants::RATEBY_COMPLETED);
        $this->assertTrue(permission::can_view_ratings($course3->id));

        // Assert exception in require- method.
        try {
            permission::require_can_view_ratings($course4->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_can_rate(): void {
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'teacher');

        // Only user who is enrolled as a student can add rating.
        $this->setUser($user1);
        $this->assertTrue(permission::can_add_rating($course->id));

        $this->setUser($user2);
        $this->assertFalse(permission::can_add_rating($course->id));

        $this->setUser($user3);
        $this->assertFalse(permission::can_add_rating($course->id));

        // Assert exception in require- method.
        try {
            permission::require_can_add_rating($course->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_can_rate_completion(): void {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $cc = ['course' => $course->id, 'userid' => $user2->id];
        $ccompletion = new \completion_completion($cc);
        $ccompletion->mark_complete();

        // Only user who completed the course can rate it.
        $this->get_generator()->set_config(constants::SETTING_RATINGMODE, constants::RATEBY_COMPLETED);

        $this->setUser($user1);
        $this->assertFalse(permission::can_add_rating($course->id));

        $this->setUser($user2);
        $this->assertTrue(permission::can_add_rating($course->id));
    }

    public function test_can_flag_rating(): void {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $cat = $this->getDataGenerator()->create_category();
        $userrole = $DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('moodle/category:viewcourselist', CAP_PROHIBIT,
            $userrole, \context_coursecat::instance($cat->id)->id, true);
        $course4 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $user = $this->getDataGenerator()->create_user();

        // Leave ratings for all courses as another user.
        $userother = $this->getDataGenerator()->create_user();
        $this->setUser($userother);
        $rating1 = api::set_rating($course1->id, (object)['rating' => 1]);
        $rating2 = api::set_rating($course2->id, (object)['rating' => 2]);
        $rating3 = api::set_rating($course3->id, (object)['rating' => 3]);
        $rating4 = api::set_rating($course4->id, (object)['rating' => 4]);

        // User can normally view ratings because they can see courses in the course list.
        // Course4 is in a category that prevents viewing. However if user is enrolled in this course they can view ratings.
        $this->setUser($user);
        $this->assertTrue(permission::can_flag_rating($rating1->get('id')));
        $this->assertTrue(permission::can_flag_rating($rating1->get('id'), $course1->id));
        $this->assertFalse(permission::can_flag_rating($rating4->get('id')));
        $this->getDataGenerator()->enrol_user($user->id, $course4->id, 'student');
        $this->assertTrue(permission::can_flag_rating($rating4->get('id')));

        // Disable ratings everywhere.
        $this->get_generator()->set_config(constants::SETTING_RATINGMODE, constants::RATEBY_NOONE);
        $this->assertFalse(permission::can_flag_rating($rating1->get('id')));

        // Allow to override ratings per course.
        $this->get_generator()->set_config(constants::SETTING_PERCOURSE, 1);
        $this->get_generator()->set_course_rating_mode($course2->id, constants::RATEBY_ANYTIME);
        $this->assertTrue(permission::can_flag_rating($rating2->get('id')));

        $this->get_generator()->set_course_rating_mode($course3->id, constants::RATEBY_COMPLETED);
        $this->assertTrue(permission::can_flag_rating($rating3->get('id')));

        // Assert exception in require- method.
        try {
            permission::require_can_flag_rating($rating1->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_can_delete_rating(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $cat = $this->getDataGenerator()->create_category();
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $managerrole = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($managerrole, $manager->id, \context_coursecat::instance($cat->id)->id);

        $this->setUser($user);
        $rating1 = api::set_rating($course1->id, (object)['rating' => 1]);
        $rating2 = api::set_rating($course2->id, (object)['rating' => 2]);

        $this->setUser($manager);
        $this->assertFalse(permission::can_delete_rating($rating1->get('id')));
        $this->assertTrue(permission::can_delete_rating($rating2->get('id')));

        // Assert exception in require- method.
        try {
            permission::require_can_delete_rating($rating1->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}
