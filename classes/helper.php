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

namespace tool_badgeexpiry;

use core_badges\badge;

/**
 * Class helper
 *
 * @package    tool_badgeexpiry
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Get expired issued badges
     *
     * @return array
     */
    public static function get_expired_badges(): array {
        global $CFG, $DB;
        if (empty($CFG->enablebadges)) {
            return [];
        }
        $enabled = get_config('tool_badgeexpiry', 'enabled');
        if (!$enabled) {
            return [];
        }
        $expiredsince = get_config('tool_badgeexpiry', 'expiredsince');
        $now = time();
        $params = [
            'expiredate' => $now,
            'expireperiod' => $now,
            'expiredsince' => $expiredsince,
        ];
        $sql = "SELECT b.id badgeid, bi.userid
        FROM {badge} b
            JOIN {badge_issued} bi ON bi.badgeid = b.id
            JOIN {course} c ON c.id = b.courseid AND c.visible = 1
            JOIN {user} u ON u.id = bi.userid AND u.suspended = 0 AND u.deleted = 0
        WHERE (b.expiredate < :expiredate OR (b.expireperiod + bi.dateissued < :expireperiod))
            AND (bi.dateissued >= :expiredsince)";

        return $DB->get_records_sql($sql, $params);
    }
}
