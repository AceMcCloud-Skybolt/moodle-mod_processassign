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
 * Test data generator for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/processassign/lib.php');

class mod_processassign_generator extends testing_module_generator {

    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;

        $defaults = [
            'name' => 'Process assignment',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'activity' => '',
            'activityformat' => FORMAT_HTML,
            'grade' => 100,
            'alwaysshowdescription' => 1,
            'sendnotifications' => 0,
            'sendstudentnotifications' => 1,
            'submissiondrafts' => 0,
            'requiresubmissionstatement' => 0,
            'requirefeedbackresponse' => 0,
            'maxattempts' => 1,
            'attemptreopenmethod' => 'manual',
            'gradebookmode' => 'single',
            'stagecount' => 3,
        ];

        foreach ($defaults as $field => $value) {
            if (!isset($record->{$field})) {
                $record->{$field} = $value;
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $stagedefaults = [
                "stage{$i}type" => $i === 1 ? 'proposal' : ($i === 2 ? 'draft' : 'final'),
                "stage{$i}name" => $i <= $record->stagecount ? "Stage {$i}" : '',
                "stage{$i}instructions" => "Instructions for stage {$i}",
                "stage{$i}maxgrade" => $i === 1 ? 10 : ($i === 2 ? 30 : 60),
                "stage{$i}duedate" => 0,
                "stage{$i}timelimit" => 0,
                "stage{$i}submissiononlinetext" => 1,
                "stage{$i}submissionfile" => 0,
                "stage{$i}maxfiles" => 5,
                "stage{$i}maxbytes" => 0,
                "stage{$i}acceptedfiletypes" => '*',
                "stage{$i}wordlimitenabled" => 0,
                "stage{$i}wordlimit" => 0,
                "stage{$i}requirefeedbackresponse" => 0,
            ];

            foreach ($stagedefaults as $field => $value) {
                if (!isset($record->{$field})) {
                    $record->{$field} = $value;
                }
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    public function create_stage_submission(array $record): stdClass {
        global $DB;

        $now = time();
        $record = (object)$record;
        $record->submissiontext = $record->submissiontext ?? '';
        $record->submissionformat = $record->submissionformat ?? FORMAT_HTML;
        $record->feedback = $record->feedback ?? '';
        $record->feedbackformat = $record->feedbackformat ?? FORMAT_HTML;
        $record->feedbackresponse = $record->feedbackresponse ?? '';
        $record->feedbackresponseformat = $record->feedbackresponseformat ?? FORMAT_HTML;
        $record->status = $record->status ?? PROCESSASSIGN_STATUS_SUBMITTED;
        $record->graderid = $record->graderid ?? 0;
        $record->timecreated = $record->timecreated ?? $now;
        $record->timemodified = $record->timemodified ?? $now;
        $record->timesubmitted = $record->timesubmitted ?? $now;
        $record->timegraded = $record->timegraded ?? 0;
        $record->timefeedbackresponded = $record->timefeedbackresponded ?? 0;

        $record->id = $DB->insert_record('processassign_subs', $record);
        return $record;
    }
}
