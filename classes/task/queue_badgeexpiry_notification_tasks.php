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

namespace tool_badgeexpiry\task;

use core_user;
use tool_badgeexpiry\helper;

/**
 * Class queue_badgeexpiry_notification_tasks
 *
 * @package    tool_badgeexpiry
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_badgeexpiry_notification_tasks extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('queuetaskname', 'tool_badgeexpiry');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        $enabled = get_config('tool_badgeexpiry', 'enabled');
        if (!$enabled) {
            mtrace('Badge expiry notifications are disabled.');
            // Update the expiredsince config to now to avoid sending notifications
            // for badges that expired while the setting was disabled.
            set_config('expiredsince', time(), 'tool_badgeexpiry');
            return;
        }
        // Get all badge expiry records that have not been notified yet.
        $records = helper::get_expired_badges();
        set_config('expiredsince', time(), 'tool_badgeexpiry');
        $count = count($records);
        if ($count == 0) {
            mtrace('No expired badges found to notify.');
            return;
        }
        $qcount = 0;
        foreach ($records as $record) {
            // Only notify users if they are still active.
            $user = core_user::get_user($record->userid);
            if (!$user || $user->suspended || $user->deleted) {
                continue;
            }
            // Only notify users if they are enrolled on the course.
            $ucourses  = enrol_get_users_courses($record->userid, true);
            if (!array_key_exists($record->courseid, $ucourses)) {
                continue;
            }
            // Queue a notification task for each record.
            $task = new badgeexpiry_notification_task();
            $task->set_custom_data([
                'userid' => $record->userid,
                'badgeid' => $record->badgeid,
            ]);
            \core\task\manager::queue_adhoc_task($task);
            $qcount++;
        }
        if ($qcount == 0) {
            mtrace('No expired badges found to notify.');
            return;
        }
        mtrace("Queued $qcount badge expiry notification tasks.");
    }
}
