<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

define('PROCESSASSIGN_STATUS_DRAFT', 0);
define('PROCESSASSIGN_STATUS_SUBMITTED', 1);
define('PROCESSASSIGN_STATUS_GRADED', 2);

function processassign_stage_type_options(): array {
    return [
        'custom' => get_string('stagetype:custom', 'processassign'),
        'proposal' => get_string('stagetype:proposal', 'processassign'),
        'outline' => get_string('stagetype:outline', 'processassign'),
        'draft' => get_string('stagetype:draft', 'processassign'),
        'revisionplan' => get_string('stagetype:revisionplan', 'processassign'),
        'reflection' => get_string('stagetype:reflection', 'processassign'),
        'researchlog' => get_string('stagetype:researchlog', 'processassign'),
        'media' => get_string('stagetype:media', 'processassign'),
        'final' => get_string('stagetype:final', 'processassign'),
    ];
}

function processassign_gradebook_mode_options(): array {
    return [
        'single' => get_string('gradebookmode:single', 'processassign'),
        'category' => get_string('gradebookmode:category', 'processassign'),
    ];
}

function processassign_default_instructions_for_stage_type(string $stagetype): string {
    $identifier = 'stagetypeinstructions:' . $stagetype;
    if (get_string_manager()->string_exists($identifier, 'processassign')) {
        return get_string($identifier, 'processassign');
    }

    return '';
}

function processassign_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

function processassign_extend_settings_navigation(settings_navigation $settings, navigation_node $navref): void {
    $page = $settings->get_page();
    $cm = $page->cm;
    if (!$cm) {
        return;
    }

    if (!has_capability('mod/processassign:grade', $cm->context)) {
        return;
    }

    $url = new moodle_url('/mod/processassign/view.php', [
        'id' => $cm->id,
        'action' => 'submissions',
        'statusfilter' => 'all',
    ]);
    $page->requires->css('/mod/processassign/styles.css');
    if ($modulepagenode = $navref->get('modulepage')) {
        $modulepagenode->text = get_string('submissions', 'processassign');
        $modulepagenode->action = $url;
        return;
    }
    $navref->add(
        text: get_string('submissions', 'processassign'),
        action: $url,
        type: navigation_node::TYPE_SETTING,
        key: 'mod_processassign_submissions'
    );
}

function processassign_attempt_reopen_options(): array {
    return [
        'none' => get_string('attemptreopenmethod_none', 'assign'),
        'manual' => get_string('attemptreopenmethod_manual', 'assign'),
        'automatic' => get_string('attemptreopenmethod_automatic', 'assign'),
        'untilpass' => get_string('attemptreopenmethod_untilpass', 'assign'),
    ];
}

function processassign_add_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    processassign_normalise_settings($data);
    if (!isset($data->grade)) {
        $data->grade = 100;
    }
    $data->id = $DB->insert_record('processassign', $data);
    processassign_save_intro_files($data);
    processassign_save_stages($data->id, $data);
    processassign_grade_item_update($data);

    return $data->id;
}

function processassign_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $oldrecord = $DB->get_record('processassign', ['id' => $data->id], 'id, gradecategoryid');
    $data->timemodified = time();
    processassign_normalise_settings($data);
    if ($oldrecord && !empty($oldrecord->gradecategoryid)) {
        $data->gradecategoryid = $oldrecord->gradecategoryid;
    }
    $DB->update_record('processassign', $data);
    processassign_save_intro_files($data);
    processassign_save_stages($data->id, $data);
    processassign_grade_item_update($data);
    processassign_update_grades($data);

    return true;
}

