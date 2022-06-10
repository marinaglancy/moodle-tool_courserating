<?php

namespace tool_courserating\task;

use tool_courserating\api;

class reindex extends \core\task\adhoc_task {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('reindextask', 'tool_courserating');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        api::reindex();
    }

    public static function schedule() {
        global $USER;

        $task = new static();
        $task->set_userid($USER->id);

        \core\task\manager::queue_adhoc_task($task, true);
    }
}
