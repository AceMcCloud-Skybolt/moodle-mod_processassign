<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for the Process Assignment privacy provider.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace mod_processassign\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/processassign/lib.php');

final class provider_test extends \core_privacy\tests\provider_testcase {

    protected function create_processassign_with_submission(): array {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_processassign');
        $processassign = $generator->create_instance(['course' => $course->id, 'stagecount' => 1]);
        $context = \context_module::instance($processassign->cmid);
        $stage = array_values($this->get_stages($processassign))[0];

        $generator->create_stage_submission([
            'processassignid' => $processassign->id,
            'stageid' => $stage->id,
            'userid' => $student->id,
            'submissiontext' => 'Student process evidence',
            'status' => PROCESSASSIGN_STATUS_GRADED,
            'grade' => 80,
            'feedback' => 'Useful feedback',
        ]);
        $generator->create_stage_submission([
            'processassignid' => $processassign->id,
            'stageid' => $stage->id,
            'userid' => $otherstudent->id,
            'submissiontext' => 'Other student evidence',
            'status' => PROCESSASSIGN_STATUS_SUBMITTED,
        ]);

        return [$processassign, $context, $student, $otherstudent];
    }

    protected function get_stages(\stdClass $processassign): array {
        global $DB;
        return $DB->get_records('processassign_stages',
            ['processassignid' => $processassign->id], 'sortorder ASC');
    }

    public function test_get_contexts_for_userid_returns_process_assignment_context(): void {
        $this->resetAfterTest();

        [, $context, $student] = $this->create_processassign_with_submission();

        $contextlist = provider::get_contexts_for_userid($student->id);

        $this->assertContains((int)$context->id, $contextlist->get_contextids());
    }

    public function test_get_users_in_context_returns_submission_users(): void {
        $this->resetAfterTest();

        [, $context, $student, $otherstudent] = $this->create_processassign_with_submission();
        $userlist = new userlist($context, 'mod_processassign');

        provider::get_users_in_context($userlist);

        $this->assertEqualsCanonicalizing([$student->id, $otherstudent->id], $userlist->get_userids());
    }

    public function test_delete_data_for_user_removes_only_that_users_submissions(): void {
        global $DB;

        $this->resetAfterTest();

        [$processassign, $context, $student, $otherstudent] = $this->create_processassign_with_submission();
        $contextlist = new approved_contextlist($student, 'mod_processassign', [$context->id]);

        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('processassign_subs', [
            'processassignid' => $processassign->id,
            'userid' => $student->id,
        ]));
        $this->assertTrue($DB->record_exists('processassign_subs', [
            'processassignid' => $processassign->id,
            'userid' => $otherstudent->id,
        ]));
    }

    public function test_delete_data_for_users_removes_approved_users_only(): void {
        global $DB;

        $this->resetAfterTest();

        [$processassign, $context, $student, $otherstudent] = $this->create_processassign_with_submission();
        $userlist = new approved_userlist($context, 'mod_processassign', [$otherstudent->id]);

        provider::delete_data_for_users($userlist);

        $this->assertTrue($DB->record_exists('processassign_subs', [
            'processassignid' => $processassign->id,
            'userid' => $student->id,
        ]));
        $this->assertFalse($DB->record_exists('processassign_subs', [
            'processassignid' => $processassign->id,
            'userid' => $otherstudent->id,
        ]));
    }
}
