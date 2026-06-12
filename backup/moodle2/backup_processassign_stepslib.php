<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class backup_processassign_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $processassign = new backup_nested_element('processassign', ['id'], [
            'course', 'name', 'intro', 'introformat', 'activity', 'activityformat', 'grade',
            'allowsubmissionsfromdate', 'duedate', 'cutoffdate', 'gradingduedate', 'timelimit',
            'alwaysshowdescription', 'submissiononlinetext', 'submissionfile', 'maxfiles', 'maxbytes',
            'feedbackcomments', 'feedbackfiles', 'feedbackmaxfiles', 'feedbackmaxbytes',
            'wordlimitenabled', 'wordlimit', 'sendnotifications', 'sendstudentnotifications', 'submissiondrafts',
            'requiresubmissionstatement', 'requirefeedbackresponse', 'maxattempts', 'attemptreopenmethod',
            'gradebookmode', 'timemodified',
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
            'feedbackresponse', 'feedbackresponseformat', 'status', 'gradedby', 'timecreated', 'timemodified',
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
            $submission->annotate_ids('user', 'gradedby');
            $submission->annotate_files('mod_processassign', 'submission', 'id');
            $submission->annotate_files('mod_processassign', 'feedback', 'id');
        }

        $processassign->annotate_files('mod_processassign', 'intro', null);
        $processassign->annotate_files('mod_processassign', 'introattachment', null);
        $processassign->annotate_files('mod_processassign', 'activity', null);

        return $this->prepare_activity_structure($processassign);
    }
}
