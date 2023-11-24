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

/**
 * Class implementing WS tool_courserating_course_rating_popup
 *
 * @package    tool_courserating
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_courserating\external;

use external_function_parameters;
use external_single_structure;
use external_api;
use external_value;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * Implementation of web service tool_courserating_course_rating_popup
 *
 * @package    tool_courserating
 * @copyright  2023 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_rating_popup extends external_api {

    /**
     * Describes the parameters for tool_courserating_course_rating_popup
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Implementation of web service tool_courserating_course_rating_popup
     *
     * @param mixed $courseid
     */
    public static function execute($courseid) {
        global $PAGE, $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/courserating/lib.php');

        // Basically copied from the core_get_fragment WS except for login check.

        // Hack alert: Set a default URL to stop the annoying debug.
        $PAGE->set_url('/');
        // Hack alert: Forcing bootstrap_renderer to initiate moodle page.
        $OUTPUT->header();

        // Overwriting page_requirements_manager with the fragment one so only JS included from
        // this point is returned to the user.
        $PAGE->start_collecting_javascript_requirements();
        $data = tool_courserating_output_fragment_course_ratings_popup(['courseid' => $courseid]);
        $jsfooter = $PAGE->requires->get_end_code();
        $output = ['html' => $data, 'javascript' => $jsfooter];
        return $output;
    }

    /**
     * Describe the return structure for tool_courserating_course_rating_popup
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'html' => new external_value(PARAM_RAW, 'HTML fragment.'),
                'javascript' => new external_value(PARAM_RAW, 'JavaScript fragment'),
            ]
        );
    }
}