function processassign_normalise_settings($data) {
    $data->allowsubmissionsfromdate = (int)($data->allowsubmissionsfromdate ?? 0);
    $data->duedate = (int)($data->duedate ?? 0);
    $data->cutoffdate = (int)($data->cutoffdate ?? 0);
    $data->gradingduedate = (int)($data->gradingduedate ?? 0);
    $data->timelimit = max(0, (int)($data->timelimit ?? 0));
    $data->alwaysshowdescription = !empty($data->alwaysshowdescription) ? 1 : 0;
    $activity = $data->activityeditor ?? null;
    $data->activity = is_array($activity) ? ($activity['text'] ?? '') : ($data->activity ?? '');
    $data->activityformat = is_array($activity) ? ($activity['format'] ?? FORMAT_HTML) : ($data->activityformat ?? FORMAT_HTML);
    $data->submissiononlinetext = !empty($data->submissiononlinetext) ? 1 : 0;
    $data->submissionfile = !empty($data->submissionfile) ? 1 : 0;
    $data->maxfiles = max(1, (int)($data->maxfiles ?? 5));
    $data->maxbytes = (int)($data->maxbytes ?? 0);
    $data->feedbackcomments = !empty($data->feedbackcomments) ? 1 : 0;
    $data->feedbackfiles = !empty($data->feedbackfiles) ? 1 : 0;
    $data->feedbackmaxfiles = max(1, (int)($data->feedbackmaxfiles ?? 5));
    $data->feedbackmaxbytes = (int)($data->feedbackmaxbytes ?? 0);
    $data->wordlimitenabled = !empty($data->wordlimitenabled) ? 1 : 0;
    $data->wordlimit = max(0, (int)($data->wordlimit ?? 0));
    $data->sendnotifications = !empty($data->sendnotifications) ? 1 : 0;
    $data->sendstudentnotifications = !empty($data->sendstudentnotifications) ? 1 : 0;
    $data->submissiondrafts = !empty($data->submissiondrafts) ? 1 : 0;
    $data->requiresubmissionstatement = !empty($data->requiresubmissionstatement) ? 1 : 0;
    $data->requirefeedbackresponse = !empty($data->requirefeedbackresponse) ? 1 : 0;
    $data->maxattempts = (int)($data->maxattempts ?? 1);
    $data->attemptreopenmethod = in_array($data->attemptreopenmethod ?? 'manual',
        ['none', 'manual', 'untilpass', 'automatic'], true) ? $data->attemptreopenmethod : 'manual';
    $data->gradebookmode = in_array($data->gradebookmode ?? 'single', ['single', 'category'], true)
        ? $data->gradebookmode
        : 'single';
    $data->gradecategoryid = (int)($data->gradecategoryid ?? 0);
}

function processassign_save_intro_files($data): void {
    global $DB;

    if (empty($data->coursemodule)) {
        return;
    }

    $context = context_module::instance($data->coursemodule);
    if (isset($data->introattachments)) {
        file_save_draft_area_files($data->introattachments, $context->id, 'mod_processassign',
            'introattachment', 0, ['subdirs' => 0]);
    }

    if (!empty($data->activityeditor) && is_array($data->activityeditor)) {
        $data->activity = file_save_draft_area_files($data->activityeditor['itemid'], $context->id,
            'mod_processassign', 'activity', 0, ['subdirs' => true], $data->activity ?? '');
        $DB->set_field('processassign', 'activity', $data->activity, ['id' => $data->id]);
        $DB->set_field('processassign', 'activityformat', $data->activityformat, ['id' => $data->id]);
    }
}

function processassign_delete_instance($id) {
    global $DB;

    if (!$processassign = $DB->get_record('processassign', ['id' => $id])) {
        return false;
    }

    processassign_grade_item_delete($processassign);
    if (!empty($processassign->gradecategoryid)) {
        processassign_delete_grade_category($processassign);
    }
    $DB->delete_records('processassign_subs', ['processassignid' => $id]);
    $DB->delete_records('processassign_stages', ['processassignid' => $id]);
    $DB->delete_records('processassign', ['id' => $id]);

    return true;
}

