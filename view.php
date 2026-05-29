<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/processassign/lib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);
$submissionid = optional_param('submissionid', 0, PARAM_INT);
$statusfilter = optional_param('statusfilter', 'all', PARAM_ALPHANUMEXT);
$stagefilter = optional_param('stagefilter', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$editstageid = optional_param('editstageid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('processassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$processassign = $DB->get_record('processassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/processassign/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($processassign->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/processassign/styles.css');

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$canSubmit = has_capability('mod/processassign:submit', $context);
$canGrade = has_capability('mod/processassign:grade', $context);
if ($canGrade && ($action === 'view' || $action === 'submissions')) {
    $PAGE->set_secondary_active_tab('mod_processassign_submissions');
}

$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context' => $context,
];

function processassign_get_stages($processassignid) {
    global $DB;
    return $DB->get_records('processassign_stages', ['processassignid' => $processassignid], 'sortorder ASC');
}

function processassign_get_student_submissions($processassignid, $userid) {
    global $DB;
    return $DB->get_records('processassign_subs',
        ['processassignid' => $processassignid, 'userid' => $userid], '', '*', 0, 0);
}

function processassign_status_label($submission) {
    if (!$submission) {
        return get_string('notsubmitted', 'processassign');
    }
    if ((int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
        return get_string('graded', 'processassign');
    }
    if ((int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
        return get_string('submitted', 'processassign');
    }
    return get_string('draft', 'moodle');
}

function processassign_stage_complete($stage, $submission): bool {
    if (!$submission || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED) {
        return false;
    }

    return empty($stage->requirefeedbackresponse) || !empty($submission->timefeedbackresponded);
}

function processassign_stage_status_label($stage, $submission): string {
    if (processassign_stage_complete($stage, $submission)) {
        return get_string('complete');
    }
    if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED && !empty($stage->requirefeedbackresponse)) {
        return get_string('feedbackresponserequired', 'processassign');
    }

    return processassign_status_label($submission);
}

function processassign_render_submission($submission, $context) {
    global $OUTPUT;

    $html = '';
    if (!empty($submission->submissiontext)) {
        $html .= html_writer::div(format_text($submission->submissiontext, $submission->submissionformat),
            'processassign-submission-text');
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_processassign', 'submission', $submission->id, 'filename', false);
    if ($files) {
        $items = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($context->id, 'mod_processassign', 'submission', $submission->id,
                $file->get_filepath(), $file->get_filename());
            $items[] = html_writer::link($url, s($file->get_filename()));
        }
        $html .= html_writer::alist($items);
    }

    return $html ?: $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
}

function processassign_render_feedback_files($submission, $context) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_processassign', 'feedback', $submission->id, 'filename', false);
    if (!$files) {
        return '';
    }

    $items = [];
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url($context->id, 'mod_processassign', 'feedback', $submission->id,
            $file->get_filepath(), $file->get_filename());
        $items[] = html_writer::link($url, s($file->get_filename()));
    }

    return html_writer::div(html_writer::alist($items), 'mt-2 alert alert-secondary');
}

function processassign_stage_requirements_html($stage): string {
    $items = [];
    if (!empty($stage->submissiononlinetext)) {
        $items[] = html_writer::span(get_string('onlinetext', 'assignsubmission_onlinetext'),
            'badge bg-light text-dark border me-1');
    }
    if (!empty($stage->submissionfile)) {
        $items[] = html_writer::span(get_string('filesubmissions', 'assign'), 'badge bg-light text-dark border me-1');
        $items[] = html_writer::span(get_string('maxfiles', 'assignsubmission_file') . ': ' . (int)$stage->maxfiles,
            'badge bg-light text-dark border me-1');
        if (!empty($stage->acceptedfiletypes) && $stage->acceptedfiletypes !== '*') {
            $items[] = html_writer::span(get_string('acceptedfiletypes', 'assignsubmission_file') . ': ' .
                s($stage->acceptedfiletypes), 'badge bg-light text-dark border me-1');
        }
    }
    if (!empty($stage->wordlimitenabled) && !empty($stage->wordlimit)) {
        $items[] = html_writer::span(get_string('wordlimit', 'assignsubmission_onlinetext') . ': ' . (int)$stage->wordlimit,
            'badge bg-light text-dark border me-1');
    }

    return $items ? implode('', $items) : '-';
}

