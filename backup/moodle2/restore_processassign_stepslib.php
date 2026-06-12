<?php
// This file is part of Moodle - http://moodle.org/

class restore_processassign_activity_structure_step extends restore_activity_structure_step {

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

    protected function process_processassign($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->gradecategoryid = 0;
        $data->allowsubmissionsfromdate = $this->apply_date_offset($data->allowsubmissionsfromdate ?? 0);
        $data->duedate = $this->apply_date_offset($data->duedate ?? 0);
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate ?? 0);
        $data->gradingduedate = $this->apply_date_offset($data->gradingduedate ?? 0);

        $newitemid = $DB->insert_record('processassign', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_processassign_stage($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->processassignid = $this->get_new_parentid('processassign');
        $data->duedate = $this->apply_date_offset($data->duedate);

        $newitemid = $DB->insert_record('processassign_stages', $data);
        $this->set_mapping('processassign_stage', $oldid, $newitemid);
    }

    protected function process_processassign_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->stageid = $this->get_new_parentid('processassign_stage');
        $data->processassignid = $this->get_new_parentid('processassign');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);

        $newitemid = $DB->insert_record('processassign_subs', $data);
        $this->set_mapping('processassign_submission', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        global $CFG, $DB;

        $this->add_related_files('mod_processassign', 'intro', null);
        $this->add_related_files('mod_processassign', 'submission', 'processassign_submission');
        $this->add_related_files('mod_processassign', 'feedback', 'processassign_submission');

        require_once($CFG->dirroot . '/mod/processassign/lib.php');
        if ($processassign = $DB->get_record('processassign', ['id' => $this->task->get_activityid()])) {
            processassign_update_grades($processassign);
        }
    }
}
