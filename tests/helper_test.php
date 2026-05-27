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

use core_badges_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/badgeslib.php");

/**
 * Tests for tool_badgeexpiry
 *
 * @package    tool_badgeexpiry
 * @category   test
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helper_test extends \advanced_testcase {
    /**
     * Test get_expired_badges returns empty when badges are disabled.
     * @covers \tool_badgeexpiry\helper::get_expired_badges
     */
    public function test_get_expired_badges_disabled(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablebadges = false;
        $result = helper::get_expired_badges();
        $this->assertEmpty($result, 'Expected no expired badges when badges are disabled.');
    }

    /**
     * Test get_expired_badges returns empty when notifications are disabled.
     * @covers \tool_badgeexpiry\helper::get_expired_badges
     */
    public function test_get_expired_badges_notifications_disabled(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablebadges = true;
        set_config('enabled', false, 'tool_badgeexpiry');
        // Add a badge that would be expired to check that it is not returned when notifications are disabled.
        $course = self::getDataGenerator()->create_course();
        /** @var core_badges_generator $bgen */
        $bgen = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $badge = $bgen->create_badge(['courseid' => $course->id, 'expiredate' => time() - 3600, 'expireperiod' => 0]);
        $recipient = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($recipient->id, $course->id);
        $bgen->create_issued_badge([
            'badgeid' => $badge->id,
            'userid' => $recipient->id,
        ]);
        $result = helper::get_expired_badges();
        $this->assertEmpty($result, 'Expected no expired badges when notifications are disabled.');
    }

    /**
     * Get expired badges
     *
     * @dataProvider get_expired_badges_provider
     * @covers \tool_badgeexpiry\helper::get_expired_badges
     * @param int $expiredate
     * @param int $expireperiod
     * @param int $expectedcount
     * @param int $adjusttime Adjust the issuedsince time by this amount to test expire period.
     * This is needed as the badge is issued at now time when created in test.
     *
     * @return void
     */
    public function test_get_expired_badges(int $expiredate, int $expireperiod, int $expectedcount, int $adjusttime): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enablebadges = true;
        set_config('enabled', true, 'tool_badgeexpiry');
        set_config('expiredsince', time() + $adjusttime, 'tool_badgeexpiry');

        // Create a course and badge.
        $course = self::getDataGenerator()->create_course();
        /** @var core_badges_generator $bgen */
        $bgen = $this->getDataGenerator()->get_plugin_generator('core_badges');

        // This isn't checking the actual badges and criteria.
        $badge = $bgen->create_badge(['courseid' => $course->id, 'expiredate' => $expiredate, 'expireperiod' => $expireperiod]);
        $recipient = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($recipient->id, $course->id);
        // Badge is always issued at now time.
        $bgen->create_issued_badge([
            'badgeid' => $badge->id,
            'userid' => $recipient->id,
        ]);

        $result = helper::get_expired_badges();
        $this->assertCount($expectedcount, $result, 'Unexpected number of expired badges returned.');
    }

    /**
     * Get expired badges provider
     *
     * @return array
     */
    public static function get_expired_badges_provider(): array {
        return [
            'expired badge by date' => [
                'expiredate' => time() - 1800, // Fixed 30 minutes ago.
                'expireperiod' => 0,
                'expectedcount' => 1,
                'adjusttime' => 0,
            ],
            'expired badge by period' => [
                'expiredate' => 0,
                'expireperiod' => 1800, // Relative 30 minutes from date issued.
                'expectedcount' => 1,
                'adjusttime' => 0,
            ],
            'not expired badge' => [
                'expiredate' => time() + 1800, // 30 minutes in the future
                'expireperiod' => 0,
                'expectedcount' => 0,
                'adjusttime' => 0,
            ],
            'not expired badge by period' => [
                'expiredate' => 0,
                'expireperiod' => 1800, // Relative 30 minutes from date issued.
                'expectedcount' => 0,
                'adjusttime' => 3600, // Move date last checked forward 1 hour to make badge not expired by period.
            ],
            'badge issued before expired since time' => [
                'expiredate' => time(), // Now.
                'expireperiod' => 0,
                'expectedcount' => 0,
                'adjusttime' => 7200, // Expired since some future time.
            ],
        ];
    }
}
