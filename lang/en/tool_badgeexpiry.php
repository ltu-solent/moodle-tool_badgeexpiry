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
 * English language pack for tool_badgeexpiry
 *
 * @package    tool_badgeexpiry
 * @category   string
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



$string['enablebadgeexpiry'] = 'Enable badge expiry notifications';
$string['enablebadgeexpiry_desc'] = 'If enabled, users will receive notifications when their badges have expired.';

$string['messageprovider:badge_expiry_notification'] = 'Badge expiry notification';

$string['notificationmessage'] = '<p>Hello {$a->recipient},</p>
<p>Your badge "{$a->badgelink}" on {$a->courselink} expired on {$a->expirydate}.</p>
<p>It may be there is updated badge available for you. Please check the course "{$a->courselink}" for more details.</p>';
$string['notificationsubject'] = 'Your badge "{$a->badgename}" has expired';

$string['pluginname'] = 'Badge expiry notifications';

$string['queuetaskname'] = 'Queue badge expiry notification tasks';