function processassign_save_stages($processassignid, $data) {
    global $DB;

    $now = time();
    $stagecount = max(1, min(5, (int)($data->stagecount ?? 5)));
    $existing = $DB->get_records('processassign_stages', ['processassignid' => $processassignid], '', 'sortorder,id');
    $bystage = [];
    foreach ($existing as $stage) {
        $bystage[(int)$stage->sortorder] = $stage;
    }

    for ($i = 1; $i <= 5; $i++) {
        $name = trim($data->{'stage' . $i . 'name'} ?? '');
        $stagetype = clean_param($data->{'stage' . $i . 'type'} ?? 'custom', PARAM_ALPHANUMEXT);
        $instructionsdata = $data->{'stage' . $i . 'instructionseditor'}
            ?? $data->{'stage' . $i . 'instructions'}
            ?? '';
        $instructions = is_array($instructionsdata) ? ($instructionsdata['text'] ?? '') : $instructionsdata;
        $instructionsformat = is_array($instructionsdata) ? ($instructionsdata['format'] ?? FORMAT_HTML) : FORMAT_MOODLE;
        if (trim($instructions) === '') {
            $instructions = processassign_default_instructions_for_stage_type($stagetype);
            $instructionsformat = FORMAT_MOODLE;
        }
        $maxgrade = max(0, (int)($data->{'stage' . $i . 'maxgrade'} ?? 0));
        $duedate = (int)($data->{'stage' . $i . 'duedate'} ?? 0);
        $timelimit = max(0, (int)($data->{'stage' . $i . 'timelimit'} ?? 0));
        $submissiononlinetext = !empty($data->{'stage' . $i . 'submissiononlinetext'}) ? 1 : 0;
        $submissionfile = !empty($data->{'stage' . $i . 'submissionfile'}) ? 1 : 0;
        $maxfiles = max(1, (int)($data->{'stage' . $i . 'maxfiles'} ?? 5));
        $maxbytes = (int)($data->{'stage' . $i . 'maxbytes'} ?? 0);
        $acceptedfiletypes = clean_param($data->{'stage' . $i . 'acceptedfiletypes'} ?? '*', PARAM_RAW_TRIMMED);
        $wordlimitenabled = !empty($data->{'stage' . $i . 'wordlimitenabled'}) ? 1 : 0;
        $wordlimit = max(0, (int)($data->{'stage' . $i . 'wordlimit'} ?? 0));
        $requirefeedbackresponse = !empty($data->{'stage' . $i . 'requirefeedbackresponse'}) ? 1 : 0;
        $releasegrade = 1;
        $releasefeedback = 1;

        if ($i > $stagecount || $name === '') {
            if (isset($bystage[$i]) && !$DB->record_exists('processassign_subs', ['stageid' => $bystage[$i]->id])) {
                $DB->delete_records('processassign_stages', ['id' => $bystage[$i]->id]);
            }
            continue;
        }

        $record = (object)[
            'processassignid' => $processassignid,
            'sortorder' => $i,
            'stagetype' => $stagetype,
            'name' => $name,
            'instructions' => $instructions,
            'instructionsformat' => $instructionsformat,
            'maxgrade' => $maxgrade,
            'duedate' => $duedate,
            'timelimit' => $timelimit,
            'submissiononlinetext' => $submissiononlinetext,
            'submissionfile' => $submissionfile,
            'maxfiles' => $maxfiles,
            'maxbytes' => $maxbytes,
            'acceptedfiletypes' => $acceptedfiletypes === '' ? '*' : $acceptedfiletypes,
            'wordlimitenabled' => $wordlimitenabled,
            'wordlimit' => $wordlimit,
            'requirefeedbackresponse' => $requirefeedbackresponse,
            'releasegrade' => $releasegrade,
            'releasefeedback' => $releasefeedback,
            'timemodified' => $now,
        ];

        if (isset($bystage[$i])) {
            $record->id = $bystage[$i]->id;
            $DB->update_record('processassign_stages', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('processassign_stages', $record);
        }
    }
}

function processassign_get_coursemodule_info($coursemodule) {
    global $DB;

    if (!$processassign = $DB->get_record('processassign', ['id' => $coursemodule->instance],
            'id, name, intro, introformat')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $processassign->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('processassign', $processassign, $coursemodule->id, false);
    }
    return $info;
}

function processassign_grade_item_update($processassign, $grades = null) {
    processassign_require_gradebook();

    if (($processassign->gradebookmode ?? 'single') === 'category') {
        processassign_grade_item_delete($processassign, 0);
        processassign_update_stage_grade_items($processassign);
        return GRADE_UPDATE_OK;
    }

    processassign_delete_stage_grade_items($processassign);
    if (!empty($processassign->gradecategoryid)) {
        processassign_delete_grade_category($processassign);
    }

    $params = [
        'itemname' => $processassign->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => $processassign->grade,
        'grademin' => 0,
    ];

    return grade_update('mod/processassign', $processassign->course, 'mod', 'processassign',
        $processassign->id, 0, $grades, $params);
}

function processassign_grade_item_delete($processassign, $itemnumber = null) {
    processassign_require_gradebook();

    if ($itemnumber !== null) {
        return grade_update('mod/processassign', $processassign->course, 'mod', 'processassign',
            $processassign->id, $itemnumber, null, ['deleted' => 1]);
    }

    $status = GRADE_UPDATE_OK;
    for ($i = 0; $i <= 5; $i++) {
        $result = grade_update('mod/processassign', $processassign->course, 'mod', 'processassign',
            $processassign->id, $i, null, ['deleted' => 1]);
        if ($result !== GRADE_UPDATE_OK) {
            $status = $result;
        }
    }

    return $status;
}

function processassign_require_gradebook(): void {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->libdir . '/grade/grade_category.php');
    require_once($CFG->libdir . '/grade/grade_item.php');
}

