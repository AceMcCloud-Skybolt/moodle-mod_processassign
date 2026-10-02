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
 * Stage and submission data access and status logic.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_processassign\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/lib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Stage and submission data access and status logic.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stage_manager {
    /**
     * Fetch all stages for an instance ordered by sort order.
     *
     * @param int $processassignid the instance id
     * @return array stage records
     */
    public static function get_stages($processassignid) {
        global $DB;
        return $DB->get_records('processassign_stages', ['processassignid' => $processassignid], 'sortorder ASC');
    }

    /**
     * Fetch all submissions by a user for an instance.
     *
     * @param int $processassignid the instance id
     * @param int $userid the user id
     * @return array submission records
     */
    public static function get_student_submissions($processassignid, $userid) {
        global $DB;
        return $DB->get_records(
            'processassign_subs',
            ['processassignid' => $processassignid, 'userid' => $userid],
            '',
            '*',
            0,
            0
        );
    }

    /**
     * Find the submission for a given stage in a list of submissions.
     *
     * @param array $submissions submission records
     * @param int $stageid the stage id
     * @return \stdClass|null the submission, or null if not found
     */
    public static function get_submission_for_stage(array $submissions, int $stageid) {
        foreach ($submissions as $submission) {
            if ((int)$submission->stageid === $stageid) {
                return $submission;
            }
        }

        return null;
    }

    /**
     * Find a stage by id in a list of stages.
     *
     * @param array $stages stage records
     * @param int $stageid the stage id
     * @return \stdClass|null the stage, or null if not found
     */
    public static function get_stage_by_id(array $stages, int $stageid) {
        foreach ($stages as $stage) {
            if ((int)$stage->id === $stageid) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Return the localised status label for a submission.
     *
     * @param \stdClass|null $submission the submission record, or null if none
     * @return string the status label
     */
    public static function status_label($submission) {
        if (!$submission) {
            return get_string('notsubmitted', 'processassign');
        }
        if ((int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
            return get_string('graded', 'processassign');
        }
        if ((int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
            return get_string('submitted', 'processassign');
        }
        return get_string('submissionstatus_draft', 'assign');
    }

    /**
     * Whether a stage requires the student to respond to feedback before continuing.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @return bool true if a feedback response is required
     */
    public static function stage_requires_feedback_response($processassign, $stage): bool {
        return !empty($processassign->requirefeedbackresponse) || !empty($stage->requirefeedbackresponse);
    }

    /**
     * Whether a stage is complete for the submitting student.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return bool true if the stage is complete
     */
    public static function stage_complete($processassign, $stage, $submission): bool {
        if (!$submission || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED) {
            return false;
        }

        return !self::stage_requires_feedback_response($processassign, $stage)
            || !empty($submission->timefeedbackresponded);
    }

    /**
     * Return the localised stage status label, taking feedback response requirements into account.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return string the status label
     */
    public static function stage_status_label($processassign, $stage, $submission): string {
        if (self::stage_complete($processassign, $stage, $submission)) {
            return get_string('complete');
        }
        if (
            $submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                && self::stage_requires_feedback_response($processassign, $stage)
        ) {
            return get_string('feedbackresponserequired', 'processassign');
        }

        return self::status_label($submission);
    }

    /**
     * Whether a stage is unlocked for a student (all earlier stages complete).
     *
     * @param \stdClass $processassign the instance record
     * @param array $stages all stage records in order
     * @param array $submissions the student's submission records
     * @param int $stageid the stage to check
     * @return bool true if the stage is unlocked
     */
    public static function student_stage_is_unlocked(
        $processassign,
        array $stages,
        array $submissions,
        int $stageid
    ): bool {
        foreach ($stages as $stage) {
            $submission = self::get_submission_for_stage($submissions, (int)$stage->id);
            if ((int)$stage->id === $stageid) {
                return true;
            }
            if (!self::stage_complete($processassign, $stage, $submission)) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get the advanced grading instance for a stage, if an advanced grading method is active.
     *
     * @param \context_module $context the module context
     * @param \stdClass $stage the stage record
     * @param \stdClass $submission the submission record
     * @return \gradingform_instance|null the grading instance, or null if simple grading applies
     */
    public static function get_stage_grading_instance($context, $stage, $submission) {
        global $USER;

        $gradingmanager = get_grading_manager($context, 'mod_processassign', 'stage' . $stage->sortorder);
        if (!$gradingmethod = $gradingmanager->get_active_method()) {
            return null;
        }

        $controller = $gradingmanager->get_controller($gradingmethod);
        if (!$controller->is_form_available()) {
            return null;
        }

        $instanceid = optional_param('advancedgradinginstanceid', 0, PARAM_INT);
        $gradinginstance = $controller->get_or_create_instance($instanceid, $USER->id, $submission->id);
        $gradinginstance->get_controller()->set_grade_range(make_grades_menu($stage->maxgrade), true);

        return $gradinginstance;
    }

    /**
     * Determine the dashboard status key and label for a stage/submission pair.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return array two-element array of status key and localised label
     */
    public static function dashboard_status($processassign, $stage, $submission): array {
        if (self::stage_complete($processassign, $stage, $submission)) {
            return ['complete', get_string('complete')];
        }
        if (
            $submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                && self::stage_requires_feedback_response($processassign, $stage)
        ) {
            return ['awaitingresponse', get_string('awaitingresponse', 'processassign')];
        }
        if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
            return ['awaitingfeedback', get_string('awaitingfeedback', 'processassign')];
        }
        if (
            (!empty($stage->duedate) && time() > $stage->duedate)
                || (empty($stage->duedate) && !empty($processassign->duedate) && time() > $processassign->duedate)
                || (!empty($processassign->cutoffdate) && time() > $processassign->cutoffdate)
        ) {
            return ['late', get_string('late', 'processassign')];
        }

        return ['notstarted', get_string('notstarted', 'processassign')];
    }

    /**
     * Collect the data needed for the teacher dashboard: students, submissions, filters and counts.
     *
     * @param \stdClass $processassign the instance record
     * @param \context_module $context the module context
     * @param array $stages all stage records in order
     * @return array dashboard data keyed by students, submissions, filters, counts, submittedusers, needsgrading
     */
    public static function collect_teacher_dashboard_data($processassign, $context, $stages): array {
        global $DB;

        $students = get_enrolled_users(
            $context,
            'mod/processassign:submit',
            0,
            'u.*',
            'u.lastname, u.firstname, u.id'
        );

        if (!$students) {
            return [
                'students' => [],
                'submissions' => [],
                'filters' => [],
                'counts' => [],
                'submittedusers' => [],
                'needsgrading' => 0,
            ];
        }

        $records = $DB->get_records('processassign_subs', ['processassignid' => $processassign->id]);
        $submissions = [];
        foreach ($records as $record) {
            $submissions[$record->userid][$record->stageid] = $record;
        }

        $filters = [
            'all' => get_string('all'),
            'awaitingfeedback' => get_string('awaitingfeedback', 'processassign'),
            'awaitingresponse' => get_string('awaitingresponse', 'processassign'),
            'late' => get_string('late', 'processassign'),
            'notstarted' => get_string('notstarted', 'processassign'),
            'complete' => get_string('complete'),
        ];
        $counts = array_fill_keys(array_keys($filters), 0);
        $submittedusers = [];
        $needsgrading = 0;
        foreach ($students as $student) {
            foreach ($stages as $stage) {
                $submission = $submissions[$student->id][$stage->id] ?? null;
                [$statuskey] = self::dashboard_status($processassign, $stage, $submission);
                $counts['all']++;
                $counts[$statuskey]++;
                if (
                    $submission && in_array(
                        (int)$submission->status,
                        [PROCESSASSIGN_STATUS_SUBMITTED, PROCESSASSIGN_STATUS_GRADED],
                        true
                    )
                ) {
                    $submittedusers[$student->id] = true;
                }
                if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
                    $needsgrading++;
                }
            }
        }

        return [
            'students' => $students,
            'submissions' => $submissions,
            'filters' => $filters,
            'counts' => $counts,
            'submittedusers' => $submittedusers,
            'needsgrading' => $needsgrading,
        ];
    }

    /**
     * Resolve the effective due date for a stage (stage due date, falling back to the activity due date).
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @return int the due date timestamp, or 0 if none
     */
    public static function stage_due_date($processassign, $stage): int {
        return !empty($stage->duedate) ? (int)$stage->duedate : (int)($processassign->duedate ?? 0);
    }

    /**
     * Whether the student can still edit an existing submission for a stage.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return bool true if editing is allowed
     */
    public static function student_can_edit_submission($processassign, $stage, $submission): bool {
        if (!$submission || (int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
            return false;
        }
        if (!empty($processassign->cutoffdate) && time() > (int)$processassign->cutoffdate) {
            return false;
        }
        $duedate = self::stage_due_date($processassign, $stage);
        if (!empty($duedate) && time() > $duedate) {
            return false;
        }
        return true;
    }

    /**
     * Whether the student can submit (or resubmit) a stage.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return bool true if submitting is allowed
     */
    public static function student_can_submit_stage($processassign, $stage, $submission): bool {
        if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
            return false;
        }
        if (!empty($processassign->cutoffdate) && time() > (int)$processassign->cutoffdate) {
            return false;
        }
        $duedate = self::stage_due_date($processassign, $stage);
        if (!empty($duedate) && time() > $duedate) {
            return false;
        }
        return true;
    }

    /**
     * Get the ordered list of gradable submission ids (submitted first, then graded).
     *
     * @param \stdClass $processassign the instance record
     * @return array submission ids in grading order
     */
    public static function get_grader_submission_ids($processassign): array {
        global $DB;

        $records = $DB->get_records_sql(
            "
            SELECT s.id
              FROM {processassign_subs} s
              JOIN {user} u ON u.id = s.userid
              JOIN {processassign_stages} st ON st.id = s.stageid
             WHERE s.processassignid = :processassignid
               AND s.status IN (:submitted, :graded)
          ORDER BY CASE WHEN s.status = :submittedorder THEN 0 ELSE 1 END,
                   u.lastname, u.firstname, st.sortorder",
            [
                'processassignid' => $processassign->id,
                'submitted' => PROCESSASSIGN_STATUS_SUBMITTED,
                'graded' => PROCESSASSIGN_STATUS_GRADED,
                'submittedorder' => PROCESSASSIGN_STATUS_SUBMITTED,
            ]
        );

        return array_map('intval', array_keys($records));
    }

    /**
     * Validate a requested submission id against the gradable list, falling back to the first gradable one.
     *
     * @param \stdClass $processassign the instance record
     * @param int $submissionid the requested submission id, or 0
     * @return int a valid submission id, or 0 if nothing is gradable
     */
    public static function pick_grader_submissionid($processassign, int $submissionid): int {
        $submissionids = self::get_grader_submission_ids($processassign);
        if (!$submissionids) {
            return 0;
        }
        if ($submissionid && in_array($submissionid, $submissionids, true)) {
            return $submissionid;
        }

        return reset($submissionids);
    }
}