function processassign_render_student_history($stages, $submissions, $context) {
    global $OUTPUT;

    $rows = [];
    foreach ($stages as $stage) {
        $submission = null;
        foreach ($submissions as $studentsubmission) {
            if ((int)$studentsubmission->stageid === (int)$stage->id) {
                $submission = $studentsubmission;
                break;
            }
        }

        if (!$submission) {
            continue;
        }

        $details = html_writer::div(processassign_stage_status_label($stage, $submission), 'small text-muted');
        if ((int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
            $details .= html_writer::div(get_string('grade', 'processassign') . ': ' .
                format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2), 'small');
            if (!empty($submission->feedback)) {
                $details .= html_writer::div(format_text($submission->feedback, $submission->feedbackformat),
                    'mt-2 alert alert-info');
            }
            $details .= processassign_render_feedback_files($submission, $context);
            if (!empty($submission->feedbackresponse)) {
                $details .= html_writer::div(format_text($submission->feedbackresponse, $submission->feedbackresponseformat),
                    'mt-2 alert alert-secondary');
            }
        }

        $rows[] = html_writer::tag('li',
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

function processassign_render_student_view($processassign, $cm, $course, $context, $stages, $canSubmit, $editoroptions,
        $editstageid) {
    global $DB, $OUTPUT, $PAGE, $USER;

    $submissions = processassign_get_student_submissions($processassign->id, $USER->id);
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
        $currentsubmission = null;
        foreach ($submissions as $studentsubmission) {
            if ((int)$studentsubmission->stageid === (int)$stageforprogress->id) {
                $currentsubmission = $studentsubmission;
                break;
            }
        }
        if (processassign_stage_complete($stageforprogress, $currentsubmission)) {
            $completedstages++;
            continue;
        }
        $currentstageindex = $index;
        $currentstatus = processassign_stage_status_label($stageforprogress, $currentsubmission);
        if ($currentsubmission && (int)$currentsubmission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
            $currentaction = get_string('studentactionwaitfeedback', 'processassign');
        } else if ($currentsubmission && (int)$currentsubmission->status === PROCESSASSIGN_STATUS_GRADED
                && !empty($stageforprogress->requirefeedbackresponse) && empty($currentsubmission->timefeedbackresponded)) {
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
    echo html_writer::div(get_string('studentprogressvalue', 'processassign',
        (object)['current' => $currentstageindex, 'total' => $totalstages]), 'mb-1');
    echo html_writer::div(get_string('status', 'processassign') . ': ' . s($currentstatus), 'mb-1');
    echo html_writer::div(get_string('studentaction', 'processassign') . ': ' . s($currentaction), 'fw-semibold');
    echo html_writer::tag('details',
        html_writer::tag('summary', get_string('howprocessworks', 'processassign')) .
        html_writer::div(get_string('howprocessworksdesc', 'processassign'), 'mt-2'),
        ['class' => 'mt-3']
    );
    echo html_writer::end_div();

    processassign_render_student_history($stages, $submissions, $context);
    echo $OUTPUT->heading(get_string('stages', 'processassign'), 3);

    foreach ($stages as $stage) {
        $submission = null;
        foreach ($submissions as $studentsubmission) {
            if ((int)$studentsubmission->stageid === (int)$stage->id) {
                $submission = $studentsubmission;
                break;
            }
        }

        $classes = 'card mb-3';
        echo html_writer::start_div($classes);
        echo html_writer::start_div('card-body');
        echo $OUTPUT->heading(format_string($stage->name), 4);
        echo html_writer::div(format_text($stage->instructions, $stage->instructionsformat), 'mb-2');
        if (!empty($stage->duedate)) {
            echo html_writer::div(get_string('duedate', 'processassign') . ': ' . userdate($stage->duedate), 'small text-muted');
        } else if (!empty($processassign->duedate)) {
            echo html_writer::div(get_string('duedate', 'processassign') . ': ' . userdate($processassign->duedate),
                'small text-muted');
        }
        if (!empty($stage->wordlimitenabled) && !empty($stage->wordlimit)) {
            echo html_writer::div(get_string('wordlimit', 'assignsubmission_onlinetext') . ': ' . $stage->wordlimit,
                'small text-muted');
        }
        echo html_writer::div(get_string('submissionrequirements', 'processassign') . ': ' .
            processassign_stage_requirements_html($stage), 'small mt-2');
        echo html_writer::div(get_string('status', 'processassign') . ': ' .
            processassign_stage_status_label($stage, $submission), 'mt-2');

        if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
            echo html_writer::div(get_string('grade', 'processassign') . ': ' .
                format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2), 'mt-2');
            if (!empty($submission->feedback)) {
                echo html_writer::div(format_text($submission->feedback, $submission->feedbackformat), 'mt-2 alert alert-info');
            }
            echo processassign_render_feedback_files($submission, $context);
            if (!empty($submission->feedbackresponse)) {
                echo html_writer::tag('h5', get_string('feedbackresponse', 'processassign'), ['class' => 'mt-3']);
                echo html_writer::div(format_text($submission->feedbackresponse, $submission->feedbackresponseformat),
                    'mt-2 alert alert-secondary');
            }
        }

        $beforeopen = !empty($processassign->allowsubmissionsfromdate) && time() < $processassign->allowsubmissionsfromdate;
        $aftercutoff = !empty($processassign->cutoffdate) && time() > $processassign->cutoffdate;
        $effectiveduedate = !empty($stage->duedate) ? $stage->duedate : (int)$processassign->duedate;
        $afterstagedue = !empty($effectiveduedate) && time() > $effectiveduedate;
        $showeditform = !$submission || (int)$submission->status === PROCESSASSIGN_STATUS_DRAFT
            || ((int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED && (int)$editstageid === (int)$stage->id);

        if (!$unlocked) {
            $lockreason = get_string('lockreason:previousstage', 'processassign');
            if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                    && !empty($stage->requirefeedbackresponse) && empty($submission->timefeedbackresponded)) {
                $lockreason = get_string('lockreason:feedbackresponse', 'processassign');
            }
            echo $OUTPUT->notification(get_string('notopenyet', 'processassign'), 'info');
            echo html_writer::div($lockreason, 'small text-muted');
        } else if ($beforeopen) {
            echo $OUTPUT->notification(get_string('submissionsnotopen', 'processassign',
                userdate($processassign->allowsubmissionsfromdate)), 'info');
            echo html_writer::div(get_string('lockreason:availability', 'processassign'), 'small text-muted');
        } else if ($aftercutoff || $afterstagedue) {
            echo $OUTPUT->notification(get_string('submissionsclosed', 'processassign'), 'warning');
            echo html_writer::div(get_string('lockreason:cutoff', 'processassign'), 'small text-muted');
        } else if ($canSubmit && !$formrendered && $submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED
                && !empty($stage->requirefeedbackresponse) && empty($submission->timefeedbackresponded)) {
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

            if ($data = $mform->get_data()) {
                $submission->feedbackresponse = $data->feedbackresponseeditor['text'];
                $submission->feedbackresponseformat = $data->feedbackresponseeditor['format'];
                $submission->timefeedbackresponded = time();
                $submission->timemodified = time();
                $DB->update_record('processassign_subs', $submission);
                redirect($PAGE->url, get_string('feedbackresponsesaved', 'processassign'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }

            $mform->display();
            $formrendered = true;
        } else if ($canSubmit && !$formrendered && $submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED
                && (int)$editstageid !== (int)$stage->id) {
            $editurl = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id, 'editstageid' => $stage->id]);
            echo html_writer::div(get_string('submittedforgrading', 'processassign'), 'mt-2 alert alert-success');
            echo html_writer::link($editurl, get_string('editsubmission', 'assign'), ['class' => 'btn btn-secondary mt-2']);
        } else if ($canSubmit && !$formrendered && $showeditform && (!$submission
                || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED)) {
            echo $OUTPUT->heading(get_string('currentstage', 'processassign'), 5);

            $draftitemid = file_get_submitted_draft_itemid('submissionfiles');
            $itemid = $submission ? $submission->id : 0;
            $filemanageroptions = [
                'subdirs' => 0,
                'maxbytes' => !empty($stage->maxbytes) ? $stage->maxbytes : $course->maxbytes,
                'maxfiles' => !empty($stage->maxfiles) ? $stage->maxfiles : 5,
                'accepted_types' => !empty($stage->acceptedfiletypes) ? $stage->acceptedfiletypes : '*',
            ];
            file_prepare_draft_area($draftitemid, $context->id, 'mod_processassign', 'submission', $itemid,
                $filemanageroptions);

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

            if ($data = $mform->get_data()) {
                $now = time();
                $submitted = empty($processassign->submissiondrafts) || !empty($data->submitstage);
                $record = (object)[
                    'processassignid' => $processassign->id,
                    'stageid' => $stage->id,
                    'userid' => $USER->id,
                    'submissiontext' => $data->submissioneditor['text'] ?? '',
                    'submissionformat' => $data->submissioneditor['format'] ?? FORMAT_HTML,
                    'status' => $submitted ? PROCESSASSIGN_STATUS_SUBMITTED : PROCESSASSIGN_STATUS_DRAFT,
                    'timemodified' => $now,
                    'timesubmitted' => $submitted ? $now : ($submission ? $submission->timesubmitted : 0),
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
                    file_save_draft_area_files($data->submissionfiles, $context->id, 'mod_processassign', 'submission',
                        $submissionid, $filemanageroptions);
                }
                if ($submitted && !empty($processassign->sendnotifications)) {
                    processassign_notify_graders($processassign, $cm, $course, $context, $stage, $USER);
                }
                redirect(new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]),
                    get_string($submitted ? 'submissionsaved' : 'draftsaved', 'processassign'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }

            $mform->display();
            $formrendered = true;
        }

        echo html_writer::end_div();
        echo html_writer::end_div();

        if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED && !empty($submission->timesubmitted)) {
            echo html_writer::start_div('alert alert-success mt-n2 mb-3');
            echo html_writer::tag('strong', get_string('submissionreceipt', 'processassign')) . ': ' .
                userdate($submission->timesubmitted);
            echo html_writer::end_div();
        }

        $unlocked = processassign_stage_complete($stage, $submission);
    }
}

function processassign_get_stage_grading_instance($context, $stage, $submission) {
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

function processassign_notify_graders($processassign, $cm, $course, $context, $stage, $student) {
    $graders = get_enrolled_users($context, 'mod/processassign:grade');
    if (!$graders) {
        return;
    }

    $subject = get_string('submissionnotificationsubject', 'processassign', format_string($processassign->name));
    $url = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]);
    $body = get_string('submissionnotificationbody', 'processassign', (object)[
        'student' => fullname($student),
        'stage' => format_string($stage->name),
        'activity' => format_string($processassign->name),
        'course' => format_string($course->fullname),
        'url' => $url->out(false),
    ]);

    foreach ($graders as $grader) {
        email_to_user($grader, core_user::get_support_user(), $subject, $body);
    }
}

