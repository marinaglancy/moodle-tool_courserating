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

namespace tool_courserating\task;

use tool_courserating\api;

/**
 * Task to recalculate ratings on all courses
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reindex extends \core\task\adhoc_task {

    /**
     * Name of the task
     *
     * @return \lang_string|string
     */
    public function get_name() {
        return get_string('reindextask', 'tool_courserating');
    }

    /**
     * Execute the task
     *
     * @return void
     */
    public function execute() {
        api::reindex();
    }

    /**
     * Schedule the task to run on the next cron
     */
    public static function schedule() {
        global $USER;

        $task = new static();
        $task->set_userid($USER->id);

        \core\task\manager::queue_adhoc_task($task, true);
    }
}
