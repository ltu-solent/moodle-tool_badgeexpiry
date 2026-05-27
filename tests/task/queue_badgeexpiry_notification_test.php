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

use core_badges_generator;
use core\user;

/**
 * Tests for Badge expiry notifications
 *
 * @package    tool_badgeexpiry
 * @category   test
 * @covers     \tool_badgeexpiry\task\queue_badgeexpiry_notification_tasks
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class queue_badgeexpiry_notification_test extends \advanced_testcase {
    /**
     * Test a recipient is still active and has access to the course to do anything about an expired badge
     *
     * @param string $userstatus The status of the user (active, suspended, deleted).
     * @param bool $enrolled Whether the user is enrolled in the course or not.
     * @param bool $expectnotification Whether we expect a notification to be sent or not.
     * @return void
     * @dataProvider provider_test_queue_badgeexpiry_notification_tasks
     */
    public function test_queue_badgeexpiry_notification_tasks(string $userstatus, bool $enrolled, bool $expectnotification): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enablebadges = true;
        $now = time();
        set_config('enabled', true, 'tool_badgeexpiry');
        set_config('expiredsince', 0, 'tool_badgeexpiry');

        $course = $this->getDataGenerator()->create_course();
        /** @var core_badges_generator $bgen */
        $bgen = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $badge = $bgen->create_badge(['courseid' => $course->id, 'expireperiod' => 600]);
        $recipient = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($recipient->id, $course->id);
        // Issue the badge before suspending, deleting or unenrolling the user.
        $bgen->create_issued_badge([
            'badgeid' => $badge->id,
            'userid' => $recipient->id,
        ]);
        // Need to manually set the date issued to be in the past to trigger the expiry.
        $issued = $DB->get_record('badge_issued', ['badgeid' => $badge->id, 'userid' => $recipient->id]);
        $issued->dateissued = $now - 3000;
        $DB->update_record('badge_issued', $issued);

        if ($userstatus === 'suspended') {
            $recipient->suspended = 1;
            user_update_user($recipient, false, false);
        }
        if ($userstatus === 'deleted') {
            user_delete_user($recipient);
        }
        if (!$enrolled) {
            /** @var \enrol_manual_plugin $manplugin */
            $manplugin = enrol_get_plugin('manual');
            $manualinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
            $manplugin->unenrol_user($manualinstance, $recipient->id);
        }
        $task = new queue_badgeexpiry_notification_tasks();
        $task->execute();

        $records = $DB->get_records('task_adhoc', ['component' => 'tool_badgeexpiry']);
        if ($expectnotification) {
            $this->assertCount(1, $records, "Expected a notification task to be queued for a $userstatus user who is " .
                ($enrolled ? "enrolled" : "not enrolled") . " in the course.");
            $this->expectOutputString("Queued 1 badge expiry notification tasks.\n");
        } else {
            $this->assertCount(0, $records, "Expected no notification task to be queued for a $userstatus user who is " .
                ($enrolled ? "enrolled" : "not enrolled") . " in the course.");
            $this->expectOutputString("No expired badges found to notify.\n");
        }
    }

    /**
     * Queue badge expire provider
     *
     * @return array
     */
    public static function provider_test_queue_badgeexpiry_notification_tasks(): array {
        return [
            'active user' => [
                'userstatus' => 'active',
                'enrolled' => true,
                'expectnotification' => true,
            ],
            'suspended user' => [
                'userstatus' => 'suspended',
                'enrolled' => true,
                'expectnotification' => false,
            ],
            'deleted user' => [
                'userstatus' => 'deleted',
                'enrolled' => true,
                'expectnotification' => false,
            ],
            'unenrolled user' => [
                'userstatus' => 'active',
                'enrolled' => false,
                'expectnotification' => false,
            ],
        ];
    }
}
