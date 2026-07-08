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
 * Privacy provider for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

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

/**
 * Privacy provider for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the personal data stored by the plugin.
     *
     * @param collection $items the metadata collection to add to
     * @return collection the updated collection
     */
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

    /**
     * Get the contexts that contain personal data for the given user.
     *
     * @param int $userid the user id
     * @return contextlist the contexts containing the user's data
     */
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

    /**
     * Add the users who have data in the given context to the userlist.
     *
     * @param userlist $userlist the userlist to add to
     */
    public static function get_users_in_context(userlist $userlist): void {
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

    /**
     * Export the user's stage submissions, grades and feedback.
     *
     * @param approved_contextlist $contextlist the approved contexts to export from
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
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

    /**
     * Delete all user data in the given context.
     *
     * @param \context $context the context to purge
     */
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

    /**
     * Delete the user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist the approved contexts to delete from
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
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

    /**
     * Delete data for the approved users in a single context.
     *
     * @param approved_userlist $userlist the approved users and context
     */
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

    /**
     * Delete the submission and feedback files for a set of submissions.
     *
     * @param \context_module $context the module context
     * @param array $submissionids ids of the submissions whose files should be deleted
     */
    protected static function delete_submission_files(\context_module $context, array $submissionids): void {
        $fs = get_file_storage();
        foreach ($submissionids as $submissionid) {
            $fs->delete_area_files($context->id, 'mod_processassign', 'submission', $submissionid);
            $fs->delete_area_files($context->id, 'mod_processassign', 'feedback', $submissionid);
        }
    }
}