function processassign_update_stage_grade_items($processassign): void {
    global $DB;

    processassign_require_gradebook();
    $category = processassign_ensure_grade_category($processassign);
    $stages = $DB->get_records('processassign_stages', ['processassignid' => $processassign->id], 'sortorder ASC');
    $activeitemnumbers = [];

    foreach ($stages as $stage) {
        $itemnumber = (int)$stage->sortorder;
        $activeitemnumbers[] = $itemnumber;
        $grades = processassign_get_stage_grades($processassign, $stage);
        grade_update('mod/processassign', $processassign->course, 'mod', 'processassign', $processassign->id,
            $itemnumber, $grades, [
                'itemname' => $stage->name,
                'gradetype' => GRADE_TYPE_VALUE,
                'grademax' => max(1, (float)$stage->maxgrade),
                'grademin' => 0,
            ]);
        processassign_move_grade_item_to_category($processassign, $itemnumber, $category->id);
    }

    for ($i = 1; $i <= 5; $i++) {
        if (!in_array($i, $activeitemnumbers, true)) {
            processassign_grade_item_delete($processassign, $i);
        }
    }
}

function processassign_ensure_grade_category($processassign): grade_category {
    global $DB;

    processassign_require_gradebook();
    $parentid = processassign_get_parent_grade_category_id($processassign);
    if (!empty($processassign->gradecategoryid)) {
        $category = grade_category::fetch(['id' => $processassign->gradecategoryid, 'courseid' => $processassign->course]);
        if ($category) {
            if ($category->fullname !== $processassign->name) {
                $category->fullname = $processassign->name;
                $category->update('mod/processassign');
            }
            if ($parentid && (int)$category->parent !== $parentid) {
                $category->set_parent($parentid);
            }
            return $category;
        }
    }

    $category = new grade_category([
        'courseid' => $processassign->course,
        'fullname' => $processassign->name,
        'aggregation' => GRADE_AGGREGATE_SUM,
    ], false);
    $category->apply_default_settings();
    $category->aggregation = GRADE_AGGREGATE_SUM;
    $category->aggregateonlygraded = 1;
    $category->insert('mod/processassign');
    if ($parentid && (int)$category->parent !== $parentid) {
        $category->set_parent($parentid);
    }

    $DB->set_field('processassign', 'gradecategoryid', $category->id, ['id' => $processassign->id]);
    $processassign->gradecategoryid = $category->id;

    return $category;
}

function processassign_get_parent_grade_category_id($processassign): int {
    $selected = (int)($processassign->gradecat ?? 0);
    if ($selected <= 0) {
        return 0;
    }

    $category = grade_category::fetch(['id' => $selected, 'courseid' => $processassign->course]);
    return $category ? (int)$category->id : 0;
}

function processassign_move_grade_item_to_category($processassign, int $itemnumber, int $categoryid): void {
    $item = grade_item::fetch([
        'courseid' => $processassign->course,
        'itemtype' => 'mod',
        'itemmodule' => 'processassign',
        'iteminstance' => $processassign->id,
        'itemnumber' => $itemnumber,
    ]);

    if ($item) {
        $item->set_parent($categoryid);
    }
}

function processassign_delete_stage_grade_items($processassign): void {
    for ($i = 1; $i <= 5; $i++) {
        processassign_grade_item_delete($processassign, $i);
    }
}

