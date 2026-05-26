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
 * Install script for tool_badgeexpiry
 *
 * @package    tool_badgeexpiry
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executed on installation of tool_badgeexpiry
 *
 * @return bool
 */
function xmldb_tool_badgeexpiry_install() {
    // Only send notifications for badges expired since the installation of the plugin.
    if (get_config('tool_badgeexpiry', 'expiredsince') === false) {
        set_config('expiredsince', time(), 'tool_badgeexpiry');
    }
    return true;
}
