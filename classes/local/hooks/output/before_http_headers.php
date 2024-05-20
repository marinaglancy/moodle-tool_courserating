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

namespace tool_courserating\local\hooks\output;

/**
 * Hook callbacks for tool_courserating
 *
 * @package    tool_courserating
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_http_headers {

    /**
     * Callback allowing to add js to $PAGE->requires
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }

        global $PAGE, $CFG;
        if (\tool_courserating\helper::course_ratings_enabled_anywhere() &&
                !in_array($PAGE->pagelayout, ['redirect', 'embedded'])) {
            // Add JS to all pages, the course ratings can be displayed on any page (for example course listings).
            $branch = $CFG->branch ?? '';
            $PAGE->requires->js_call_amd('tool_courserating/rating', 'init',
                [\context_system::instance()->id, "{$branch}" < "400"]);
            if (\tool_courserating\helper::is_course_edit_page()) {
                $field = \tool_courserating\helper::get_course_rating_field();
                $PAGE->requires->js_call_amd('tool_courserating/rating', 'hideEditField',
                    [$field->get('shortname')]);
            }
        }
    }
}
