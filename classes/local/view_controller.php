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
 * Form processing for the Process Assignment view page.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_processassign\local;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/lib.php');

/**
 * Form processing for the Process Assignment view page.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_controller {
    /**
     * Handle a student's submitted form: save a stage submission or a feedback response.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \context_module $context the module context
     * @param array $stages all stage records in order
     * @param array $editoroptions editor options for the forms
     */
    public static function handle_student_post(
        $processassign,
        $cm,
        $course,
        $context,
        array $stages,
        array $editoroptions
    ) {
        global $DB, $PAGE, $USER;

        if (!data_submitted() || !confirm_sesskey()) {
            return;
        }

        $stageid = optional_param('stageid', 0, PARAM_INT);
        $submissionid = optional_param('submissionid', 0, PARAM_INT);
        $submissions = stage_manager::get_student_submissions($processassign->id, $USER->id);

        if ($stageid) {
            $stage = stage_manager::get_stage_by_id($stages, $stageid);
            $submission = stage_manager::get_submission_for_stage($submissions, $stageid);
            if (
                !$stage || !stage_manager::student_stage_is_unlocked($processassign, $stages, $submissions, $stageid)
                    || !stage_manager::student_can_submit_stage($processassign, $stage, $submission)
                    || (!empty($processassign->allowsubmissionsfromdate)
                        && time() < (int)$processassign->allowsubmissionsfromdate)
            ) {
                redirect(
                    $PAGE->url,
                    get_string('stagenotavailable', 'processassign'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            $draftitemid = file_get_submitted_draft_itemid('submissionfiles');
            $itemid = $submission ? $submission->id : 0;
            $filemanageroptions = [
                'subdirs' => 0,
                'maxbytes' => !empty($stage->maxbytes) ? $stage->maxbytes : $course->maxbytes,
                'maxfiles' => !empty($stage->maxfiles) ? $stage->maxfiles : 5,
                'accepted_types' => !empty($stage->acceptedfiletypes) ? $stage->acceptedfiletypes : '*',
            ];
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'mod_processassign',
                'submission',
                $itemid,
                $filemanageroptions
            );

            $mform = new \mod_processassign\form\submission_form($PAGE->url, [
                'stage' => $stage,
                'processassign' => $processassign,
                'options' => ['editor' => $editoroptions, 'filemanager' => $filemanageroptions],
            ]);
            if (!$data = $mform->get_data()) {
                return;
            }
            if ((int)$data->stageid !== (int)$stage->id) {
                redirect(
                    $PAGE->url,
                    get_string('stagenotavailable', 'processassign'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            $now = time();
            $submitted = empty($processassign->submissiondrafts) || !empty($data->submitstage)
                || ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED);
            $timesubmitted = 0;
            if ($submitted) {
                $timesubmitted = ($submission && !empty($submission->timesubmitted)) ? $submission->timesubmitted : $now;
            } else if ($submission) {
                $timesubmitted = $submission->timesubmitted;
            }
            $record = (object)[
                'processassignid' => $processassign->id,
                'stageid' => $stage->id,
                'userid' => $USER->id,
                'submissiontext' => $data->submissioneditor['text'] ?? '',
                'submissionformat' => $data->submissioneditor['format'] ?? FORMAT_HTML,
                'status' => $submitted ? PROCESSASSIGN_STATUS_SUBMITTED : PROCESSASSIGN_STATUS_DRAFT,
                'timemodified' => $now,
                'timesubmitted' => $timesubmitted,
            ];

            if ($submission) {
                $record->id = $submission->id;
                $DB->update_record('processassign_subs', $record);
                $submissionid = $submission->id;
            } else {
                $record->timecreated = $now;
                $submissionid = $DB->insert_record('processassign_subs', $record);
            }

            if (!empty($stage->submissionfile)) {
                file_save_draft_area_files(
                    $data->submissionfiles,
                    $context->id,
                    'mod_processassign',
                    'submission',
                    $submissionid,
                    $filemanageroptions
                );
            }
            if (
                $submitted && !empty($processassign->sendnotifications)
                    && (!$submission || (int)$submission->status !== PROCESSASSIGN_STATUS_SUBMITTED)
            ) {
                notification_manager::notify_graders($processassign, $cm, $course, $context, $stage, $USER);
            }

            redirect(
                new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]),
                get_string($submitted ? 'submissionsaved' : 'draftsaved', 'processassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($submissionid) {
            $submission = $DB->get_record('processassign_subs', [
                'id' => $submissionid,
                'processassignid' => $processassign->id,
                'userid' => $USER->id,
            ]);
            $stage = $submission ? stage_manager::get_stage_by_id($stages, (int)$submission->stageid) : null;
            if (
                !$submission || !$stage || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED
                    || !stage_manager::stage_requires_feedback_response($processassign, $stage)
                    || !empty($submission->timefeedbackresponded)
            ) {
                redirect(
                    $PAGE->url,
                    get_string('stagenotavailable', 'processassign'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            $mform = new \mod_processassign\form\feedback_response_form($PAGE->url, [
                'options' => ['editor' => $editoroptions],
            ]);
            if (!$data = $mform->get_data()) {
                return;
            }
            if ((int)$data->submissionid !== (int)$submission->id) {
                redirect(
                    $PAGE->url,
                    get_string('stagenotavailable', 'processassign'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            $submission->feedbackresponse = $data->feedbackresponseeditor['text'];
            $submission->feedbackresponseformat = $data->feedbackresponseeditor['format'];
            $submission->timefeedbackresponded = time();
            $submission->timemodified = time();
            $DB->update_record('processassign_subs', $submission);
            redirect(
                $PAGE->url,
                get_string('feedbackresponsesaved', 'processassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }

    /**
     * Display and process the grading form for a single submission, then output the full grading page.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \context_module $context the module context
     * @param int $submissionid the submission to grade
     * @param array $editoroptions editor options for the feedback editor
     * @param bool $graderworkflow whether the sequential grader workflow (prev/next) is active
     */
    public static function handle_grade_view(
        $processassign,
        $cm,
        $course,
        $context,
        $submissionid,
        $editoroptions,
        bool $graderworkflow = false
    ) {
        global $DB, $OUTPUT, $USER;

        require_capability('mod/processassign:grade', $context);

        $submission = $DB->get_record(
            'processassign_subs',
            ['id' => $submissionid, 'processassignid' => $processassign->id],
            '*',
            MUST_EXIST
        );
        $stage = $DB->get_record('processassign_stages', ['id' => $submission->stageid], '*', MUST_EXIST);
        $student = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);
        $gradinginstance = stage_manager::get_stage_grading_instance($context, $stage, $submission);

        $formurl = new moodle_url('/mod/processassign/view.php', [
            'id' => $cm->id,
            'action' => $graderworkflow ? 'grader' : 'grade',
            'submissionid' => $submissionid,
        ]);

        $feedbackfilemanageroptions = [
            'subdirs' => 0,
            'maxbytes' => !empty($processassign->feedbackmaxbytes) ? (int)$processassign->feedbackmaxbytes :
                $course->maxbytes,
            'maxfiles' => !empty($processassign->feedbackmaxfiles) ? (int)$processassign->feedbackmaxfiles : 5,
            'accepted_types' => '*',
        ];
        $feedbackdraftitemid = file_get_submitted_draft_itemid('feedbackfiles');
        file_prepare_draft_area(
            $feedbackdraftitemid,
            $context->id,
            'mod_processassign',
            'feedback',
            (int)$submission->id,
            $feedbackfilemanageroptions
        );

        $mform = new \mod_processassign\form\grade_form($formurl, [
            'stage' => $stage,
            'processassign' => $processassign,
            'gradinginstance' => $gradinginstance,
            'showshownext' => $graderworkflow,
            'options' => ['editor' => $editoroptions, 'feedbackfilemanager' => $feedbackfilemanageroptions],
        ]);
        $mform->set_data([
            'submissionid' => $submission->id,
            'grade' => $submission->grade,
            'notifystudent' => !empty($processassign->sendstudentnotifications) ? 1 : 0,
            'feedback' => [
                'text' => $submission->feedback ?? '',
                'format' => FORMAT_HTML,
            ],
            'feedbackfiles' => $feedbackdraftitemid,
        ]);

        if ($data = $mform->get_data()) {
            if ($gradinginstance) {
                $submission->grade = $gradinginstance->submit_and_get_grade($data->advancedgrading, $submission->id);
            } else {
                $submission->grade = $data->grade;
            }
            if (!empty($processassign->feedbackcomments)) {
                $submission->feedback = $data->feedback['text'];
                $submission->feedbackformat = $data->feedback['format'];
            }
            $submission->status = PROCESSASSIGN_STATUS_GRADED;
            $submission->graderid = $USER->id;
            $submission->timegraded = time();
            $submission->timemodified = time();
            $DB->update_record('processassign_subs', $submission);
            if (!empty($processassign->feedbackfiles) && isset($data->feedbackfiles)) {
                file_save_draft_area_files(
                    $data->feedbackfiles,
                    $context->id,
                    'mod_processassign',
                    'feedback',
                    $submission->id,
                    $feedbackfilemanageroptions
                );
            }
            processassign_update_grades($processassign, $submission->userid);
            if (!empty($data->notifystudent)) {
                notification_manager::notify_student($processassign, $cm, $course, $stage, $student);
            }
            $redirecturl = new moodle_url('/mod/processassign/view.php', [
                'id' => $cm->id,
                'action' => 'submissions',
            ]);
            if ($graderworkflow && optional_param('saveandshownext', '', PARAM_ALPHA)) {
                $submissionids = stage_manager::get_grader_submission_ids($processassign);
                $position = array_search((int)$submission->id, $submissionids, true);
                if ($position !== false && isset($submissionids[$position + 1])) {
                    $redirecturl = new moodle_url('/mod/processassign/view.php', [
                        'id' => $cm->id,
                        'action' => 'grader',
                        'submissionid' => $submissionids[$position + 1],
                    ]);
                }
            }
            redirect(
                $redirecturl,
                get_string('gradesaved', 'processassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        echo $OUTPUT->header();
        if ($graderworkflow) {
            echo view_builder::render_grader_navigation($processassign, $cm, (int)$submission->id);
        }
        echo $OUTPUT->heading(fullname($student), 2, 'mb-1');
        echo html_writer::div(s($student->email), 'text-muted mb-1');
        echo html_writer::div(format_string($stage->name) . ' - ' . get_string('duedate', 'processassign') . ': ' .
            (stage_manager::stage_due_date($processassign, $stage) ?
                userdate(stage_manager::stage_due_date($processassign, $stage)) : '-'), 'text-muted mb-4');
        echo $OUTPUT->heading(get_string('submissionstatussummary', 'processassign'), 3);
        echo view_builder::render_grader_status_panel($processassign, $stage, $submission);
        echo $OUTPUT->heading(get_string('submission', 'processassign'), 3);
        echo view_builder::render_submission($submission, $context);
        echo $OUTPUT->heading(get_string('grade', 'processassign'), 3);
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
}