function processassign_notify_student($processassign, $cm, $course, $stage, $student) {
    $subject = get_string('gradenotificationsubject', 'processassign', format_string($processassign->name));
    $url = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]);
    $body = get_string('gradenotificationbody', 'processassign', (object)[
        'stage' => format_string($stage->name),
        'activity' => format_string($processassign->name),
        'course' => format_string($course->fullname),
        'url' => $url->out(false),
    ]);

    email_to_user($student, core_user::get_support_user(), $subject, $body);
}

function processassign_dashboard_status($processassign, $stage, $submission): array {
    if (processassign_stage_complete($stage, $submission)) {
        return ['complete', get_string('complete')];
    }
    if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_GRADED && !empty($stage->requirefeedbackresponse)) {
        return ['awaitingresponse', get_string('awaitingresponse', 'processassign')];
    }
    if ($submission && (int)$submission->status === PROCESSASSIGN_STATUS_SUBMITTED) {
        return ['awaitingfeedback', get_string('awaitingfeedback', 'processassign')];
    }
    if ((!empty($stage->duedate) && time() > $stage->duedate)
            || (empty($stage->duedate) && !empty($processassign->duedate) && time() > $processassign->duedate)
            || (!empty($processassign->cutoffdate) && time() > $processassign->cutoffdate)) {
        return ['late', get_string('late', 'processassign')];
    }

    return ['notstarted', get_string('notstarted', 'processassign')];
}

