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

namespace tool_courserating;

use core_plugin_manager;
use customfield_number\data_controller;
use MoodleQuickForm;
use tool_courserating\external\summary_exporter;
use tool_courserating\local\models\summary;

/**
 * Class customfield_number_provider
 *
 * @package    tool_courserating
 * @copyright  Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfield_number_provider extends \customfield_number\provider_base {

    /**
     * Register this class as the provider for the 'Number' custom field
     *
     * @param \customfield_number\hook\add_custom_providers $hook
     */
    public static function register(\customfield_number\hook\add_custom_providers $hook): void {
        $hook->add_provider(new static($hook->field));
    }

    /**
     * Provider name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('ratinglabel', 'tool_courserating');
    }

    /**
     * If provide is available for the current field.
     *
     * @return bool
     */
    public function is_available(): bool {
        return $this->field->get_handler()->get_component() === 'core_course' &&
            $this->field->get_handler()->get_area() === 'course' &&
            api::number_field_supported();
    }

    /**
     * Add autocomplete field for selecting activity type.
     * Also add checkbox to display the field when the number of activities is zero.
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form_definition(MoodleQuickForm $mform): void {
        $mform->hideIf('configdata[decimalplaces]', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('configdata[display]', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('str_display_format', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('configdata[defaultvalue]', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('configdata[minimumvalue]', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('configdata[maximumvalue]', 'configdata[fieldtype]', 'eq', get_class($this));
        $mform->hideIf('configdata[displaywhenzero]', 'configdata[fieldtype]', 'eq', get_class($this));
    }

    /**
     * Recalculate the number of activities in the course.
     *
     * @param int|null $instanceid
     */
    public function recalculate(?int $instanceid = null): void {
        global $DB, $PAGE;

        api::reindex($instanceid ?: 0);
    }

    /**
     * How the field should be displayed
     *
     * Called from {@see field_controller::prepare_field_for_display()}
     * The return value may contain safe HTML but all user input must be passed through
     * format_string/format_text functions
     *
     * @param mixed $value String or float
     * @param \context|null $context Context
     * @return ?string null if the field should not be displayed or string representation of the field
     */
    public function prepare_export_value(mixed $value, \context|null $context = null): string|null {
        global $PAGE;
        if ($value === null) {
            return null;
        }

        // A little trick, we need to find the full instance of data_controller that called this method.
        // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $data = $trace[2]['object'] ?? null;
        if ($data && ($data instanceof data_controller) && $data->get('fieldid') == $this->field->get('id')) {
            return $data->get('value');
        } else if ($context && ($context instanceof \context_course)) {
            // If we can't find it, retrieve from the field and context instance (courseid).
            $courseid = $context->instanceid;
            $fieldid = $this->field->get('id');
            $data = \core_customfield\api::get_instance_fields_data(
                [$fieldid => $this->field], $courseid)[$fieldid];
            return $data->get('value');
        }

        // Fallback.
        return (string)format_float($value, 1);
    }
}
