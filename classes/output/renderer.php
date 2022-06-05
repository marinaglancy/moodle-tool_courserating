<?php

namespace tool_courserating\output;

use context_course;
use html_writer;
use plugin_renderer_base;
use tool_courserating\external\course_ratings_summary;
use tool_courserating\external\rating_exporter;
use tool_courserating\external\ratings_list_exporter;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;

class renderer extends plugin_renderer_base {

    public function rating_summary_for_cfield(summary $summary): string {
        $context = \context_course::instance($summary->get('courseid'));
        return $this->render_from_template('tool_courserating/cfield', [
            'cntall' => $summary->get('cntall'),
            'avgrating' => sprintf("%.1f", $summary->get('avgrating')),
            'courseid' => $summary->get('courseid'),
            'stars' => $this->stars($summary->get('avgrating')),
        ]);
    }

    public function stars(float $rating): string {
        $rating = round($rating * 2) / 2;
        $icons = array_fill(0, floor($rating), $this->star(1));
        $icons[] = $this->star($rating - floor($rating));
        $icons = array_slice($icons, 0, 5);
        $icons = array_merge($icons, array_fill(0, 5 - count($icons), $this->star(0)));
        return html_writer::span(join('', $icons), 'tool_courserating-stars');
    }

    public function star(float $rating): string {
        if ($rating < 0.25) {
            $icon = 'star-o';
        } else if ($rating >= 0.75) {
            $icon = 'star';
        } else {
            $icon = 'star-half';
        }
        return $this->pix_icon($icon, 'rating', 'tool_courserating', []);
//        $ratingpercent = round(max(min($rating * 100, 0.9999), 0), 2);
//        $data = [
//            'uniqueid' => 'courserating_' . random_string(15),
//            'fillcolor' => 'rgb(0, 118, 206)',
//            'offcolor' => 'rgb(238, 238, 238)',
//            'rating' => $ratingpercent,
//        ];
//        return $this->render_from_template('tool_courserating/star', $data);
    }

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
        $data1 = (new course_ratings_summary($courseid))->export($this);
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