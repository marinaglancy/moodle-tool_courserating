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

/**
 * Plugin constants
 *
 * @package    tool_courserating
 * @copyright  2022 Marina Glancy <marina.glancy@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {
    /** @var int */
    const REVIEWS_PER_PAGE = 10;

    /** @var string */
    const CFIELD_RATING = 'tool_courserating';
    /** @var string */
    const CFIELD_RATINGMODE = 'tool_courserating_mode';

    /** @var int */
    const RATEBY_NOONE = 1;
    /** @var int */
    const RATEBY_ANYTIME = 2;
    /** @var int */
    const RATEBY_COMPLETED = 3;

    /** @var string */
    const SETTING_RATINGMODE = 'ratingmode';
    /** @var string */
    const SETTING_PERCOURSE = 'percourse';
    /** @var string */
    const SETTING_STARCOLOR = 'starcolor';
    /** @var string */
    const SETTING_RATINGCOLOR = 'ratingcolor';
    /** @var string */
    const SETTING_DISPLAYEMPTY = 'displayempty';
    /** @var string */
    const SETTING_USEHTML = 'usehtml';
    /** @var string */
    const SETTING_PARENTCSS = 'parentcss';

    /** @var string */
    const SETTING_STARCOLOR_DEFAULT = '#e59819';
    /** @var string */
    const SETTING_RATINGCOLOR_DEFAULT = '#b4690e';

    /** @var string */
    const COLOR_GRAY = '#a0a0a0';

    /**
     * List of options for the 'ratingmode' selector
     *
     * @return \lang_string[]
     */
    public static function rated_courses_options() {
        return [
            self::RATEBY_NOONE => new \lang_string('ratebynoone', 'tool_courserating'),
            self::RATEBY_ANYTIME => new \lang_string('ratebyanybody', 'tool_courserating'),
            self::RATEBY_COMPLETED => new \lang_string('ratebycompleted', 'tool_courserating'),
        ];
    }
}
