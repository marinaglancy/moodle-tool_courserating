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

/**
 * Function to upgrade tool_courserating.
 *
 * @package     tool_courserating
 * @category    upgrade
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade tool_courserating.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_tool_courserating_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022111300) {

        // Define index avgrating (not unique) to be dropped form tool_courserating_summary.
        $table = new xmldb_table('tool_courserating_summary');
        $index = new xmldb_index('avgrating', XMLDB_INDEX_NOTUNIQUE, ['avgrating']);

        // Conditionally launch drop index avgrating.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Changing nullability of field avgrating on table tool_courserating_summary to null.
        $table = new xmldb_table('tool_courserating_summary');
        $field = new xmldb_field('avgrating', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'cntall');

        // Launch change of nullability for field avgrating.
        $dbman->change_field_notnull($table, $field);

        $DB->execute('UPDATE {tool_courserating_summary} SET avgrating = NULL WHERE cntall = 0');

        // Define index avgrating (not unique) to be added to tool_courserating_summary.
        $table = new xmldb_table('tool_courserating_summary');
        $index = new xmldb_index('avgrating', XMLDB_INDEX_NOTUNIQUE, ['avgrating']);

        // Conditionally launch add index avgrating.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Courserating savepoint reached.
        upgrade_plugin_savepoint(true, 2022111300, 'tool', 'courserating');
    }

    return true;
}
