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

use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

class api_test extends \advanced_testcase {

    protected function assertRatings(?array $expected, int $userid, int $courseid) {
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

    protected function assertSummary(array $expected, int $courseid) {
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

    public function test_set_rating() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->assertRatings([], $user->id, $course->id);
        $this->assertRatings([], $user2->id, $course->id);
        $this->assertSummary([], $course->id);

        // Set rating as the first user.
        $this->setUser($user);
        api::set_rating($course->id, (object)['rating' => 4]);

        $this->assertRatings(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 4, 'sumrating' => 4, 'cnt02' => 0, 'cnt03' => 0, 'cnt04' => 1];
        $this->assertSummary($expected, $course->id);

        // Set rating as the second user.
        $this->setUser($user2);
        api::set_rating($course->id, (object)['rating' => 2]);

        $this->assertRatings(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $this->assertRatings(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 3, 'sumrating' => 6, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 1];
        $this->assertSummary($expected, $course->id);

        // Change rating as the first user.
        $this->setUser($user);
        api::set_rating($course->id, (object)['rating' => 3, 'review' => 'hello']);

        $this->assertRatings(['rating' => 3, 'review' => 'hello', 'hasreview' => 1], $user->id, $course->id);
        $this->assertRatings(['rating' => 2, 'review' => '', 'hasreview' => 0], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 2.5, 'sumrating' => 5, 'cnt02' => 1, 'cnt03' => 1, 'cnt04' => 0, 'cntreviews' => 1];
        $this->assertSummary($expected, $course->id);

        summary::get_for_course($course->id)->recalculate();
        $this->assertSummary($expected, $course->id);

    }

    public function test_delete_rating() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Set rating as the first user.
        $this->setUser($user);
        api::set_rating($course->id, (object)['rating' => 4]);

        // Set rating as the second user.
        $this->setUser($user2);
        api::set_rating($course->id, (object)['rating' => 2]);

        $this->assertRatings(['rating' => 4, 'review' => ''], $user->id, $course->id);
        $this->assertRatings(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 2, 'avgrating' => 3, 'sumrating' => 6, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 1];
        $this->assertSummary($expected, $course->id);
        $rating = rating::get_record(['userid' => $user->id, 'courseid' => $course->id]);

        // Delete rating for the first user.
        api::delete_rating($rating->get('id'));

        $this->assertRatings(null, $user->id, $course->id);
        $this->assertRatings(['rating' => 2, 'review' => ''], $user2->id, $course->id);
        $expected = ['cntall' => 1, 'avgrating' => 2, 'sumrating' => 2, 'cnt02' => 1, 'cnt03' => 0, 'cnt04' => 0];
        $this->assertSummary($expected, $course->id);

    }
}
