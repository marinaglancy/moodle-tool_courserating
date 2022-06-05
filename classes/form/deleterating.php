<?php

namespace tool_courserating\form;

use context;
use core_form\dynamic_form;
use moodle_url;
use tool_courserating\api;
use tool_courserating\local\models\rating;
use tool_courserating\permission;

class deleterating extends dynamic_form {
    /** @var rating */
    protected $rating;

    protected function get_rating_id(): int {
        $id = $this->optional_param('ratingid', 0, PARAM_INT);
        if ($id <= 0) {
            throw new \moodle_exception('missingparam', '', '', 'ratingid');
        }
        return $id;
    }

    protected function get_rating(): rating {
        if (!$this->rating) {
            $this->rating = new rating($this->get_rating_id());
        }
        return $this->rating;
    }

    protected function get_context_for_dynamic_submission(): context {
        return \context_course::instance($this->get_rating()->get('courseid'));
    }

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

    public function set_data_for_dynamic_submission(): void {
        $this->set_data(['ratingid' => $this->get_rating_id()]);
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/course/view.php',
            ['id' => $this->get_rating()->get('courseid'), 'deleterating' => $this->get_rating_id()]);
    }

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'ratingid');
        $mform->setType('ratingid', PARAM_INT);

        $mform->addElement('textarea', 'reason', get_string('deletereason', 'tool_courserating'));
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', get_string('required'), 'required', null, 'client');
    }
}