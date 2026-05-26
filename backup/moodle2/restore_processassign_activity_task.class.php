<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/backup/moodle2/restore_processassign_stepslib.php');

class restore_processassign_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_processassign_activity_structure_step('processassign_structure', 'processassign.xml'));
    }

    public static function define_decode_contents() {
        return [
            new restore_decode_content('processassign', ['intro'], 'processassign'),
            new restore_decode_content('processassign_stages', ['instructions'], 'processassign_stage'),
            new restore_decode_content('processassign_subs', ['submissiontext', 'feedback', 'feedbackresponse'],
                'processassign_submission'),
        ];
    }

    public static function define_decode_rules() {
        return [
            new restore_decode_rule('PROCESSASSIGNVIEWBYID', '/mod/processassign/view.php?id=$1', 'course_module'),
            new restore_decode_rule('PROCESSASSIGNINDEX', '/mod/processassign/index.php?id=$1', 'course'),
        ];
    }

    public static function define_restore_log_rules() {
        return [];
    }

    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