function processassign_collect_teacher_dashboard_data($processassign, $context, $stages): array {
    global $DB;

    $students = get_enrolled_users($context, 'mod/processassign:submit', 0, 'u.*',
        'u.lastname, u.firstname, u.id');

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
            [$statuskey] = processassign_dashboard_status($processassign, $stage, $submission);
            $counts['all']++;
            $counts[$statuskey]++;
            if ($submission && in_array((int)$submission->status,
                    [PROCESSASSIGN_STATUS_SUBMITTED, PROCESSASSIGN_STATUS_GRADED], true)) {
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

function processassign_render_grading_summary($processassign, $cm, $context, $stages): void {
    global $OUTPUT;

    $data = processassign_collect_teacher_dashboard_data($processassign, $context, $stages);

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

function processassign_render_action_menu(string $label, array $items): string {
    static $menuid = 0;
    $menuid++;
    $id = 'processassign-action-menu-' . $menuid;
    $toggle = html_writer::link('#',
        html_writer::tag('i', '', ['class' => 'icon fa fa-ellipsis-vertical fa-fw', 'aria-hidden' => 'true']) .
            html_writer::span($label, 'sr-only'),
        [
            'class' => 'btn btn-icon d-flex align-items-center justify-content-center no-caret dropdown-toggle icon-no-margin',
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
            $links[] = html_writer::span($item['text'] . html_writer::span(get_string('planned', 'processassign'),
                'badge bg-light text-dark ms-2'), 'dropdown-item disabled', [
                    'title' => get_string('plannedfeature', 'processassign'),
                ]);
            continue;
        }
        $links[] = html_writer::link($item['url'], $item['text'], ['class' => 'dropdown-item']);
    }

    return html_writer::div($toggle . html_writer::div(implode('', $links), 'dropdown-menu'), 'dropdown d-inline-block');
}

function processassign_stage_due_date($processassign, $stage): int {
    return !empty($stage->duedate) ? (int)$stage->duedate : (int)($processassign->duedate ?? 0);
}

function processassign_time_remaining_text($processassign, $stage): string {
    $duedate = processassign_stage_due_date($processassign, $stage);
    if (empty($duedate)) {
        return '-';
    }
    $difference = $duedate - time();
    if ($difference >= 0) {
        return get_string('timeleft', 'processassign', format_time($difference));
    }
    return get_string('late', 'processassign');
}

function processassign_current_gradebook_grade_text($processassign, $stage, $submission): string {
    if (!$submission || (int)$submission->status !== PROCESSASSIGN_STATUS_GRADED) {
        return get_string('notgraded', 'processassign');
    }
    if (($processassign->gradebookmode ?? 'single') === 'category') {
        return format_float($submission->grade, 2) . ' / ' . format_float($stage->maxgrade, 2);
    }
    $grade = processassign_get_user_grade($processassign, $submission->userid);
    return format_float($grade->rawgrade, 2) . ' / ' . format_float($processassign->grade, 2);
}

function processassign_student_can_edit_submission($processassign, $stage, $submission): bool {
    if (!$submission || (int)$submission->status === PROCESSASSIGN_STATUS_GRADED) {
        return false;
    }
    if (!empty($processassign->cutoffdate) && time() > (int)$processassign->cutoffdate) {
        return false;
    }
    $duedate = processassign_stage_due_date($processassign, $stage);
    if (!empty($duedate) && time() > $duedate) {
        return false;
    }
    return true;
}

function processassign_render_teacher_table($processassign, $cm, $context, $stages, $statusfilter, $stagefilter, $search) {
    global $OUTPUT, $PAGE;

    $data = processassign_collect_teacher_dashboard_data($processassign, $context, $stages);
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
        'onchange' => 'this.form.submit()',
    ]);

    $stageoptions = [0 => get_string('allstages', 'processassign')];
    foreach ($stages as $stage) {
        $stageoptions[$stage->id] = format_string($stage->name);
    }
    echo html_writer::label(get_string('stage', 'processassign'), 'id_stagefilter', false, ['class' => 'accesshide']);
    echo html_writer::select($stageoptions, 'stagefilter', $stagefilter, false, [
        'id' => 'id_stagefilter',
        'class' => 'custom-select',
        'onchange' => 'this.form.submit()',
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
    echo processassign_render_action_menu(get_string('actions'), [[
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
        get_string('feedbackfiles', 'assignfeedback_file'),
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
            [$statuskey, $statuslabel] = processassign_dashboard_status($processassign, $stage, $submission);
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
                $files = $fs->get_area_files($context->id, 'mod_processassign', 'submission', $submission->id,
                    'filename', false);
                if ($files) {
                    $filelinks = [];
                    foreach ($files as $file) {
                        $url = moodle_url::make_pluginfile_url($context->id, 'mod_processassign', 'submission',
                            $submission->id, $file->get_filepath(), $file->get_filename());
                        $filelinks[] = html_writer::link($url, s($file->get_filename()));
                    }
                    $submissionfiles = implode(html_writer::empty_tag('br'), $filelinks);
                }
                if (!empty($submission->submissiontext)) {
                    $submissiontext = html_writer::div(format_text($submission->submissiontext,
                        $submission->submissionformat), 'processassign-table-text small');
                }
                if (!empty($submission->feedback)) {
                    $feedback = html_writer::div(format_text($submission->feedback, $submission->feedbackformat),
                        'processassign-table-text small');
                }
                $feedbackfileshtml = processassign_render_feedback_files($submission, $context);
                if ($feedbackfileshtml !== '') {
                    $feedbackfiles = $feedbackfileshtml;
                }
                if (!empty($submission->feedbackresponse)) {
                    $feedbackresponse = html_writer::div(format_text($submission->feedbackresponse,
                        $submission->feedbackresponseformat), 'processassign-table-text small');
                }
            }
            if (!empty($student->email)) {
                $subject = rawurlencode(get_string('nudgesubject', 'processassign', format_string($processassign->name)));
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
                html_writer::checkbox('selected[]', $student->id . ':' . $stage->id, false, '', ['disabled' => 'disabled']),
                html_writer::link(new moodle_url('/user/view.php', ['id' => $student->id, 'course' => $cm->course]),
                    fullname($student)),
                s($student->email),
                format_string($stage->name) . html_writer::div(
                    get_string('stagetype:' . $stage->stagetype, 'processassign'), 'small text-muted'),
                html_writer::span($statuslabel, 'processassign-status processassign-status-' . $statuskey),
                $grade,
                $modified,
                $submissionfiles,
                $submissiontext,
                $feedback,
                $feedbackfiles,
                $feedbackresponse,
                $submissionactions ? processassign_render_action_menu(get_string('submissionactions', 'processassign'),
                    $submissionactions) : '-',
                $gradeactions ? processassign_render_action_menu(get_string('gradeactions', 'processassign'), $gradeactions) : '-',
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

function processassign_get_grader_submission_ids($processassign): array {
    global $DB;

    $records = $DB->get_records_sql("
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
        ]);

    return array_map('intval', array_keys($records));
}

function processassign_pick_grader_submissionid($processassign, int $submissionid): int {
    $submissionids = processassign_get_grader_submission_ids($processassign);
    if (!$submissionids) {
        return 0;
    }
    if ($submissionid && in_array($submissionid, $submissionids, true)) {
        return $submissionid;
    }

    return reset($submissionids);
}

function processassign_render_grader_navigation($processassign, $cm, int $submissionid): string {
    global $DB;

    $submissionids = processassign_get_grader_submission_ids($processassign);
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

    $records = $DB->get_records_sql("
        SELECT s.id AS submissionid, u.id AS userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
               u.middlename, u.alternatename, st.name AS stagename
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
        ]);
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

function processassign_render_grader_status_panel($processassign, $stage, $submission): string {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable processassign-grader-status mb-4';
    $table->data = [
        [get_string('submission', 'processassign'), $submission ? processassign_status_label($submission) :
            get_string('noattempt', 'processassign')],
        [get_string('gradingstatus', 'processassign'), $submission &&
            (int)$submission->status === PROCESSASSIGN_STATUS_GRADED ? get_string('graded', 'processassign') :
            get_string('notgraded', 'processassign')],
        [get_string('timeremaining', 'assign'), processassign_time_remaining_text($processassign, $stage)],
        [get_string('studentcanedit', 'processassign'), processassign_student_can_edit_submission($processassign, $stage,
            $submission) ? get_string('yes') : get_string('no')],
        [get_string('currentgradebookgrade', 'processassign'), processassign_current_gradebook_grade_text($processassign,
            $stage, $submission)],
    ];
    return html_writer::table($table);
}

function processassign_handle_grade_view($processassign, $cm, $course, $context, $submissionid, $editoroptions,
        bool $graderworkflow = false) {
    global $DB, $OUTPUT, $PAGE, $USER;

    require_capability('mod/processassign:grade', $context);

    $submission = $DB->get_record('processassign_subs', ['id' => $submissionid, 'processassignid' => $processassign->id],
        '*', MUST_EXIST);
    $stage = $DB->get_record('processassign_stages', ['id' => $submission->stageid], '*', MUST_EXIST);
    $student = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);
    $gradinginstance = processassign_get_stage_grading_instance($context, $stage, $submission);

    $formurl = new moodle_url('/mod/processassign/view.php', [
        'id' => $cm->id,
        'action' => $graderworkflow ? 'grader' : 'grade',
        'submissionid' => $submissionid,
    ]);

    $feedbackfilemanageroptions = [
        'subdirs' => 0,
        'maxbytes' => !empty($processassign->feedbackmaxbytes) ? (int)$processassign->feedbackmaxbytes : $course->maxbytes,
        'maxfiles' => !empty($processassign->feedbackmaxfiles) ? (int)$processassign->feedbackmaxfiles : 5,
        'accepted_types' => '*',
    ];
    $feedbackdraftitemid = file_get_submitted_draft_itemid('feedbackfiles');
    file_prepare_draft_area($feedbackdraftitemid, $context->id, 'mod_processassign', 'feedback', (int)$submission->id,
        $feedbackfilemanageroptions);

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
        $submission->gradedby = $USER->id;
        $submission->timegraded = time();
        $submission->timemodified = time();
        $DB->update_record('processassign_subs', $submission);
        if (!empty($processassign->feedbackfiles) && isset($data->feedbackfiles)) {
            file_save_draft_area_files($data->feedbackfiles, $context->id, 'mod_processassign', 'feedback',
                $submission->id, $feedbackfilemanageroptions);
        }
        processassign_update_grades($processassign, $submission->userid);
        if (!empty($data->notifystudent)) {
            processassign_notify_student($processassign, $cm, $course, $stage, $student);
        }
        $redirecturl = new moodle_url('/mod/processassign/view.php', [
            'id' => $cm->id,
            'action' => 'submissions',
        ]);
        if ($graderworkflow && optional_param('saveandshownext', '', PARAM_RAW)) {
            $submissionids = processassign_get_grader_submission_ids($processassign);
            $position = array_search((int)$submission->id, $submissionids, true);
            if ($position !== false && isset($submissionids[$position + 1])) {
                $redirecturl = new moodle_url('/mod/processassign/view.php', [
                    'id' => $cm->id,
                    'action' => 'grader',
                    'submissionid' => $submissionids[$position + 1],
                ]);
            }
        }
        redirect($redirecturl,
            get_string('gradesaved', 'processassign'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    if ($graderworkflow) {
        echo processassign_render_grader_navigation($processassign, $cm, (int)$submission->id);
    }
    echo $OUTPUT->heading(fullname($student), 2, 'mb-1');
    echo html_writer::div(s($student->email), 'text-muted mb-1');
    echo html_writer::div(format_string($stage->name) . ' - ' . get_string('duedate', 'processassign') . ': ' .
        (processassign_stage_due_date($processassign, $stage) ? userdate(processassign_stage_due_date($processassign, $stage)) :
            '-'), 'text-muted mb-4');
    echo $OUTPUT->heading(get_string('submissionstatussummary', 'processassign'), 3);
    echo processassign_render_grader_status_panel($processassign, $stage, $submission);
    echo $OUTPUT->heading(get_string('submission', 'processassign'), 3);
    echo processassign_render_submission($submission, $context);
    echo $OUTPUT->heading(get_string('grade', 'processassign'), 3);
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'grade' && $submissionid) {
    processassign_handle_grade_view($processassign, $cm, $course, $context, $submissionid, $editoroptions);
}
if ($action === 'grader') {
    $submissionid = processassign_pick_grader_submissionid($processassign, $submissionid);
    if (!$submissionid) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('gradeall', 'processassign'));
        echo $OUTPUT->notification(get_string('nogradablesubmissions', 'processassign'), 'info');
        echo html_writer::link(new moodle_url('/mod/processassign/view.php', [
            'id' => $cm->id,
            'action' => 'submissions',
        ]), get_string('submissions', 'processassign'), ['class' => 'btn btn-secondary']);
        echo $OUTPUT->footer();
        exit;
    }
    processassign_handle_grade_view($processassign, $cm, $course, $context, $submissionid, $editoroptions, true);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($processassign->name));
if ($canGrade) {
    processassign_update_grades($processassign);
}
if (!empty($processassign->alwaysshowdescription)
        || empty($processassign->allowsubmissionsfromdate)
        || time() >= $processassign->allowsubmissionsfromdate
        || $canGrade) {
    echo format_module_intro('processassign', $processassign, $cm->id);
}

$stages = processassign_get_stages($processassign->id);
if (!$stages) {
    echo $OUTPUT->notification(get_string('nostages', 'processassign'), 'warning');
} else {
    if ($canGrade) {
        if ($action === 'submissions') {
            processassign_render_teacher_table($processassign, $cm, $context, $stages, $statusfilter, $stagefilter, $search);
        } else {
            processassign_render_grading_summary($processassign, $cm, $context, $stages);
        }
    } else if ($canSubmit) {
        processassign_render_student_view($processassign, $cm, $course, $context, $stages, $canSubmit, $editoroptions,
            $editstageid);
    }
}

echo $OUTPUT->footer();
