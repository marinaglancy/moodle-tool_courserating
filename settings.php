<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     tool_courserating
 * @category    admin
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // "courserequests" settingpage.
    $temp = new admin_settingpage('tool_courserating', new lang_string('pluginname', 'tool_courserating'));
    $options = [
        \tool_courserating\constants::RATEBY_NOONE => new lang_string('ratebynoone', 'tool_courserating'),
        \tool_courserating\constants::RATEBY_ANYTIME => new lang_string('ratebyanybody', 'tool_courserating'),
        \tool_courserating\constants::RATEBY_COMPLETED => new lang_string('ratebycompleted', 'tool_courserating'),
    ];
    $temp->add(new admin_setting_configselect('tool_courserating/' . \tool_courserating\constants::SETTING_RATEDCOURSES,
        new lang_string('ratedcourses', 'tool_courserating'),
        new lang_string('ratedcoursesconfig', 'tool_courserating'),
        \tool_courserating\constants::RATEBY_ANYTIME,
        $options));

    $el = new admin_setting_configcheckbox('tool_courserating/' . \tool_courserating\constants::SETTING_PERCOURSE,
        new lang_string('percourseoverride', 'tool_courserating'),
        new lang_string('percourseoverrideconfig', 'tool_courserating'), 0);
    $el->set_updatedcallback('tool_courserating\task\reindex::schedule');
    $temp->add($el);

    $el = new admin_setting_configcolourpicker('tool_courserating/' . \tool_courserating\constants::SETTING_STARCOLOR,
        new lang_string('colorstar', 'tool_courserating'),
        '', \tool_courserating\constants::SETTING_STARCOLOR_DEFAULT);
    $el->set_updatedcallback('tool_courserating\task\reindex::schedule');
    $temp->add($el);

    $el = new admin_setting_configcolourpicker('tool_courserating/' . \tool_courserating\constants::SETTING_RATINGCOLOR,
        new lang_string('colorrating', 'tool_courserating'),
        new lang_string('colorratingconfig', 'tool_courserating'),
        \tool_courserating\constants::SETTING_RATINGCOLOR_DEFAULT);
    $el->set_updatedcallback('tool_courserating\task\reindex::schedule');
    $temp->add($el);

    $el = new admin_setting_configcheckbox('tool_courserating/' . \tool_courserating\constants::SETTING_DISPLAYEMPTY,
        new lang_string('displayempty', 'tool_courserating'),
        new lang_string('displayemptyconfig', 'tool_courserating'), 0);
    $el->set_updatedcallback('tool_courserating\task\reindex::schedule');
    $temp->add($el);

    $temp->add(new admin_setting_description('tool_courserating/description',
        '',
        new lang_string('settingsdescription', 'tool_courserating')));

    $ADMIN->add('courses', $temp);
}
