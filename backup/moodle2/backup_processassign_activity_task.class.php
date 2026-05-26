<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/backup/moodle2/backup_processassign_stepslib.php');

class backup_processassign_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_processassign_activity_structure_step('processassign_structure', 'processassign.xml'));
    }

    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/(" . $base . "\/mod\/processassign\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROCESSASSIGNINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/processassign\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROCESSASSIGNVIEWBYID*$2@$', $content);

        return $content;
    }
}
