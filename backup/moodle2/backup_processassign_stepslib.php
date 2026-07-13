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
 * Backup structure step for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete processassign structure for backup, with optional user info.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class backup_processassign_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the backup structure: instance, stages and (optionally) submissions.
     *
     * @return backup_nested_element the wrapped structure
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $processassign = new backup_nested_element('processassign', ['id'], [
            'course', 'name', 'intro', 'introformat', 'activity', 'activityformat', 'grade',
            'allowsubmissionsfromdate', 'duedate', 'cutoffdate', 'gradingduedate', 'timelimit',
            'alwaysshowdescription', 'submissiononlinetext', 'submissionfile', 'maxfiles', 'maxbytes',
            'feedbackcomments', 'feedbackfiles', 'feedbackmaxfiles', 'feedbackmaxbytes',
            'wordlimitenabled', 'wordlimit', 'sendnotifications', 'sendstudentnotifications', 'submissiondrafts',
            'requiresubmissionstatement', 'requirefeedbackresponse', 'maxattempts', 'attemptreopenmethod',
            'gradebookmode', 'timecreated', 'timemodified',
        ]);

        $stages = new backup_nested_element('stages');
        $stage = new backup_nested_element('stage', ['id'], [
            'sortorder', 'stagetype', 'name', 'instructions', 'instructionsformat', 'maxgrade', 'duedate',
            'timelimit', 'submissiononlinetext', 'submissionfile', 'maxfiles', 'maxbytes', 'acceptedfiletypes',
            'wordlimitenabled', 'wordlimit', 'requirefeedbackresponse', 'releasegrade',
            'releasefeedback', 'timecreated', 'timemodified',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'submissiontext', 'submissionformat', 'grade', 'feedback', 'feedbackformat',
            'feedbackresponse', 'feedbackresponseformat', 'status', 'graderid', 'timecreated', 'timemodified',
            'timesubmitted', 'timegraded', 'timefeedbackresponded',
        ]);

        $processassign->add_child($stages);
        $stages->add_child($stage);
        if ($userinfo) {
            $stage->add_child($submissions);
            $submissions->add_child($submission);
        }

        $processassign->set_source_table('processassign', ['id' => backup::VAR_ACTIVITYID]);
        $stage->set_source_table('processassign_stages', ['processassignid' => backup::VAR_PARENTID], 'sortorder ASC');
        if ($userinfo) {
            $submission->set_source_table('processassign_subs', ['stageid' => backup::VAR_PARENTID]);
            $submission->annotate_ids('user', 'userid');
            $submission->annotate_ids('user', 'graderid');
            $submission->annotate_files('mod_processassign', 'submission', 'id');
            $submission->annotate_files('mod_processassign', 'feedback', 'id');
        }

        $processassign->annotate_files('mod_processassign', 'intro', null);
        $processassign->annotate_files('mod_processassign', 'introattachment', null);
        $processassign->annotate_files('mod_processassign', 'activity', null);

        return $this->prepare_activity_structure($processassign);
    }
}
