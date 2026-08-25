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
 * HTML construction helpers for the Process Assignment view page.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_processassign\local;

use core_text;
use html_table;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/lib.php');

/**
 * HTML construction helpers for the Process Assignment view page.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_builder {
    /**
     * Render a submission's text and files as HTML.
     *
     * @param \stdClass $submission the submission record
     * @param \context_module $context the module context
     * @return string HTML for the submission
     */
    public static function render_submission($submission, $context) {
        global $OUTPUT;

        $html = '';
        if (!empty($submission->submissiontext)) {
            $html .= html_writer::div(
                format_text($submission->submissiontext, $submission->submissionformat),
                'processassign-submission-text'
            );
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_processassign', 'submission', $submission->id, 'filename', false);
        if ($files) {
            $items = [];
            foreach ($files as $file) {
                $url = moodle_url::make_pluginfile_url(
                    $context->id,
                    'mod_processassign',
                    'submission',
                    $submission->id,
                    $file->get_filepath(),
                    $file->get_filename()
                );
                $items[] = html_writer::link($url, s($file->get_filename()));
            }
            $html .= html_writer::alist($items);
        }

        return $html ?: $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
    }

    /**
     * Render links to the feedback files attached to a submission.
     *
     * @param \stdClass $submission the submission record
     * @param \context_module $context the module context
     * @return string HTML for the feedback files, or an empty string if none
     */
    public static function render_feedback_files($submission, $context) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_processassign', 'feedback', $submission->id, 'filename', false);
        if (!$files) {
            return '';
        }

        $items = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_processassign',
                'feedback',
                $submission->id,
                $file->get_filepath(),
                $file->get_filename()
            );
            $items[] = html_writer::link($url, s($file->get_filename()));
        }

        return html_writer::div(html_writer::alist($items), 'mt-2 alert alert-secondary');
    }

    /**
     * Render the activity instructions block.
     *
     * @param \stdClass $processassign the instance record
     * @param \context_module $context the module context
     * @return string HTML for the instructions, or an empty string if none
     */
    public static function render_activity_instructions($processassign, $context): string {
        if (trim($processassign->activity ?? '') === '') {
            return '';
        }

        $activity = file_rewrite_pluginfile_urls(
            $processassign->activity,
            'pluginfile.php',
            $context->id,
            'mod_processassign',
            'activity',
            0
        );

        return html_writer::div(
            html_writer::tag('h3', get_string('activityeditor', 'assign'), ['class' => 'h5']) .
            format_text($activity, $processassign->activityformat ?? FORMAT_HTML, ['context' => $context]),
            'processassign-activity-instructions alert alert-light border'
        );
    }

    /**
     * Render links to the intro attachment files.
     *
     * @param \context_module $context the module context
     * @return string HTML for the attachments, or an empty string if none
     */
    public static function render_intro_attachments($context): string {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_processassign', 'introattachment', 0, 'filename', false);
        if (!$files) {
            return '';
        }

        $items = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_processassign',
                'introattachment',
                0,
                $file->get_filepath(),
                $file->get_filename()
            );
            $items[] = html_writer::link($url, s($file->get_filename()));
        }

        return html_writer::div(
            html_writer::tag('h3', get_string('introattachments', 'assign'), ['class' => 'h5']) .
            html_writer::alist($items),
            'processassign-intro-attachments alert alert-light border'
        );
    }

    /**
     * Render the submission requirement badges for a stage.
     *
     * @param \stdClass $stage the stage record
     * @return string HTML badges, or '-' if the stage has no requirements
     */
    public static function stage_requirements_html($stage): string {
        $items = [];
        if (!empty($stage->submissiononlinetext)) {
            $items[] = html_writer::span(
                get_string('onlinetext', 'assignsubmission_onlinetext'),
                'badge bg-light text-dark border me-1'
            );
        }
        if (!empty($stage->submissionfile)) {
            $items[] = html_writer::span(get_string('filesubmissions', 'assign'), 'badge bg-light text-dark border me-1');
            $items[] = html_writer::span(
                get_string('maxfiles', 'assignsubmission_file') . ': ' . (int)$stage->maxfiles,
                'badge bg-light text-dark border me-1'
            );
            if (!empty($stage->acceptedfiletypes) && $stage->acceptedfiletypes !== '*') {
                $items[] = html_writer::span(get_string('acceptedfiletypes', 'assignsubmission_file') . ': ' .
                    s($stage->acceptedfiletypes), 'badge bg-light text-dark border me-1');
            }
        }
        if (!empty($stage->wordlimitenabled) && !empty($stage->wordlimit)) {
            $items[] = html_writer::span(
                get_string('wordlimit', 'assignsubmission_onlinetext') . ': ' . (int)$stage->wordlimit,
                'badge bg-light text-dark border me-1'
            );
        }

        return $items ? implode('', $items) : '-';
    }

    /**
     * Output the student's history of previously submitted stages.
     *
     * @param \stdClass $processassign the instance record
     * @param array $stages all stage records in order
     * @param array $submissions the student's submission records
     * @param \context_module $context the module context
     */
    public static function render_student_history($processassign, $stages, $submissions, $context) {
        global $OUTPUT;

        $rows = [];
        foreach ($stages as $stage) {
            $submission = stage_manager::get_submission_for_stage($submissions, (int)$stage->id);
            if (!$submission) {
                continue;
            }

            $details = html_writer::div(
                stage_manager::stage_status_label($processassign, $stage, $submission),
                'small text-muted'
            );
            if ((int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
                $details .= html_writer::div(get_string('grade', 'processassign') . ': ' .
                    format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2), 'small');
                if (!empty($submission->feedback)) {
                    $details .= html_writer::div(
                        format_text($submission->feedback, $submission->feedbackformat),
                        'mt-2 alert alert-info'
                    );
                }
                $details .= self::render_feedback_files($submission, $context);
                if (!empty($submission->feedbackresponse)) {
                    $details .= html_writer::div(format_text(
                        $submission->feedbackresponse,
                        $submission->feedbackresponseformat
                    ), 'mt-2 alert alert-secondary');
                }
            }

            $rows[] = html_writer::tag(
                'li',
                html_writer::tag('strong', format_string($stage->name)) . $details,
                ['class' => 'list-group-item']
            );
        }

        if (!$rows) {
            return;
        }

        echo html_writer::start_div('mb-4');
        echo $OUTPUT->heading(get_string('previousstagehistory', 'processassign'), 3);
        echo html_writer::tag('ul', implode('', $rows), ['class' => 'list-group']);
        echo html_writer::end_div();
    }

    /**
     * Output the student view: progress summary, history and per-stage cards with submission forms.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \context_module $context the module context
     * @param array $stages all stage records in order
     * @param bool $cansubmit whether the user can submit
     * @param array $editoroptions editor options for the submission forms
     * @param int $editstageid stage id the student asked to re-edit, or 0
     */
    public static function render_student_view(
        $processassign,
        $cm,
        $course,
        $context,
        $stages,
        $cansubmit,
        $editoroptions,
        $editstageid
    ) {
        global $OUTPUT, $PAGE, $USER;

        $submissions = stage_manager::get_student_submissions($processassign->id, $USER->id);
        $unlocked = true;
        $formrendered = false;
        $completedstages = 0;
        $currentstageindex = 1;
        $totalstages = count($stages);
        $currentstatus = get_string('notstarted', 'processassign');
        $currentaction = get_string('studentactionsubmit', 'processassign');

        $index = 0;
        foreach ($stages as $stageforprogress) {
            $index++;
            $currentsubmission = stage_manager::get_submission_for_stage($submissions, (int)$stageforprogress->id);
            if (stage_manager::stage_complete($processassign, $stageforprogress, $currentsubmission)) {
                $completedstages++;
                continue;
            }
            $currentstageindex = $index;
            $currentstatus = stage_manager::stage_status_label($processassign, $stageforprogress, $currentsubmission);
            if ($currentsubmission && (int)$currentsubmission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
                $currentaction = get_string('studentactionwaitfeedback', 'processassign');
            } else if (
                $currentsubmission && (int)$currentsubmission->status === PROCESSASSIGN_STATUS_GRADED
                    && stage_manager::stage_requires_feedback_response($processassign, $stageforprogress)
                    && empty($currentsubmission->timefeedbackresponded)
            ) {
                $currentaction = get_string('studentactionfeedbackresponse', 'processassign');
            } else {
                $currentaction = get_string('studentactionsubmit', 'processassign');
            }
            break;
        }
        if ($completedstages >= $totalstages) {
            $currentstageindex = $totalstages;
            $currentstatus = get_string('complete');
            $currentaction = get_string('studentactioncomplete', 'processassign');
        }

        echo html_writer::start_div('alert alert-light border mb-4');
        echo $OUTPUT->heading(get_string('studentprogress', 'processassign'), 4, 'mb-2');
        echo html_writer::div(get_string(
            'studentprogressvalue',
            'processassign',
            (object)['current' => $currentstageindex, 'total' => $totalstages]
        ), 'mb-1');
        echo html_writer::div(get_string('status', 'processassign') . ': ' . s($currentstatus), 'mb-1');
        echo html_writer::div(get_string('studentaction', 'processassign') . ': ' . s($currentaction), 'fw-semibold');
        echo html_writer::tag(
            'details',
            html_writer::tag('summary', get_string('howprocessworks', 'processassign')) .
            html_writer::div(get_string('howprocessworksdesc', 'processassign'), 'mt-2'),
            ['class' => 'mt-3']
        );
        echo html_writer::end_div();

        self::render_student_history($processassign, $stages, $submissions, $context);
        echo $OUTPUT->heading(get_string('stages', 'processassign'), 3);

        foreach ($stages as $stage) {
            $submission = stage_manager::get_submission_for_stage($submissions, (int)$stage->id);

            $classes = 'card mb-3';
            echo html_writer::start_div($classes);
            echo html_writer::start_div('card-body');
            echo $OUTPUT->heading(format_string($stage->name), 4);
            echo html_writer::div(format_text($stage->instructions, $stage->instructionsformat), 'mb-2');
            if (!empty($stage->duedate)) {
                echo html_writer::div(
                    get_string('duedate', 'processassign') . ': ' . userdate($stage->duedate),
                    'small text-muted'
                );
            } else if (!empty($processassign->duedate)) {
                echo html_writer::div(
                    get_string('duedate', 'processassign') . ': ' . userdate($processassign->duedate),
                    'small text-muted'
                );
            }
            if (!empty($stage->wordlimitenabled) && !empty($stage->wordlimit)) {
                echo html_writer::div(
                    get_string('wordlimit', 'assignsubmission_onlinetext') . ': ' . $stage->wordlimit,
                    'small text-muted'
                );
            }
            echo html_writer::div(get_string('submissionrequirements', 'processassign') . ': ' .
                self::stage_requirements_html($stage), 'small mt-2');
            echo html_writer::div(get_string('status', 'processassign') . ': ' .
                stage_manager::stage_status_label($processassign, $stage, $submission), 'mt-2');

            if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
                echo html_writer::div(get_string('grade', 'processassign') . ': ' .
                    format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2), 'mt-2');
                if (!empty($submission->feedback)) {
                    echo html_writer::div(
                        format_text($submission->feedback, $submission->feedbackformat),
                        'mt-2 alert alert-info'
                    );
                }
                echo self::render_feedback_files($submission, $context);
                if (!empty($submission->feedbackresponse)) {
                    echo html_writer::tag('h5', get_string('feedbackresponse', 'processassign'), ['class' => 'mt-3']);
                    echo html_writer::div(
                        format_text($submission->feedbackresponse, $submission->feedbackresponseformat),
                        'mt-2 alert alert-secondary'
                    );
                }
            }

            $beforeopen = !empty($processassign->allowsubmissionsfromdate)
                && time() < $processassign->allowsubmissionsfromdate;
            $aftercutoff = !empty($processassign->cutoffdate) && time() > $processassign->cutoffdate;
            $effectiveduedate = !empty($stage->duedate) ? $stage->duedate : (int)$processassign->duedate;
            $afterstagedue = !empty($effectiveduedate) && time() > $effectiveduedate;
            $showeditform = !$submission || (int)$submission->status === PROCESSASSIGN_STATUS_DRAFT
                || ((int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED && (int)$editstageid === (int)$stage->id);

            if (!$unlocked) {
                $lockreason = get_string('lockreason:previousstage', 'processassign');
                if (
                    $submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                        && stage_manager::stage_requires_feedback_response($processassign, $stage)
                        && empty($submission->timefeedbackresponded)
                ) {
                    $lockreason = get_string('lockreason:feedbackresponse', 'processassign');
                }
                echo $OUTPUT->notification(get_string('notopenyet', 'processassign'), 'info');
                echo html_writer::div($lockreason, 'small text-muted');
            } else if ($beforeopen) {
                echo $OUTPUT->notification(get_string(
                    'submissionsnotopen',
                    'processassign',
                    userdate($processassign->allowsubmissionsfromdate)
                ), 'info');
                echo html_writer::div(get_string('lockreason:availability', 'processassign'), 'small text-muted');
            } else if ($aftercutoff || $afterstagedue) {
                echo $OUTPUT->notification(get_string('submissionsclosed', 'processassign'), 'warning');
                echo html_writer::div(get_string('lockreason:cutoff', 'processassign'), 'small text-muted');
            } else if (
                $cansubmit && !$formrendered && $submission
                    && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                    && stage_manager::stage_requires_feedback_response($processassign, $stage)
                    && empty($submission->timefeedbackresponded)
            ) {
                echo $OUTPUT->heading(get_string('feedbackresponserequired', 'processassign'), 5);

                $mform = new \mod_processassign\form\feedback_response_form($PAGE->url, [
                    'options' => ['editor' => $editoroptions],
                ]);
                $mform->set_data([
                    'submissionid' => $submission->id,
                    'feedbackresponseeditor' => [
                        'text' => $submission->feedbackresponse ?? '',
                        'format' => FORMAT_HTML,
                    ],
                ]);

                $mform->display();
                $formrendered = true;
            } else if (
                $cansubmit && !$formrendered && $submission
                    && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED
                    && (int)$editstageid !== (int)$stage->id
            ) {
                $editurl = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id, 'editstageid' => $stage->id]);
                echo html_writer::div(get_string('submittedforgrading', 'processassign'), 'mt-2 alert alert-success');
                echo html_writer::link(
                    $editurl,
                    get_string('editsubmission', 'assign'),
                    ['class' => 'btn btn-secondary mt-2']
                );
            } else if (
                $cansubmit && !$formrendered && $showeditform && (!$submission
                    || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED)
            ) {
                echo $OUTPUT->heading(get_string('currentstage', 'processassign'), 5);

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
                $formdata = [
                    'stageid' => $stage->id,
                ];
                if (!empty($stage->submissiononlinetext)) {
                    $formdata['submissioneditor'] = [
                        'text' => $submission->submissiontext ?? '',
                        'format' => $submission->submissionformat ?? FORMAT_HTML,
                    ];
                }
                if (!empty($stage->submissionfile)) {
                    $formdata['submissionfiles'] = $draftitemid;
                }
                $mform->set_data($formdata);

                $mform->display();
                $formrendered = true;
            }

            echo html_writer::end_div();
            echo html_writer::end_div();

            if (
                $submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED
                    && !empty($submission->timesubmitted)
            ) {
                echo html_writer::start_div('alert alert-success mt-n2 mb-3');
                echo html_writer::tag('strong', get_string('submissionreceipt', 'processassign')) . ': ' .
                    userdate($submission->timesubmitted);
                echo html_writer::end_div();
            }

            $unlocked = stage_manager::stage_complete($processassign, $stage, $submission);
        }
    }

    /**
     * Output the grading summary table and action buttons for graders.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \context_module $context the module context
     * @param array $stages all stage records in order
     */
    public static function render_grading_summary($processassign, $cm, $context, $stages): void {
        global $OUTPUT;

        $data = stage_manager::collect_teacher_dashboard_data($processassign, $context, $stages);

        echo $OUTPUT->heading(get_string('gradingsummary', 'processassign'), 3);
        $summary = new html_table();
        $summary->attributes['class'] = 'generaltable mb-4';
        $summary->data = [
            [get_string('hiddenfromstudents', 'processassign'), $cm->visible ? get_string('no') : get_string('yes')],
            [get_string('participants'), count($data['students'])],
            [get_string('submitted', 'processassign'), count($data['submittedusers'])],
            [get_string('needsgrading', 'processassign'), $data['needsgrading']],
        ];
        echo html_writer::table($summary);

        $buttons = [];
        $buttons[] = html_writer::link(
            new moodle_url('/mod/processassign/view.php', ['id' => $cm->id, 'action' => 'grader']),
            get_string('gradeall', 'processassign'),
            ['class' => 'btn btn-primary me-2']
        );
        $buttons[] = html_writer::link(
            new moodle_url('/mod/processassign/view.php', [
                'id' => $cm->id,
                'action' => 'submissions',
                'statusfilter' => 'all',
            ]),
            get_string('submissions', 'processassign'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::div(implode(' ', $buttons), 'mb-4');
    }

    /**
     * Render a dropdown action menu.
     *
     * @param string $label accessible label for the menu toggle
     * @param array $items menu items, each with text and url keys (or disabled => true)
     * @return string HTML for the menu
     */
    public static function render_action_menu(string $label, array $items): string {
        static $menuid = 0;
        $menuid++;
        $id = 'processassign-action-menu-' . $menuid;
        $toggle = html_writer::link(
            '#',
            html_writer::tag('i', '', ['class' => 'icon fa fa-ellipsis-vertical fa-fw', 'aria-hidden' => 'true']) .
                html_writer::span($label, 'sr-only'),
            [
                'class' => 'btn btn-icon d-flex align-items-center justify-content-center no-caret dropdown-toggle '
                    . 'icon-no-margin',
                'id' => $id,
                'role' => 'button',
                'data-toggle' => 'dropdown',
                'aria-haspopup' => 'true',
                'aria-expanded' => 'false',
                'title' => $label,
            ]
        );

        $links = [];
        foreach ($items as $item) {
            if (!empty($item['disabled'])) {
                $links[] = html_writer::span($item['text'] . html_writer::span(
                    get_string('planned', 'processassign'),
                    'badge bg-light text-dark ms-2'
                ), 'dropdown-item disabled', [
                        'title' => get_string('plannedfeature', 'processassign'),
                    ]);
                continue;
            }
            $links[] = html_writer::link($item['url'], $item['text'], ['class' => 'dropdown-item']);
        }

        return html_writer::div(
            $toggle . html_writer::div(implode('', $links), 'dropdown-menu'),
            'dropdown d-inline-block'
        );
    }

    /**
     * Return the time remaining (or late) text for a stage.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @return string the time remaining text, or '-' if no due date
     */
    public static function time_remaining_text($processassign, $stage): string {
        $duedate = stage_manager::stage_due_date($processassign, $stage);
        if (empty($duedate)) {
            return '-';
        }
        $difference = $duedate - time();
        if ($difference >= 0) {
            return get_string('timeleft', 'processassign', format_time($difference));
        }
        return get_string('late', 'processassign');
    }

    /**
     * Return the current gradebook grade for a submission as display text.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return string the grade text
     */
    public static function current_gradebook_grade_text($processassign, $stage, $submission): string {
        if (!$submission || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED) {
            return get_string('notgraded', 'processassign');
        }
        if (($processassign->gradebookmode ?? 'single') === 'category') {
            return format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2);
        }
        $grade = processassign_get_user_grade($processassign, $submission->userid);
        return format_float($grade->rawgrade, 2) . ' / ' . format_float($processassign->grade, 2);
    }

    /**
     * Output the teacher submissions table with search and filter controls.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \context_module $context the module context
     * @param array $stages all stage records in order
     * @param string $statusfilter status filter key, or 'all'
     * @param int $stagefilter stage id to filter by, or 0 for all
     * @param string $search free-text search over student name and email
     */
    public static function render_teacher_table(
        $processassign,
        $cm,
        $context,
        $stages,
        $statusfilter,
        $stagefilter,
        $search
    ) {
        global $OUTPUT, $PAGE;

        $data = stage_manager::collect_teacher_dashboard_data($processassign, $context, $stages);
        if (!$data['students']) {
            echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
            return;
        }

        echo html_writer::start_div('processassign-submissions');
        echo html_writer::div(get_string('prototypehint', 'processassign'), 'alert alert-info processassign-prototypehint');
        echo html_writer::start_div('processassign-submissionsbar d-flex flex-wrap align-items-center gap-3 mb-4');
        echo $OUTPUT->heading(get_string('submissions', 'processassign'), 2, 'mb-0 me-3');

        echo html_writer::start_tag('form', [
            'method' => 'get',
            'action' => $PAGE->url->out(false),
            'class' => 'd-flex flex-wrap align-items-center gap-3 flex-grow-1',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'submissions']);

        echo html_writer::empty_tag('input', [
            'type' => 'search',
            'name' => 'search',
            'value' => s($search),
            'placeholder' => get_string('searchusers', 'processassign'),
            'class' => 'form-control processassign-search',
        ]);

        $statusoptions = [];
        foreach ($data['filters'] as $key => $label) {
            $statusoptions[$key] = $label . ' (' . ($data['counts'][$key] ?? 0) . ')';
        }
        echo html_writer::label(get_string('status'), 'id_statusfilter', false, ['class' => 'accesshide']);
        echo html_writer::select($statusoptions, 'statusfilter', $statusfilter, false, [
            'id' => 'id_statusfilter',
            'class' => 'custom-select',
        ]);

        $stageoptions = [0 => get_string('allstages', 'processassign')];
        foreach ($stages as $stage) {
            $stageoptions[$stage->id] = format_string($stage->name);
        }
        echo html_writer::label(get_string('stage', 'processassign'), 'id_stagefilter', false, ['class' => 'accesshide']);
        echo html_writer::select($stageoptions, 'stagefilter', $stagefilter, false, [
            'id' => 'id_stagefilter',
            'class' => 'custom-select',
        ]);

        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('filter'),
            'class' => 'btn btn-secondary',
        ]);
        echo html_writer::link(new moodle_url('/mod/processassign/view.php', [
            'id' => $cm->id,
            'action' => 'grader',
        ]), get_string('grade', 'processassign'), ['class' => 'btn btn-primary ms-auto']);
        echo html_writer::end_tag('form');
        echo html_writer::end_div();

        echo html_writer::start_div('processassign-submissions-actions d-flex justify-content-end align-items-center mb-3');
        echo html_writer::checkbox('quickgrading', 1, false, get_string('quickgrading', 'assign'), [
            'disabled' => 'disabled',
            'class' => 'me-2',
        ]);
        echo self::render_action_menu(get_string('actions'), [[
            'text' => get_string('viewgradebook', 'processassign'),
            'url' => new moodle_url('/grade/report/grader/index.php', ['id' => $cm->course]),
        ], [
            'text' => get_string('bulknotyetavailable', 'processassign'),
            'disabled' => true,
        ]]);
        echo html_writer::end_div();

        $table = new html_table();
        $table->attributes['class'] = 'generaltable processassign-submissions-table';
        $table->head = [
            get_string('select'),
            get_string('fullnameuser'),
            get_string('email'),
            get_string('stage', 'processassign'),
            get_string('status', 'processassign'),
            get_string('grade', 'processassign'),
            get_string('timemodified', 'assign'),
            get_string('submissionfiles', 'processassign'),
            get_string('submissiontext', 'processassign'),
            get_string('feedback', 'processassign'),
            get_string('feedbackfiles', 'processassign'),
            get_string('feedbackresponse', 'processassign'),
            get_string('submissionactions', 'processassign'),
            get_string('gradeactions', 'processassign'),
        ];

        foreach ($data['students'] as $student) {
            $studenttext = core_text::strtolower(fullname($student) . ' ' . $student->email);
            if ($search !== '' && core_text::strpos($studenttext, core_text::strtolower($search)) === false) {
                continue;
            }
            foreach ($stages as $stage) {
                if ($stagefilter && (int)$stagefilter !== (int)$stage->id) {
                    continue;
                }
                $submission = $data['submissions'][$student->id][$stage->id] ?? null;
                [$statuskey, $statuslabel] = stage_manager::dashboard_status($processassign, $stage, $submission);
                if ($statusfilter !== 'all' && $statusfilter !== $statuskey) {
                    continue;
                }

                $submissionactions = [];
                $gradeactions = [];
                $grade = '-';
                $modified = '-';
                $submissionfiles = '-';
                $submissiontext = '-';
                $feedback = '-';
                $feedbackfiles = '-';
                $feedbackresponse = '-';
                if ($submission) {
                    $gradeurl = new moodle_url('/mod/processassign/view.php', [
                        'id' => $cm->id,
                        'action' => 'grader',
                        'submissionid' => $submission->id,
                    ]);
                    $gradeactions[] = [
                        'text' => get_string('grade', 'processassign'),
                        'url' => $gradeurl,
                    ];
                    $submissionactions[] = [
                        'text' => get_string('viewsubmission', 'processassign'),
                        'url' => $gradeurl,
                    ];
                    $submissionactions[] = [
                        'text' => get_string('editsubmission', 'assign'),
                        'disabled' => true,
                    ];
                    $submissionactions[] = [
                        'text' => get_string('preventsubmissionsshort', 'assign'),
                        'disabled' => true,
                    ];
                    $submissionactions[] = [
                        'text' => get_string('grantextension', 'assign'),
                        'disabled' => true,
                    ];
                    if ((int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
                        $grade = format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2);
                    }
                    if (!empty($submission->timemodified)) {
                        $modified = userdate($submission->timemodified);
                    }
                    $fs = get_file_storage();
                    $files = $fs->get_area_files(
                        $context->id,
                        'mod_processassign',
                        'submission',
                        $submission->id,
                        'filename',
                        false
                    );
                    if ($files) {
                        $filelinks = [];
                        foreach ($files as $file) {
                            $url = moodle_url::make_pluginfile_url(
                                $context->id,
                                'mod_processassign',
                                'submission',
                                $submission->id,
                                $file->get_filepath(),
                                $file->get_filename()
                            );
                            $filelinks[] = html_writer::link($url, s($file->get_filename()));
                        }
                        $submissionfiles = implode(html_writer::empty_tag('br'), $filelinks);
                    }
                    if (!empty($submission->submissiontext)) {
                        $submissiontext = html_writer::div(format_text(
                            $submission->submissiontext,
                            $submission->submissionformat
                        ), 'processassign-table-text small');
                    }
                    if (!empty($submission->feedback)) {
                        $feedback = html_writer::div(
                            format_text($submission->feedback, $submission->feedbackformat),
                            'processassign-table-text small'
                        );
                    }
                    $feedbackfileshtml = self::render_feedback_files($submission, $context);
                    if ($feedbackfileshtml !== '') {
                        $feedbackfiles = $feedbackfileshtml;
                    }
                    if (!empty($submission->feedbackresponse)) {
                        $feedbackresponse = html_writer::div(format_text(
                            $submission->feedbackresponse,
                            $submission->feedbackresponseformat
                        ), 'processassign-table-text small');
                    }
                }
                if (!empty($student->email)) {
                    $subject = rawurlencode(get_string(
                        'nudgesubject',
                        'processassign',
                        format_string($processassign->name)
                    ));
                    $body = rawurlencode(get_string('nudgebody', 'processassign', (object)[
                        'stage' => format_string($stage->name),
                        'activity' => format_string($processassign->name),
                    ]));
                    $submissionactions[] = [
                        'text' => get_string('nudge', 'processassign'),
                        'url' => "mailto:{$student->email}?subject={$subject}&body={$body}",
                    ];
                }

                $table->data[] = [
                    html_writer::checkbox(
                        'selected[]',
                        $student->id . ':' . $stage->id,
                        false,
                        '',
                        ['disabled' => 'disabled']
                    ),
                    html_writer::link(
                        new moodle_url('/user/view.php', ['id' => $student->id, 'course' => $cm->course]),
                        fullname($student)
                    ),
                    s($student->email),
                    format_string($stage->name) . html_writer::div(
                        get_string('stagetype:' . $stage->stagetype, 'processassign'),
                        'small text-muted'
                    ),
                    html_writer::span($statuslabel, 'processassign-status processassign-status-' . $statuskey),
                    $grade,
                    $modified,
                    $submissionfiles,
                    $submissiontext,
                    $feedback,
                    $feedbackfiles,
                    $feedbackresponse,
                    $submissionactions ? self::render_action_menu(
                        get_string('submissionactions', 'processassign'),
                        $submissionactions
                    ) : '-',
                    $gradeactions ? self::render_action_menu(
                        get_string('gradeactions', 'processassign'),
                        $gradeactions
                    ) : '-',
                ];
            }
        }

        if (empty($table->data)) {
            echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
            echo html_writer::end_div();
            return;
        }

        echo html_writer::table($table);
        echo html_writer::end_div();
    }

    /**
     * Render the previous/next navigation bar and user selector for the grader workflow.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param int $submissionid the submission currently being graded
     * @return string HTML for the navigation bar
     */
    public static function render_grader_navigation($processassign, $cm, int $submissionid): string {
        global $DB;

        $submissionids = stage_manager::get_grader_submission_ids($processassign);
        $position = array_search($submissionid, $submissionids, true);
        if ($position === false) {
            return '';
        }

        $items = [];
        $items[] = html_writer::link(new moodle_url('/mod/processassign/view.php', [
            'id' => $cm->id,
            'action' => 'submissions',
            'statusfilter' => 'all',
        ]), get_string('viewgrading', 'assign'), ['class' => 'btn btn-secondary me-2']);
        if (isset($submissionids[$position - 1])) {
            $items[] = html_writer::link(new moodle_url('/mod/processassign/view.php', [
                'id' => $cm->id,
                'action' => 'grader',
                'submissionid' => $submissionids[$position - 1],
            ]), get_string('previous'), ['class' => 'btn btn-secondary me-2']);
        }
        $items[] = html_writer::span(get_string('submissionposition', 'processassign', (object)[
            'current' => $position + 1,
            'total' => count($submissionids),
        ]), 'me-2');
        if (isset($submissionids[$position + 1])) {
            $items[] = html_writer::link(new moodle_url('/mod/processassign/view.php', [
                'id' => $cm->id,
                'action' => 'grader',
                'submissionid' => $submissionids[$position + 1],
            ]), get_string('next'), ['class' => 'btn btn-secondary']);
        }

        $records = $DB->get_records_sql(
            "
            SELECT s.id AS submissionid, u.id AS userid, u.firstname, u.lastname, u.firstnamephonetic,
                   u.lastnamephonetic, u.middlename, u.alternatename, st.name AS stagename
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
        $options = [];
        foreach ($records as $record) {
            $record->id = $record->userid;
            $options[$record->submissionid] = fullname($record) . ' - ' . format_string($record->stagename);
        }
        $selector = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => (new moodle_url('/mod/processassign/view.php'))->out(false),
            'class' => 'd-inline-flex align-items-center ms-3',
        ]);
        $selector .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        $selector .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'grader']);
        $selector .= html_writer::label(get_string('changeuser', 'assign'), 'id_submissionid', false, ['class' => 'me-2']);
        $selector .= html_writer::select($options, 'submissionid', $submissionid, false, [
            'id' => 'id_submissionid',
            'class' => 'custom-select me-2',
        ]);
        $selector .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('go'),
            'class' => 'btn btn-secondary',
        ]);
        $selector .= html_writer::end_tag('form');

        return html_writer::div(implode(' ', $items) . $selector, 'processassign-grader-nav mb-3');
    }

    /**
     * Render the submission status summary table shown to graders.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $stage the stage record
     * @param \stdClass|null $submission the submission record, or null if none
     * @return string HTML for the status table
     */
    public static function render_grader_status_panel($processassign, $stage, $submission): string {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable processassign-grader-status mb-4';
        $table->data = [
            [get_string('submission', 'processassign'), $submission ? stage_manager::status_label($submission) :
                get_string('noattempt', 'processassign')],
            [get_string('gradingstatus', 'processassign'), $submission &&
                (int)$submission->status === PROCESSASSIGN_STATUS_GRADED ? get_string('graded', 'processassign') :
                get_string('notgraded', 'processassign')],
            [get_string('timeremaining', 'assign'), self::time_remaining_text($processassign, $stage)],
            [get_string('studentcanedit', 'processassign'), stage_manager::student_can_edit_submission(
                $processassign,
                $stage,
                $submission
            ) ? get_string('yes') : get_string('no')],
            [get_string('currentgradebookgrade', 'processassign'), self::current_gradebook_grade_text(
                $processassign,
                $stage,
                $submission
            )],
        ];
        return html_writer::table($table);
    }
}
