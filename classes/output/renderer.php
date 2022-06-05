<?php

namespace tool_courserating\output;

use plugin_renderer_base;
use tool_courserating\external\summary_exporter;
use tool_courserating\external\ratings_list_exporter;
use tool_courserating\local\models\summary;

class renderer extends plugin_renderer_base {

    public function cfield(int $courseid): string {
        $content = '';
        $fieldsdata = \core_course\customfield\course_handler::create()->get_instance_data($courseid);
        foreach ($fieldsdata as $data) {
            if ($data->get_field()->get('shortname') === 'tool_courserating') {
                $output = $this->page->get_renderer('core_customfield');
                $fd = new \core_customfield\output\field_data($data);
                $content .= $output->render($fd);
            }
        }
        return $content;
    }

    public function course_ratings_popup(int $courseid): string {
        $data1 = (new summary_exporter($courseid))->export($this);
        $data2 = (new ratings_list_exporter(['courseid' => $courseid]))->export($this);
        $data = (array)$data1 + (array)$data2;
        return $this->render_from_template('tool_courserating/course_ratings_popup', $data);
    }

    public function course_rating_block(int $courseid): string {
        $data = [
            'ratingdisplay' => $this->cfield($courseid),
            'courseid' => $courseid,
            'rate' => true
        ];
        return $this->render_from_template('tool_courserating/course_rating_block', $data);
    }
}