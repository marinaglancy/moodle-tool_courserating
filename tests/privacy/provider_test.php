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
        parent::setUp();
        $this->resetAfterTest(true);
        set_config(
            \tool_courserating\constants::SETTING_RATINGMODE,
            \tool_courserating\constants::RATEBY_ANYTIME,
            'tool_courserating'
        );
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

        [$user, $course, , , $rating2] = $this->setup_test_scenario_data();
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
        $data = $writer->get_data(['Course ratings', $course->shortname]);
        $this->assertNotEmpty($data);
        $this->assertEquals(4, $data->rating);

        // The review files are exported and the links to them are rewritten.
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $data->review);
        $this->assertStringContainsString('files/image1.png', $data->review);
        $files = $writer->get_files(['Course ratings', $course->shortname]);
        $this->assertEquals(['image1.png'], array_keys($files));

        // The flags that the user placed on other users' reviews are exported.
        $data = $writer->get_data(['Course ratings', $course->shortname, 'Flagged reviews']);
        $this->assertCount(1, $data->flags);
        $this->assertEquals($rating2->get('id'), $data->flags[0]->ratingid);
    }

    /**
     * Set up scenario data
     *
     * Two users rate the same course, both reviews have embedded files and each user flags the review of the other user.
     *
     * @return array [$user1, $course, $user2, $rating1, $rating2]
     */
    protected function setup_test_scenario_data() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'c1']);
        $this->setUser($user2);
        $rating2 = \tool_courserating\api::set_rating($course->id, (object)['rating' => 5]);
        $this->add_review_file($rating2, 'image2.png');
        $this->setUser($user1);
        $rating1 = \tool_courserating\api::set_rating($course->id, (object)['rating' => 4]);
        $this->add_review_file($rating1, 'image1.png');
        api::flag_review($rating2->get('id'));
        $this->setUser($user2);
        api::flag_review($rating1->get('id'));
        return [$user1, $course, $user2, $rating1, $rating2];
    }

    /**
     * Add a file to the review and embed it in the review text
     *
     * @param rating $rating
     * @param string $filename
     */
    protected function add_review_file(rating $rating, string $filename): void {
        $context = \context_course::instance($rating->get('courseid'));
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'tool_courserating',
            'filearea' => 'review',
            'itemid' => $rating->get('id'),
            'filepath' => '/',
            'filename' => $filename,
        ], 'Image content');
        $rating->set('review', '<p>Review <img src="@@PLUGINFILE@@/' . $filename . '" alt="Image"></p>');
        $rating->save();
    }

    /**
     * Get the names of the files in the review file area of the course
     *
     * @param \stdClass $course
     * @return array
     */
    protected function get_review_files(\stdClass $course): array {
        $files = get_file_storage()->get_area_files(
            \context_course::instance($course->id)->id,
            'tool_courserating',
            'review',
            false,
            'filename',
            false
        );
        return array_values(array_map(fn($f) => $f->get_filename(), $files));
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        [$user, $course] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        $this->assertEquals(['image1.png', 'image2.png'], $this->get_review_files($course));

        provider::delete_data_for_all_users_in_context($coursectx);
        $this->assertEmpty($DB->get_records(rating::TABLE));
        $this->assertEmpty($DB->get_records(flag::TABLE));
        $this->assertEmpty($this->get_review_files($course));
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        [$user, $course, $user2, $rating1, $rating2] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);

        // The user also has a rating in another course, it should not be deleted.
        $course2 = $this->getDataGenerator()->create_course();
        $this->setUser($user);
        $rating3 = api::set_rating($course2->id, (object)['rating' => 3]);
        $this->add_review_file($rating3, 'image3.png');
        $this->setAdminUser();

        // Test the User's retrieved contextlist contains two contexts.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(2, $contexts);

        $approvedcontextlist = new approved_contextlist($user, 'tool_courserating', [$coursectx->id]);
        provider::delete_data_for_user($approvedcontextlist);

        // The user's rating, the files in their review and all flags on it are deleted,
        // as well as the user's flags on the other reviews.
        $this->assertEqualsCanonicalizing(
            [$rating2->get('id'), $rating3->get('id')],
            $DB->get_fieldset_select(rating::TABLE, 'id', '1=1')
        );
        $this->assertEmpty($DB->get_records(flag::TABLE));
        $this->assertEquals(['image2.png'], $this->get_review_files($course));
        $this->assertEquals(['image3.png'], $this->get_review_files($course2));

        // The other user's data remains.
        $contextlist = provider::get_contexts_for_userid($user2->id);
        $this->assertEqualsCanonicalizing([$coursectx->id], $contextlist->get_contextids());
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertEqualsCanonicalizing([\context_course::instance($course2->id)->id], $contextlist->get_contextids());
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
        $this->assertEmpty($this->get_review_files($course));
    }

    /**
     * Test that data for one user in approved userlist is deleted together with the flags on their rating.
     */
    public function test_delete_data_for_users_one_user(): void {
        global $DB;
        $component = 'tool_courserating';

        [$user, $course, $user2, $rating1, $rating2] = $this->setup_test_scenario_data();
        $coursectx = \context_course::instance($course->id);
        $this->setAdminUser();

        $approvedlist = new approved_userlist($coursectx, $component, [$user->id]);
        provider::delete_data_for_users($approvedlist);

        $userlist = new \core_privacy\local\request\userlist($coursectx, $component);
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$user2->id], $userlist->get_userids());
        $this->assertEquals([$rating2->get('id')], $DB->get_fieldset_select(rating::TABLE, 'id', '1=1'));
        $this->assertEmpty($DB->get_records(flag::TABLE));
        $this->assertEquals(['image2.png'], $this->get_review_files($course));
    }
}
