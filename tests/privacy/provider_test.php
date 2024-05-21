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

namespace tool_courserating\privacy;

use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use tool_courserating\api;
use core_privacy\local\request\approved_userlist;
use tool_courserating\local\models\flag;
use tool_courserating\local\models\rating;

/**
 * Tests for privacy provider class
 *
 * @package     tool_courserating
 * @covers      \tool_courserating\privacy\provider
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Overriding setUp() function to always reset after tests.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        set_config(\tool_courserating\constants::SETTING_RATINGMODE,
            \tool_courserating\constants::RATEBY_ANYTIME, 'tool_courserating');
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);
        \tool_courserating\api::set_rating($course->id, (object)['rating' => 5]);

        $this->setAdminUser();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        $courseids = array_column($contexts, 'instanceid');
        $this->assertEqualsCanonicalizing([$course->id], $courseids);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_user_data(): void {

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        // Test the User's retrieved contextlist contains two contexts.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        // Add a system, course category and course context to the approved context list.
        $systemctx = \context_system::instance();
        $approvedcontextids = [
            $systemctx->id,
            $coursectx->id,
        ];

        // Retrieve the User's tool_cohortroles data.
        $approvedcontextlist = new approved_contextlist($user, 'tool_courserating', $approvedcontextids);
        provider::export_user_data($approvedcontextlist);

        // Test the tool_cohortroles data is exported at the system context level.
        $writer = writer::with_context($systemctx);
        $this->assertFalse($writer->has_any_data());
        // Test the tool_cohortroles data is not exported at the course context level.
        $writer = writer::with_context($coursectx);
        $this->assertTrue($writer->has_any_data());
        $this->assertNotEmpty($writer->get_data(['Course ratings', $course->shortname]));
    }

    /**
     * Set up scenario data
     *
     * @return array
     */
    protected function setup_test_scenario_data() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'c1']);
        $this->setUser($user2);
        $r = \tool_courserating\api::set_rating($course->id, (object)['rating' => 5]);
        $this->setUser($user1);
        \tool_courserating\api::set_rating($course->id, (object)['rating' => 4]);
        api::flag_review($r->get('id'));
        return [$user1, $course];
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        provider::delete_data_for_all_users_in_context($coursectx);
        $this->assertEmpty($DB->get_records(rating::TABLE));
        $this->assertEmpty($DB->get_records(flag::TABLE));
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        // Test the User's retrieved contextlist contains two contexts.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);

        $approvedcontextlist = new approved_contextlist($user, 'tool_courserating', [$coursectx->id]);
        provider::delete_data_for_user($approvedcontextlist);
    }

    /**
     * Test that only users within a course context are fetched.
     */
    public function test_get_users_in_context(): void {
        $component = 'tool_courserating';

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        $userlist = new \core_privacy\local\request\userlist($coursectx, $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $this->assertTrue(in_array($user->id, $userlist->get_userids()));
    }

    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users(): void {
        $component = 'tool_courserating';

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        $userlist1 = new \core_privacy\local\request\userlist($coursectx, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(2, $userlist1);
        $this->assertTrue(in_array($user->id, $userlist1->get_userids()));
        $userids = $userlist1->get_userids();

        $approvedlist1 = new approved_userlist($coursectx, $component, $userids);
        provider::delete_data_for_users($approvedlist1);

        $userlist1 = new \core_privacy\local\request\userlist($coursectx, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(0, $userlist1);
    }

}
