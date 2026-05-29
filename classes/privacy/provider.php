<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_processassign\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'processassign_subs',
            [
                'processassignid' => 'privacy:metadata:processassign_subs:processassignid',
                'stageid' => 'privacy:metadata:processassign_subs:stageid',
                'userid' => 'privacy:metadata:processassign_subs:userid',
                'submissiontext' => 'privacy:metadata:processassign_subs:submissiontext',
                'grade' => 'privacy:metadata:processassign_subs:grade',
                'feedback' => 'privacy:metadata:processassign_subs:feedback',
                'feedbackresponse' => 'privacy:metadata:processassign_subs:feedbackresponse',
                'gradedby' => 'privacy:metadata:processassign_subs:gradedby',
                'timesubmitted' => 'privacy:metadata:processassign_subs:timesubmitted',
                'timegraded' => 'privacy:metadata:processassign_subs:timegraded',
                'timefeedbackresponded' => 'privacy:metadata:processassign_subs:timefeedbackresponded',
            ],
            'privacy:metadata:processassign_subs'
        );
        $items->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        return $items;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {processassign} pa ON pa.id = cm.instance
                  JOIN {processassign_subs} s ON s.processassignid = pa.id
                 WHERE s.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'processassign',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT s.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {processassign} pa ON pa.id = cm.instance
                  JOIN {processassign_subs} s ON s.processassignid = pa.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, [
            'cmid' => $context->instanceid,
            'modname' => 'processassign',
        ]);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $contextdata = helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], $contextdata);
            helper::export_context_files($context, $user);

            $cm = get_coursemodule_from_id('processassign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $sql = "SELECT s.*, st.name AS stagename
                      FROM {processassign_subs} s
                      JOIN {processassign_stages} st ON st.id = s.stageid
                     WHERE s.processassignid = :processassignid
                       AND s.userid = :userid
                  ORDER BY st.sortorder";
            $submissions = $DB->get_records_sql($sql, [
                'processassignid' => $cm->instance,
                'userid' => $user->id,
            ]);

            foreach ($submissions as $submission) {
                $subcontext = [get_string('submission', 'processassign'), format_string($submission->stagename)];
                $data = (object)[
                    'submissiontext' => $submission->submissiontext,
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'feedbackresponse' => $submission->feedbackresponse,
                    'status' => $submission->status,
                    'timesubmitted' => transform::datetime($submission->timesubmitted),
                    'timegraded' => transform::datetime($submission->timegraded),
                    'timefeedbackresponded' => transform::datetime($submission->timefeedbackresponded),
                ];
                writer::with_context($context)->export_data($subcontext, $data);
                writer::with_context($context)->export_area_files($subcontext, 'mod_processassign', 'submission',
                    $submission->id);
                writer::with_context($context)->export_area_files($subcontext, 'mod_processassign', 'feedback',
                    $submission->id);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('processassign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $submissions = $DB->get_records('processassign_subs', ['processassignid' => $cm->instance], '', 'id');
        self::delete_submission_files($context, array_keys($submissions));
        $DB->delete_records('processassign_subs', ['processassignid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('processassign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $submissions = $DB->get_records('processassign_subs',
                ['processassignid' => $cm->instance, 'userid' => $userid], '', 'id');
            self::delete_submission_files($context, array_keys($submissions));
            $DB->delete_records('processassign_subs', ['processassignid' => $cm->instance, 'userid' => $userid]);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('processassign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = ['processassignid' => $cm->instance] + $userparams;
        $submissions = $DB->get_records_select('processassign_subs',
            "processassignid = :processassignid AND userid {$usersql}", $params, '', 'id');
        self::delete_submission_files($context, array_keys($submissions));
        $DB->delete_records_select('processassign_subs',
            "processassignid = :processassignid AND userid {$usersql}", $params);
    }

    protected static function delete_submission_files(\context_module $context, array $submissionids): void {
        $fs = get_file_storage();
        foreach ($submissionids as $submissionid) {
            $fs->delete_area_files($context->id, 'mod_processassign', 'submission', $submissionid);
            $fs->delete_area_files($context->id, 'mod_processassign', 'feedback', $submissionid);
        }
    }
}
