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

use core\event\base;
use tool_courserating\event\flag_created;
use tool_courserating\event\flag_deleted;
use tool_courserating\event\rating_created;
use tool_courserating\event\rating_deleted;
use tool_courserating\event\rating_updated;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

/**
 * Tests for api class
 *
 * @package     tool_courserating
 * @covers      \tool_courserating\api
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api_test extends \advanced_testcase {

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

    /**
     * Assert rating in the database matches expectations
     *
     * @param array|null $expected
     * @param int $userid
     * @param int $courseid
     */
    protected function assert_rating(?array $expected, int $userid, int $courseid) {
        global $DB;

        $params = ['courseid' => $courseid, 'userid' => $userid];
        if (empty($expected)) {
            $this->assertEmpty($DB->get_records(rating::TABLE, $params));
        } else {
            $fields = join(', ', array_keys($expected));
            $this->assertEquals($expected,
                (array)$DB->get_record(rating::TABLE, $params, $fields, MUST_EXIST));
        }
    }

    /**
     * Assert summary in the database matches expectations
     *
     * @param array $expected
     * @param int $courseid
     */
    protected function assert_summary(array $expected, int $courseid) {
        global $DB;

        $cfield = $DB->get_field_sql('SELECT d.value FROM {customfield_field} f
            JOIN {customfield_data} d ON d.fieldid = f.id
            JOIN {customfield_category} c ON f.categoryid = c.id
            WHERE f.shortname = ? AND d.instanceid = ?
                  AND c.component = ? AND c.area = ?',
            [constants::CFIELD_RATING, $courseid, 'core_course', 'course']);

        $params = ['courseid' => $courseid];
        if (empty($expected)) {
            $this->assertEmpty($DB->get_records(summary::TABLE, $params));
            $this->assertEmpty($cfield);
        } else {
            $record = (array)$DB->get_record(summary::TABLE, $params, '*', MUST_EXIST);
            $this->assertEquals($expected, array_intersect_key($record, $expected));
        }

        if ($record['cntall'] ?? 0) {
            $this->assertStringContainsString("({$record['cntall']})", strip_tags($cfield));
        } else {
            $this->assertEmpty($cfield);
        }
    }

    /**
     * Assert contents of an event
     *
     * @param \phpunit_event_sink $sink
     * @param string $classname
     * @param int $courseid
     * @param string $namematch
     * @param string $descriptionmatch
     * @return void
     */
    protected function assert_event(\phpunit_event_sink $sink, string $classname, int $courseid,
                                    string $namematch, string $descriptionmatch) {
        $events = $sink->get_events();
        $this->assertEquals(1, count($events));
        /** @var base $event */
        $event = reset($events);
        $this->assertEquals($classname, ltrim($event->eventname, '\\'));
        $this->assertEquals($courseid, $event->courseid);
        $this->assertEquals(\context_course::instance($courseid)->id, $event->contextid);
        $this->assertStringMatchesFormat($namematch, $event->get_name());
        $this->assertStringMatchesFormat($descriptionmatch, $event->get_description());

        $sink->clear();
    }

    public function test_set_rating(): void {
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->assert_rating([], $user->id, $course->id);
        $this->assert_rating([], $user2->id, $course->id);
        $this->assert_summary(['cntall' => 0], $course->id);

        // Set rating as the first user.
        $this->setUser($user);
        $sink = $this->redirectEvents();
        api::set_rating($course->id, (object)['rating' => 4]);
        $this->assert_event($sink, rating_created::class, $course->id, 'Course rating created',
            '%ahas rated the course with 4 stars');

        $this->assert_rating(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 4, 'sumrating' => 4, 'cnt02' => 0, 'cnt03' => 0, 'cnt04' => 1];
        $this->assert_summary($expected, $course->id);

        // Set rating as the second user.
        $this->setUser($user2);
        api::set_rating($course->id, (object)['rating' => 2]);

        $this->assert_rating(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $this->assert_rating(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 3, 'sumrating' => 6, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 1];
        $this->assert_summary($expected, $course->id);

        // Change rating as the first user.
        $this->setUser($user);
        $sink->clear();
        api::set_rating($course->id, (object)['rating' => 3, 'review' => 'hello']);
        $this->assert_event($sink, rating_updated::class, $course->id, 'Course rating updated',
            'User %a has changed the rating for the course from 4 to 3');

        $this->assert_rating(['rating' => 3, 'review' => 'hello', 'hasreview' => 1], $user->id, $course->id);
        $this->assert_rating(['rating' => 2, 'review' => '', 'hasreview' => 0], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 2.5, 'sumrating' => 5, 'cnt02' => 1, 'cnt03' => 1, 'cnt04' => 0,
            'cntreviews' => 1, ];
        $this->assert_summary($expected, $course->id);

        summary::get_for_course($course->id)->recalculate();
        $this->assert_summary($expected, $course->id);
    }

    public function test_delete_rating(): void {
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Set rating as the first user.
        $this->setUser($user);
        api::set_rating($course->id, (object)['rating' => 4]);

        // Set rating as the second user.
        $this->setUser($user2);
        api::set_rating($course->id, (object)['rating' => 2]);

        $this->assert_rating(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $this->assert_rating(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 3, 'sumrating' => 6, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 1];
        $this->assert_summary($expected, $course->id);
        $rating = rating::get_record(['userid' => $user->id, 'courseid' => $course->id]);

        // Delete rating for the first user.
        $sink = $this->redirectEvents();
        api::delete_rating($rating->get('id'));
        $this->assert_event($sink, rating_deleted::class, $course->id, 'Course rating deleted',
            '%ahas deleted course rating%a');

        $this->assert_rating(null, $user->id, $course->id);
        $this->assert_rating(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 2, 'sumrating' => 2, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 0];
        $this->assert_summary($expected, $course->id);
    }

    public function test_flag_rating(): void {
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Set rating as the first user.
        $this->setUser($user);
        $rating = api::set_rating($course->id, (object)['rating' => 4]);

        // Flag rating as the second user.
        $this->setUser($user2);
        $sink = $this->redirectEvents();
        api::flag_review($rating->get('id'));
        $this->assert_event($sink, flag_created::class, $course->id, 'Course rating flagged',
            '%ahas flagged the course rating%a');

        // Revoke.
        api::revoke_review_flag($rating->get('id'));
        $this->assert_event($sink, flag_deleted::class, $course->id, 'Course rating flag revoked',
            '%ahas revoked their flag%a');

        // Flag again and delete rating as admin.
        api::flag_review($rating->get('id'));
        $this->assert_event($sink, flag_created::class, $course->id, 'Course rating flagged',
            '%ahas flagged the course rating%a');
        $this->setAdminUser();
        api::delete_rating($rating->get('id'), 'spam');
        $this->assert_event($sink, rating_deleted::class, $course->id, 'Course rating deleted',
            '%ahas deleted course rating%a. Reason provided: spam');
    }

    public function test_reindex(): void {
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->assert_rating([], $user->id, $course->id);
        $this->assert_rating([], $user2->id, $course->id);
        $this->assert_summary(['cntall' => 0], $course->id);

        // Set rating as the first user.
        $this->setUser($user);
        $sink = $this->redirectEvents();
        api::set_rating($course->id, (object)['rating' => 4]);
        $this->assert_event($sink, rating_created::class, $course->id, 'Course rating created',
            '%ahas rated the course with 4 stars');

        $this->assert_rating(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 4, 'sumrating' => 4, 'cnt02' => 0, 'cnt03' => 0, 'cnt04' => 1];
        $this->assert_summary($expected, $course->id);

        api::reindex();

        $this->assert_rating(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 4, 'sumrating' => 4, 'cnt02' => 0, 'cnt03' => 0, 'cnt04' => 1];
        $this->assert_summary($expected, $course->id);
    }

    public function test_create_rating(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->get_generator()->create_rating($user->id, $course->id, 3);
        $this->assertEquals(3, summary::get_for_course($course->id)->get('avgrating'));
    }
}
