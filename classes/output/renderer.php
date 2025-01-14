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

namespace tool_courserating\output;

use plugin_renderer_base;
use tool_courserating\constants;
use tool_courserating\helper;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;
use tool_courserating\permission;
use tool_courserating\external\summary_exporter;
use tool_courserating\external\ratings_list_exporter;
use html_writer;
use moodle_url;

/**
 * Renderer for tool_courserating.
 *
 * Provides rendering methods for course ratings features.
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders content of a custom field for a course.
     *
     * @param int $courseid The course ID.
     * @return string Rendered HTML content for the custom field.
     */
    public function cfield(int $courseid): string {
        $summary = summary::get_for_course($courseid);
        $data = (new summary_exporter(0, $summary))->export($this);
        return $this->render_from_template('tool_courserating/summary_for_cfield', $data);
    }

    /**
     * Renders the popup content for course ratings.
     *
     * @param int $courseid The course ID.
     * @return string Rendered HTML content for the popup.
     */
    public function course_ratings_popup(int $courseid): string {
        global $USER;
    
        // Получение текущего значения глобальной настройки.
        $showusernames = helper::get_setting(constants::SETTING_SHOWUSERNAMES);
    
        // Если глобальная настройка установлена на "per-course", учитывать настройку курса.
        if ($showusernames == constants::SHOWUSERNAMES_PERCOURSE) {
            $percoursevisibility = get_config('tool_courserating', "username_visibility_course_{$courseid}");
            $showusernames = ($percoursevisibility === null) ? constants::SHOWUSERNAMES_SHOW : (int)$percoursevisibility;
        }
    
        // Экспорт данных для использования в шаблоне.
        $data1 = (new summary_exporter($courseid))->export($this);
        $data2 = (new ratings_list_exporter(['courseid' => $courseid]))->export($this);
        $data = (array) $data1 + (array) $data2;
    
        // Добавление информации о видимости имён пользователей.
        $data['canrate'] = permission::can_add_rating($courseid);
        $data['hasrating'] = $data['canrate'] && rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);
        $data['showusernames'] = $showusernames;
    
        // Подключение необходимых JavaScript для всплывающего окна.
        $this->page->requires->js_call_amd('tool_courserating/rating', 'setupViewRatingsPopup', []);
    
        // Рендеринг содержимого всплывающего окна.
        return $this->render_from_template('tool_courserating/course_ratings_popup', $data);
    }
    

    /**
     * Renders the course rating block to be added to the course page.
     *
     * @param int $courseid The course ID.
     * @return string Rendered HTML content for the rating block.
     */
    public function course_rating_block(int $courseid): string {
        global $CFG, $USER;

        if (!permission::can_view_ratings($courseid)) {
            return '';
        }

        $summary = summary::get_for_course($courseid);
        $canrate = permission::can_add_rating($courseid);
        $data = (new summary_exporter(0, $summary, $canrate))->export($this);
        $data->canrate = $canrate;
        $data->hasrating = $canrate && rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);

        $branch = $CFG->branch ?? '';
        if ($parentcss = helper::get_setting(constants::SETTING_PARENTCSS)) {
            $data->parentelement = $parentcss;
        } else if ((string) $branch === '311') {
            $data->parentelement = '#page-header .card-body, #page-header #course-header, #page-header';
        } else if ((string) $branch >= '400') {
            $data->parentelement = '#page-header';
            $data->extraclasses = 'pb-2';
        }

        return $this->render_from_template('tool_courserating/course_rating_block', $data);
    }

    /**
     * Renders the visibility toggle for usernames in course ratings.
     *
     * @param int $courseid The course ID.
     * @return string Rendered HTML content for the toggle form.
     */
    public function render_visibility_toggle(int $courseid): string {
        $globalvisibility = helper::get_setting(constants::SETTING_SHOWUSERNAMES);
        if ($globalvisibility != constants::SHOWUSERNAMES_PERCOURSE) {
            // Do not render the toggle if "Per-Course" mode is not active.
            return '';
        }

        $currentvisibility = get_config('tool_courserating', "username_visibility_course_{$courseid}") ?? 1;
        $url = new moodle_url('/admin/tool/courserating/index.php', ['id' => $courseid]);

        // Start container with some margin for vertical spacing.
        $output = html_writer::start_div('tool-courserating-toggle mt-3 mb-3');

        // Start the form; using 'form-inline' to align elements horizontally in many Moodle themes.
        $output .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $url->out(false),
            'class'  => 'form-inline'
        ]);

        // Label for our select element.
        $output .= html_writer::tag('label',
            get_string('usernamevisibilitytoggle', 'tool_courserating') . '&nbsp;',
            ['for' => 'username-visibility-toggle']
        );

        // The select element with "Show" or "Hide" options.
        // Adding Bootstrap classes for consistent styling.
        $output .= html_writer::select(
            [1 => get_string('show', 'tool_courserating'), 0 => get_string('hide', 'tool_courserating')],
            'usernamevisibility',
            $currentvisibility,
            false,
            [
                'id'    => 'username-visibility-toggle',
                'class' => 'custom-select mx-2'
            ]
        );

        // Hidden field for sesskey (no changes here).
        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'sesskey',
            'value' => sesskey()
        ]);

        // Submit button in a style similar to Moodle "Download" or "Save changes" buttons.
        // If your "Download" is primary (blue), change 'btn btn-secondary' to 'btn btn-primary'.
        $output .= html_writer::empty_tag('input', [
            'type'  => 'submit',
            'value' => get_string('savechanges', 'core'),
            'class' => 'btn btn-secondary'
        ]);

        // Close the form and the container.
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_div();

        return $output;
    }

}
