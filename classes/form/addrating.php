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

namespace tool_courserating\form;

use moodle_exception;
use moodle_url;
use tool_courserating\api;
use tool_courserating\helper;
use tool_courserating\permission;

/**
 * Form to add or change a rating
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addrating extends \core_form\dynamic_form {

    /**
     * Current course id
     *
     * @return int
     */
    protected function get_course_id(): int {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if (!$courseid) {
            throw new moodle_exception('missingparam', '', '', 'courseid');
        }
        return $courseid;
    }

    /**
     * Form definition
     */
    protected function definition() {
        // TODO UI.
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $this->get_course_id());
        $mform->setType('courseid', PARAM_INT);

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'rating', '', 1, 1);
        $radioarray[] = $mform->createElement('radio', 'rating', '', 2, 2);
        $radioarray[] = $mform->createElement('radio', 'rating', '', 3, 3);
        $radioarray[] = $mform->createElement('radio', 'rating', '', 4, 4);
        $radioarray[] = $mform->createElement('radio', 'rating', '', 5, 5);
        $mform->addGroup($radioarray, 'ratinggroup', get_string('rating', 'tool_courserating'), array(' ', ' '), false);

        $options = helper::review_editor_options($this->get_context_for_dynamic_submission());
        $mform->addElement('editor', 'review_editor', get_string('review', 'tool_courserating'),
            ['rows' => 4], $options);
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        // TODO check rating is set.
        return $errors;
    }

    /**
     * Current context
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        return \context_course::instance($this->get_course_id());
    }

    /**
     * Check access and throw exception if not allowed
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        permission::require_can_add_rating($this->get_course_id());
    }

    /**
     * Process form submission
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        api::set_rating($this->get_course_id(), $data);
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data(api::prepare_rating_for_form($this->get_course_id()));
    }

    /**
     * Fake URL for atto auto-save
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_course_id(), 'addrating' => 1]);
    }
}