function processassign_delete_grade_category($processassign): void {
    global $DB;

    $category = grade_category::fetch(['id' => $processassign->gradecategoryid, 'courseid' => $processassign->course]);
    if ($category) {
        $coursecategory = grade_category::fetch_course_category($processassign->course);
        $category->delete('mod/processassign', $coursecategory->id);
    }
    $DB->set_field('processassign', 'gradecategoryid', 0, ['id' => $processassign->id]);
    $processassign->gradecategoryid = 0;
}

function processassign_get_stage_grades($processassign, $stage): array {
    global $DB;

    $grades = [];
    $records = $DB->get_records('processassign_subs', [
        'processassignid' => $processassign->id,
        'stageid' => $stage->id,
        'status' => PROCESSASSIGN_STATUS_GRADED,
    ]);
    foreach ($records as $record) {
        $grade = new stdClass();
        $grade->userid = $record->userid;
        $grade->rawgrade = $record->grade;
        $grades[$record->userid] = $grade;
    }

    return $grades;
}

function processassign_update_stage_grade($processassign, $stage, $submission): void {
    if (($processassign->gradebookmode ?? 'single') !== 'category') {
        return;
    }

    processassign_require_gradebook();
    processassign_ensure_grade_category($processassign);

    $grade = new stdClass();
    $grade->userid = $submission->userid;
    $grade->rawgrade = $submission->grade;
    grade_update('mod/processassign', $processassign->course, 'mod', 'processassign', $processassign->id,
        (int)$stage->sortorder, $grade, [
            'itemname' => $stage->name,
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => max(1, (float)$stage->maxgrade),
            'grademin' => 0,
        ]);
    processassign_move_grade_item_to_category($processassign, (int)$stage->sortorder, $processassign->gradecategoryid);
}

function processassign_update_grades($processassign, $userid = 0, $nullifnone = true) {
    global $DB;

    if (($processassign->gradebookmode ?? 'single') === 'category') {
        processassign_update_stage_grade_items($processassign);
        return;
    }

    if ($userid) {
        $grade = processassign_get_user_grade($processassign, $userid);
        processassign_grade_item_update($processassign, $grade);
        return;
    }

    $userids = $DB->get_fieldset_select('processassign_subs', 'DISTINCT userid',
        'processassignid = :processassignid', ['processassignid' => $processassign->id]);
    $grades = [];
    foreach ($userids as $gradeuserid) {
        $grade = processassign_get_user_grade($processassign, $gradeuserid);
        $grades[$gradeuserid] = $grade;
    }
    processassign_grade_item_update($processassign, $grades);
}

function processassign_get_user_grade($processassign, $userid) {
    global $DB;

    $stages = $DB->get_records('processassign_stages', ['processassignid' => $processassign->id]);
    $totalmax = 0;
    foreach ($stages as $stage) {
        $totalmax += (float)$stage->maxgrade;
    }

    $earned = 0;
    $submissions = $DB->get_records('processassign_subs',
        ['processassignid' => $processassign->id, 'userid' => $userid, 'status' => PROCESSASSIGN_STATUS_GRADED]);
    foreach ($submissions as $submission) {
        $earned += (float)$submission->grade;
    }

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = $totalmax > 0 ? ($earned / $totalmax) * $processassign->grade : 0;
    return $grade;
}

function processassign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE
            || !in_array($filearea, ['introattachment', 'activity', 'submission', 'feedback'], true)) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (in_array($filearea, ['introattachment', 'activity'], true)) {
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_processassign', $filearea, 0, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    $itemid = (int)array_shift($args);
    if (!$submission = $DB->get_record('processassign_subs', ['id' => $itemid])) {
        return false;
    }
    if ((int)$submission->processassignid !== (int)$cm->instance) {
        return false;
    }
    if (!$stage = $DB->get_record('processassign_stages', [
            'id' => $submission->stageid,
            'processassignid' => $cm->instance,
        ])) {
        return false;
    }

    $cangrade = has_capability('mod/processassign:grade', $context);
    if ($submission->userid != $USER->id && !$cangrade) {
        return false;
    }
    if ($filearea === 'feedback' && !$cangrade && empty($stage->releasefeedback)) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_processassign', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
