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

use context;
use core_form\dynamic_form;
use moodle_url;
use tool_courserating\api;
use tool_courserating\local\models\rating;
use tool_courserating\permission;

/**
 * Form for deleting a rating (by manager)
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deleterating extends dynamic_form {
    /** @var rating */
    protected $rating;

    /**
     * Id of the current rating
     *
     * @return int
     */
    protected function get_rating_id(): int {
        $id = $this->optional_param('ratingid', 0, PARAM_INT);
        if ($id <= 0) {
            throw new \moodle_exception('missingparam', '', '', 'ratingid');
        }
        return $id;
    }

    /**
     * Current rating
     *
     * @return rating
     */
    protected function get_rating(): rating {
        if (!$this->rating) {
            $this->rating = new rating($this->get_rating_id());
        }
        return $this->rating;
    }

    /**
     * Current context
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_course::instance($this->get_rating()->get('courseid'));
    }

    /**
     * Check access and throw exception if not allowed
     *
     * @return void
     * @throws \moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        permission::require_can_delete_rating($this->get_rating_id(), $this->get_rating()->get('courseid'));
    }

    /**
     * Process submission
     *
     * @return mixed|void
     */
    public function process_dynamic_submission() {
        $rv = ['ratingid' => $this->get_rating_id(), 'courseid' => $this->get_rating()->get('courseid')];
        api::delete_rating($this->get_rating_id(), $this->get_data()->reason);
        return $rv;
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data(['ratingid' => $this->get_rating_id()]);
    }

    /**
     * Fake URL for atto auto-save
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/course/view.php',
            ['id' => $this->get_rating()->get('courseid'), 'deleterating' => $this->get_rating_id()]);
    }

    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'ratingid');
        $mform->setType('ratingid', PARAM_INT);

        $mform->addElement('textarea', 'reason', get_string('deletereason', 'tool_courserating'));
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', get_string('required'), 'required', null, 'client');
    }
}
