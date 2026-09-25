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
 * Tests for plugin callbacks in lib.php
 *
 * @package     tool_courserating
 * @covers      ::tool_courserating_pluginfile
 * @copyright   2026 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    /**
     * Set up before class
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/admin/tool/courserating/lib.php');
    }

    /**
     * Set up
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config(constants::SETTING_RATINGMODE, constants::RATEBY_ANYTIME, 'tool_courserating');
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
     * Can the current user access the files in the given review
     *
     * Since the requested file does not exist, the callback returns false if the access is allowed
     * and throws an exception otherwise.
     *
     * @param \stdClass $course
     * @param int $ratingid
     * @return bool
     */
    protected function can_access_review_file(\stdClass $course, int $ratingid): bool {
        $context = \context_course::instance($course->id);
        try {
            $result = tool_courserating_pluginfile($course, null, $context, 'review', [$ratingid, 'image.png'], false);
            $this->assertFalse($result);
            return true;
        } catch (\moodle_exception $e) {
            $this->assertEquals('cannotview', $e->errorcode);
            return false;
        }
    }

    public function test_pluginfile_access(): void {
        global $CFG;
        // Newer Moodle versions enable forcelogin by default in fresh installs.
        $CFG->forcelogin = 0;

        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $rating = $this->get_generator()->create_rating($student1->id, $course->id, 5, 'Great course');
        $ratingid = $rating->get('id');

        // Files are only served when the rich text editor is enabled for reviews.
        set_config(constants::SETTING_USEHTML, 1, 'tool_courserating');

        // Reviews are visible to everybody.
        $this->get_generator()->set_config(constants::SETTING_ALLOWREVIEWS, constants::ALLOWREVIEWS_VISIBLE);
        $this->setUser($student2);
        $this->assertTrue($this->can_access_review_file($course, $ratingid));
        $this->setUser($teacher);
        $this->assertTrue($this->can_access_review_file($course, $ratingid));
        $this->setUser(null);
        $this->assertTrue($this->can_access_review_file($course, $ratingid));

        // With forcelogin, visitors who are not logged in can not access the files.
        $CFG->forcelogin = 1;
        $this->assertFalse($this->can_access_review_file($course, $ratingid));
        $CFG->forcelogin = 0;

        // Reviews are only visible to teachers.
        $this->get_generator()->set_config(constants::SETTING_ALLOWREVIEWS, constants::ALLOWREVIEWS_HIDDEN);
        $this->setUser($student2);
        $this->assertFalse($this->can_access_review_file($course, $ratingid));
        $this->setUser($teacher);
        $this->assertTrue($this->can_access_review_file($course, $ratingid));

        // Reviews are disabled, teachers can still see the old reviews in the course ratings report.
        $this->get_generator()->set_config(constants::SETTING_ALLOWREVIEWS, constants::ALLOWREVIEWS_NO);
        $this->setUser($student2);
        $this->assertFalse($this->can_access_review_file($course, $ratingid));
        $this->setUser($teacher);
        $this->assertTrue($this->can_access_review_file($course, $ratingid));
    }
}
