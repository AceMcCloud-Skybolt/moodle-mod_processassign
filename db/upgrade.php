<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

function xmldb_processassign_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052001) {
        $table = new xmldb_table('processassign');
        $fields = [
            new xmldb_field('allowsubmissionsfromdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade'),
            new xmldb_field('cutoffdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'allowsubmissionsfromdate'),
            new xmldb_field('alwaysshowdescription', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'cutoffdate'),
            new xmldb_field('submissiononlinetext', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'alwaysshowdescription'),
            new xmldb_field('submissionfile', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'submissiononlinetext'),
            new xmldb_field('maxfiles', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '5', 'submissionfile'),
            new xmldb_field('maxbytes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'maxfiles'),
            new xmldb_field('wordlimitenabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'maxbytes'),
            new xmldb_field('wordlimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'wordlimitenabled'),
            new xmldb_field('sendnotifications', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'wordlimit'),
            new xmldb_field('sendstudentnotifications', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'sendnotifications'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052001, 'processassign');
    }

    if ($oldversion < 2026052200) {
        $stagestable = new xmldb_table('processassign_stages');
        $stagefields = [
            new xmldb_field('stagetype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'custom', 'sortorder'),
            new xmldb_field('requirefeedbackresponse', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'duedate'),
        ];
        foreach ($stagefields as $field) {
            if (!$dbman->field_exists($stagestable, $field)) {
                $dbman->add_field($stagestable, $field);
            }
        }

        $submissiontable = new xmldb_table('processassign_subs');
        $submissionfields = [
            new xmldb_field('feedbackresponse', XMLDB_TYPE_TEXT, null, null, null, null, null, 'feedbackformat'),
            new xmldb_field('feedbackresponseformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0',
                'feedbackresponse'),
            new xmldb_field('timefeedbackresponded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'timegraded'),
        ];
        foreach ($submissionfields as $field) {
            if (!$dbman->field_exists($submissiontable, $field)) {
                $dbman->add_field($submissiontable, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052200, 'processassign');
    }

    if ($oldversion < 2026052201) {
        $stagestable = new xmldb_table('processassign_stages');
        $stagefields = [
            new xmldb_field('releasegrade', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1',
                'requirefeedbackresponse'),
            new xmldb_field('releasefeedback', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'releasegrade'),
        ];
        foreach ($stagefields as $field) {
            if (!$dbman->field_exists($stagestable, $field)) {
                $dbman->add_field($stagestable, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052201, 'processassign');
    }

    if ($oldversion < 2026052202) {
        $table = new xmldb_table('processassign');
        $fields = [
            new xmldb_field('gradebookmode', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'single',
                'sendstudentnotifications'),
            new xmldb_field('gradecategoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'gradebookmode'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052202, 'processassign');
    }

    if ($oldversion < 2026052203) {
        $table = new xmldb_table('processassign');
        $fields = [
            new xmldb_field('duedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'allowsubmissionsfromdate'),
            new xmldb_field('gradingduedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'cutoffdate'),
            new xmldb_field('submissiondrafts', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'sendstudentnotifications'),
            new xmldb_field('requiresubmissionstatement', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'submissiondrafts'),
            new xmldb_field('maxattempts', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '1',
                'requiresubmissionstatement'),
            new xmldb_field('attemptreopenmethod', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'manual',
                'maxattempts'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $stagestable = new xmldb_table('processassign_stages');
        $stagefields = [
            new xmldb_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'duedate'),
            new xmldb_field('wordlimitenabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'timelimit'),
            new xmldb_field('wordlimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'wordlimitenabled'),
        ];
        foreach ($stagefields as $field) {
            if (!$dbman->field_exists($stagestable, $field)) {
                $dbman->add_field($stagestable, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052203, 'processassign');
    }

    return true;
}
