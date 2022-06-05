<?php

namespace tool_courserating\form;

use moodle_exception;
use moodle_url;
use tool_courserating\api;
use tool_courserating\helper;
use tool_courserating\local\models\rating;
use tool_courserating\permission;

class addrating extends \core_form\dynamic_form {

    protected function get_ctx_id() {
        $ctxid = $this->optional_param('ctxid', 0, PARAM_INT);
        if (!$ctxid) {
            throw new moodle_exception('missingparam', '', '', 'ctxid');
        }
        return $ctxid;
    }

    protected function get_course_id() {
        return \context::instance_by_id($this->get_ctx_id())->get_course_context()->instanceid;
    }

    protected function definition() {
        // TODO UI
        $mform = $this->_form;
        $mform->addElement('hidden', 'ctxid', $this->get_ctx_id());
        $mform->setType('ctxid', PARAM_INT);

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

    public function validation($data, $files) {
        $errors = [];
        // TODO check rating is set
        return $errors;
    }

    protected function get_context_for_dynamic_submission(): \context {
        return \context::instance_by_id($this->get_ctx_id());
    }

    protected function check_access_for_dynamic_submission(): void {
        permission::require_can_add_rating($this->get_course_id());
    }

    public function process_dynamic_submission() {
        $data = $this->get_data();
        api::set_rating($this->get_course_id(), $data);
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $id = $this->optional_param('id', 0, PARAM_INT);
     *     $data = api::get_entity($id); // For example, retrieve a row from the DB.
     *     file_prepare_standard_filemanager($data, ...);
     *     $this->set_data($data);
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data(api::prepare_rating_for_form($this->get_course_id()));
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_course_id(), 'addrating' => 1]);
    }
}