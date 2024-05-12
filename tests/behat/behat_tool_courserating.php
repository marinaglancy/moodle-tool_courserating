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

use Behat\Gherkin\Node\TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Behat steps for tool_courserating
 *
 * @package     tool_courserating
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_courserating extends behat_base {


    /**
     * Return the list of partial named selectors.
     *
     * @return array
     */
    public static function get_partial_named_selectors(): array {
        return [
            new behat_component_named_selector('Review', [
                <<<XPATH
    .//div[@data-for='tool_courserating-user-rating' and contains(string(), %locator%)]
XPATH
,
            ], true),
            new behat_component_named_selector('Coursebox', [
                <<<XPATH
    //div[contains(concat(' ', @class, ' '), ' coursebox ') and
        contains(.//div[contains(concat(' ', @class, ' '), ' info ')]/., %locator%)]
XPATH
,
            ], true),
        ];
    }

    /**
     * Checks if given plugin is installed, and skips the current scenario if not.
     *
     * @Given reportbuilder is available for tool_courserating
     * @throws \Moodle\BehatExtension\Exception\SkippedException
     */
    public function reportbuilder_is_available(): void {
        $path = core_component::get_component_directory('core_reportbuilder');
        if (!$path) {
            throw new \Moodle\BehatExtension\Exception\SkippedException(
                'Skipping this scenario because the reportbuilder is not avialable.');
        }
    }
}
