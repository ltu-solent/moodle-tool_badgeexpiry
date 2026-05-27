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

use core\output\html_writer;
use core\task\adhoc_task;
use core\url;
use core_badges\badge;

/**
 * Class badgeexpiry_notification_task
 *
 * @package    tool_badgeexpiry
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badgeexpiry_notification_task extends adhoc_task {
    /**
     * Execute the task.
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        if (empty($data->userid) || empty($data->badgeid)) {
            return;
        }

        // Send notification to user.
        self::send_badge_expiry_notification($data->userid, $data->badgeid);
    }

    /**
     * Send badge expiry notification to badge recipient
     *
     * @param int $userid
     * @param int $badgeid
     * @return void
     */
    private static function send_badge_expiry_notification(int $userid, int $badgeid): void {
        global $DB;
        // Get the badge and user information.
        $badge = new badge($badgeid);
        $user = \core_user::get_user($userid);
        $issued = $DB->get_record('badge_issued', ['badgeid' => $badgeid, 'userid' => $userid]);
        if (!$badge || !$user || !$issued) {
            return;
        }
        // Either the badge has a fixed expiry date or an expiry period from the date issued.
        $expirydate = $badge->calculate_expiry($issued->dateissued);
        $badgeurl = new url('/badges/badge.php', ['hash' => $issued->uniquehash]);
        $issuedlink = html_writer::link($badgeurl, clean_text($badge->name));
        $params = [
            'recipient' => fullname($user),
            'badgelink' => $issuedlink,
            'badgename' => clean_text($badge->name),
            'courselink' => html_writer::link(
                new url(
                    '/course/view.php',
                    ['id' => $badge->courseid]
                ),
                format_string($DB->get_field('course', 'fullname', ['id' => $badge->courseid]))
            ),
            'expirydate' => userdate($expirydate, get_string('strftimedate', 'langconfig')),
        ];
        $body = get_string('notificationmessage', 'tool_badgeexpiry', (object)$params);
        $subject = get_string('notificationsubject', 'tool_badgeexpiry', (object)$params);
        // Set up the message object using the core message API.
        $message = new \core\message\message();
        $message->component = 'tool_badgeexpiry';
        $message->contexturl = $badgeurl;
        $message->contexturlname = clean_text($badge->name);
        $message->courseid = is_null($badge->courseid) ? SITEID : $badge->courseid;
        $message->fullmessage = html_to_text($body);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $body;
        $message->name = 'badge_expiry_notification';
        $message->notification = 1;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->smallmessage = $subject;
        $message->subject = $subject;

        // Send the email notification to the user.
        message_send($message);
    }
}
