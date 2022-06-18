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

use tool_courserating\api;
use tool_courserating\helper;

/**
 * Behat data generator for tool_courserating
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_courserating_generator extends behat_generator_base {

    /**
     * @var tool_courserating_generator
     */
    protected $componentdatagenerator;

    /**
     * Get a list of the entities that can be created for this component.
     *
     * See {@see behat_core_generator::get_creatable_entities} for an example.
     *
     * @return array entity name => information about how to generate.
     */
    protected function get_creatable_entities(): array {
        return [
            'ratings' => [
                'singular' => 'rating',
                'datagenerator' => 'rating',
                'required' => ['user', 'course', 'rating'],
                'switchids' => ['user' => 'userid', 'course' => 'courseid'],
            ],
        ];
    }

    /**
     * Adapter to enrol_user() data generator.
     *
     * @throws Exception
     * @param array $data
     * @return void
     */
    protected function process_rating($data) {
        $this->componentdatagenerator->create_rating((int)$data['userid'], (int)$data['courseid'],
            (int)$data['rating'], $data['review'] ?? '');
    }
}
