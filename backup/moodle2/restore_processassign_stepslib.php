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
 * Restore structure step for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/lib.php');

/**
 * Structure step to restore a processassign activity, its stages and submissions.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class restore_processassign_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the restore paths.
     *
     * @return restore_path_element[] the paths wrapped into the activity structure
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('processassign', '/activity/processassign');
        $paths[] = new restore_path_element('processassign_stage', '/activity/processassign/stages/stage');
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('processassign_submission',
                '/activity/processassign/stages/stage/submissions/submission');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore a processassign instance record.
     *
     * @param array $data the record data from the backup file
     */
    protected function process_processassign($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->gradecategoryid = 0;
        $data->allowsubmissionsfromdate = $this->apply_date_offset($data->allowsubmissionsfromdate ?? 0);
        $data->duedate = $this->apply_date_offset($data->duedate ?? 0);
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate ?? 0);
        $data->gradingduedate = $this->apply_date_offset($data->gradingduedate ?? 0);
        $data->timelimit = $data->timelimit ?? 0;
        $data->activity = $data->activity ?? '';
        $data->activityformat = $data->activityformat ?? FORMAT_HTML;
        $data->requirefeedbackresponse = $data->requirefeedbackresponse ?? 0;
        $data->timecreated = !empty($data->timecreated) ? $data->timecreated : ($data->timemodified ?? time());

        $newitemid = $DB->insert_record('processassign', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore a stage record.
     *
     * @param array $data the record data from the backup file
     */
    protected function process_processassign_stage($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->processassignid = $this->get_new_parentid('processassign');
        $data->duedate = $this->apply_date_offset($data->duedate);

        $newitemid = $DB->insert_record('processassign_stages', $data);
        $this->set_mapping('processassign_stage', $oldid, $newitemid);
    }

    /**
     * Restore a submission record.
     *
     * @param array $data the record data from the backup file
     */
    protected function process_processassign_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->stageid = $this->get_new_parentid('processassign_stage');
        $data->processassignid = $this->get_new_parentid('processassign');
        $data->userid = $this->get_mappingid('user', $data->userid);
        // Backups made before the gradedby -> graderid rename still carry the old name.
        if (!isset($data->graderid) && isset($data->gradedby)) {
            $data->graderid = $data->gradedby;
            unset($data->gradedby);
        }
        $data->graderid = $this->get_mappingid('user', $data->graderid ?? 0);

        $newitemid = $DB->insert_record('processassign_subs', $data);
        $this->set_mapping('processassign_submission', $oldid, $newitemid, true);
    }

    /**
     * Restore the related file areas and rebuild the gradebook entries.
     */
    protected function after_execute() {
        global $DB;

        $this->add_related_files('mod_processassign', 'intro', null);
        $this->add_related_files('mod_processassign', 'introattachment', null);
        $this->add_related_files('mod_processassign', 'activity', null);
        $this->add_related_files('mod_processassign', 'submission', 'processassign_submission');
        $this->add_related_files('mod_processassign', 'feedback', 'processassign_submission');

        if ($processassign = $DB->get_record('processassign', ['id' => $this->task->get_activityid()])) {
            processassign_update_grades($processassign);
        }
    }
}
