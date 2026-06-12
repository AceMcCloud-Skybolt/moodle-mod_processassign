<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/processassign/lib.php');

class mod_processassign_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE, $PAGE;

        $mform = $this->_form;
        $PAGE->requires->css('/mod/processassign/styles.css');
        $editoroptions = [
            'context' => $this->context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $COURSE->maxbytes,
            'trusttext' => true,
        ];

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('processassignname', 'processassign'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description'), [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $COURSE->maxbytes,
        ]);

        $mform->addElement('editor', 'activityeditor', get_string('activityeditor', 'assign'),
            ['rows' => 10], [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $COURSE->maxbytes,
                'context' => $this->context,
                'subdirs' => true,
            ]);
        $mform->addHelpButton('activityeditor', 'activityeditor', 'assign');
        $mform->setType('activityeditor', PARAM_RAW);

        $mform->addElement('filemanager', 'introattachments', get_string('introattachments', 'assign'),
            null, ['subdirs' => 0, 'maxbytes' => $COURSE->maxbytes]);
        $mform->addHelpButton('introattachments', 'introattachments', 'assign');

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate',
            get_string('allowsubmissionsfromdate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assign');
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('duedate', 'duedate', 'assign');
        $mform->addElement('date_time_selector', 'cutoffdate', get_string('cutoffdate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assign');
        $mform->addElement('date_time_selector', 'gradingduedate', get_string('gradingduedate', 'assign'),
            ['optional' => true]);
        $mform->addHelpButton('gradingduedate', 'gradingduedate', 'assign');
        $mform->addElement('duration', 'timelimit', get_string('timelimit', 'assign'), ['optional' => true]);
        $mform->addHelpButton('timelimit', 'timelimit', 'assign');
        $mform->addElement('static', 'timelimitnotice', '', get_string('timelimitnotice', 'processassign'));
        $mform->addElement('checkbox', 'alwaysshowdescription', get_string('alwaysshowdescription', 'assign'));
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assign');
        $mform->setDefault('alwaysshowdescription', 1);

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assign'));
        $mform->addElement('selectyesno', 'submissiondrafts', get_string('submissiondrafts', 'assign'));
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'assign');
        $mform->setDefault('submissiondrafts', 0);
        $mform->addElement('selectyesno', 'requiresubmissionstatement',
            get_string('requiresubmissionstatement', 'assign'));
        $mform->addHelpButton('requiresubmissionstatement', 'requiresubmissionstatement', 'assign');
        $mform->setDefault('requiresubmissionstatement', 0);
        $maxattempts = [-1 => get_string('unlimited')];
        for ($i = 1; $i <= 10; $i++) {
            $maxattempts[$i] = $i;
        }
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'assign'), $maxattempts);
        $mform->addHelpButton('maxattempts', 'maxattempts', 'assign');
        $mform->setDefault('maxattempts', 1);
        $mform->addElement('select', 'attemptreopenmethod', get_string('attemptreopenmethod', 'assign'),
            processassign_attempt_reopen_options());
        $mform->addHelpButton('attemptreopenmethod', 'attemptreopenmethod', 'assign');
        $mform->setDefault('attemptreopenmethod', 'manual');

        $mform->addElement('header', 'notifications', get_string('notifications', 'assign'));
        $mform->addElement('selectyesno', 'sendnotifications', get_string('sendnotifications', 'assign'));
        $mform->setDefault('sendnotifications', 0);
        $mform->addElement('selectyesno', 'sendstudentnotifications',
            get_string('sendstudentnotificationsdefault', 'assign'));
        $mform->setDefault('sendstudentnotifications', 1);

        $mform->addElement('header', 'feedbacktypes', get_string('feedbacktypes', 'assign'));
        $mform->addElement('selectyesno', 'feedbackcomments', get_string('feedbackcomments', 'processassign'));
        $mform->setDefault('feedbackcomments', 1);
        $mform->addElement('selectyesno', 'feedbackfiles', get_string('feedbackfiles', 'processassign'));
        $mform->setDefault('feedbackfiles', 0);
        $maxfeedbackfiles = [];
        for ($i = 1; $i <= 20; $i++) {
            $maxfeedbackfiles[$i] = $i;
        }
        $mform->addElement('select', 'feedbackmaxfiles', get_string('maxfiles', 'assignfeedback_file'), $maxfeedbackfiles);
        $mform->setDefault('feedbackmaxfiles', 5);
        $mform->hideIf('feedbackmaxfiles', 'feedbackfiles', 'eq', 0);
        $maxfeedbackbytes = get_max_upload_sizes($CFG->maxbytes, 0, 0);
        $mform->addElement('select', 'feedbackmaxbytes', get_string('maximumsize', 'assignfeedback_file'),
            $maxfeedbackbytes);
        $mform->setDefault('feedbackmaxbytes', 0);
        $mform->hideIf('feedbackmaxbytes', 'feedbackfiles', 'eq', 0);

        $mform->addElement('header', 'stageshdr', get_string('stages', 'processassign'));
        $mform->addHelpButton('stageshdr', 'stagecount', 'processassign');
        $mform->addElement('static', 'stagesubmissiontypesnote', '', get_string('stagesubmissiontypesnote', 'processassign'));

        $stagecountoptions = [];
        for ($i = 1; $i <= 5; $i++) {
            $stagecountoptions[$i] = $i;
        }
        $mform->addElement('select', 'stagecount', get_string('numberofstages', 'processassign'), $stagecountoptions);
        $mform->setType('stagecount', PARAM_INT);
        $mform->setDefault('stagecount', 3);
        $mform->addElement('html', html_writer::div(
            html_writer::tag('strong', get_string('feedbackresponsegate', 'processassign')) . html_writer::empty_tag('br') .
            get_string('feedbackresponsegate_desc', 'processassign'),
            'alert alert-info processassign-feedback-gate'
        ));
        $mform->addElement('advcheckbox', 'requirefeedbackresponse',
            get_string('requirefeedbackresponseassignment', 'processassign'));
        $mform->addHelpButton('requirefeedbackresponse', 'requirefeedbackresponseassignment', 'processassign');
        $mform->setDefault('requirefeedbackresponse', 0);
        $hideforstagecount = function(string $elementname, int $stage) use ($mform): void {
            for ($count = 1; $count < $stage; $count++) {
                $mform->hideIf($elementname, 'stagecount', 'eq', (string)$count);
            }
        };

        for ($i = 1; $i <= 5; $i++) {
            $mform->addElement('header', 'stagehdr' . $i, get_string('stagefieldset', 'processassign', $i));
            $mform->setExpanded('stagehdr' . $i, $i === 1);
            if ($i > 1) {
                $hideforstagecount('stagehdr' . $i, $i);
            }
            $mform->addElement('select', 'stage' . $i . 'type', get_string('stagetype', 'processassign'),
                processassign_stage_type_options());
            $mform->setType('stage' . $i . 'type', PARAM_ALPHANUMEXT);
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'type', $i);
            }

            $mform->addElement('text', 'stage' . $i . 'name', get_string('stagename', 'processassign'), ['size' => '64']);
            $mform->setType('stage' . $i . 'name', PARAM_TEXT);
            if ($i === 1) {
                $mform->addRule('stage' . $i . 'name', get_string('required'), 'required', null, 'client');
            }
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'name', $i);
            }

            $mform->addElement('editor', 'stage' . $i . 'instructionseditor',
                get_string('instructions', 'processassign'),
                null, $editoroptions);
            $mform->setType('stage' . $i . 'instructionseditor', PARAM_RAW);
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'instructionseditor', $i);
            }

            $mform->addElement('text', 'stage' . $i . 'maxgrade', get_string('maxgrade', 'processassign'), ['size' => '8']);
            $mform->setType('stage' . $i . 'maxgrade', PARAM_INT);
            $mform->setDefault('stage' . $i . 'maxgrade', $i === 5 ? 60 : 10);
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'maxgrade', $i);
            }

            $mform->addElement('date_time_selector', 'stage' . $i . 'duedate', get_string('duedate', 'processassign'),
                ['optional' => true]);
            $mform->setDefault('stage' . $i . 'duedate', 0);
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'duedate', $i);
            }
            $mform->addElement('duration', 'stage' . $i . 'timelimit', get_string('timelimit', 'assign'),
                ['optional' => true]);
            $mform->addElement('static', 'stage' . $i . 'timelimitnotice', '', get_string('timelimitnotice', 'processassign'));
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'timelimit', $i);
                $hideforstagecount('stage' . $i . 'timelimitnotice', $i);
            }

            $mform->addElement('selectyesno', 'stage' . $i . 'submissiononlinetext',
                get_string('onlinetext', 'assignsubmission_onlinetext'));
            $mform->setDefault('stage' . $i . 'submissiononlinetext', 1);
            $mform->addElement('selectyesno', 'stage' . $i . 'submissionfile', get_string('filesubmissions', 'assign'));
            $mform->setDefault('stage' . $i . 'submissionfile', 1);
            $stagefilecounts = [];
            for ($filecount = 1; $filecount <= 20; $filecount++) {
                $stagefilecounts[$filecount] = $filecount;
            }
            $mform->addElement('select', 'stage' . $i . 'maxfiles', get_string('maxfiles', 'assignsubmission_file'),
                $stagefilecounts);
            $mform->setDefault('stage' . $i . 'maxfiles', 5);
            $mform->addElement('select', 'stage' . $i . 'maxbytes',
                get_string('maximumsubmissionsize', 'assignsubmission_file'), get_max_upload_sizes($CFG->maxbytes, 0, 0));
            $mform->setDefault('stage' . $i . 'maxbytes', 0);
            $mform->addElement('filetypes', 'stage' . $i . 'acceptedfiletypes',
                get_string('acceptedfiletypes', 'assignsubmission_file'));
            $mform->setDefault('stage' . $i . 'acceptedfiletypes', '*');
            $mform->hideIf('stage' . $i . 'maxfiles', 'stage' . $i . 'submissionfile', 'eq', 0);
            $mform->hideIf('stage' . $i . 'maxbytes', 'stage' . $i . 'submissionfile', 'eq', 0);
            $mform->hideIf('stage' . $i . 'acceptedfiletypes', 'stage' . $i . 'submissionfile', 'eq', 0);
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'submissiononlinetext', $i);
                $hideforstagecount('stage' . $i . 'submissionfile', $i);
                $hideforstagecount('stage' . $i . 'maxfiles', $i);
                $hideforstagecount('stage' . $i . 'maxbytes', $i);
                $hideforstagecount('stage' . $i . 'acceptedfiletypes', $i);
            }

            $mform->addElement('advcheckbox', 'stage' . $i . 'wordlimitenabled',
                get_string('enablewordlimit', 'processassign'));
            $mform->addElement('text', 'stage' . $i . 'wordlimit',
                get_string('wordlimit', 'assignsubmission_onlinetext'), ['size' => '8']);
            $mform->setType('stage' . $i . 'wordlimit', PARAM_INT);
            $mform->hideIf('stage' . $i . 'wordlimitenabled', 'stage' . $i . 'submissiononlinetext', 'eq', 0);
            $mform->hideIf('stage' . $i . 'wordlimit', 'stage' . $i . 'submissiononlinetext', 'eq', 0);
            $mform->disabledIf('stage' . $i . 'wordlimit', 'stage' . $i . 'wordlimitenabled', 'notchecked');

            $mform->addElement('advcheckbox', 'stage' . $i . 'requirefeedbackresponse',
                get_string('requirefeedbackresponse', 'processassign'));
            $mform->addHelpButton('stage' . $i . 'requirefeedbackresponse', 'requirefeedbackresponse', 'processassign');
            $mform->hideIf('stage' . $i . 'requirefeedbackresponse', 'requirefeedbackresponse', 'checked');
            if ($i > 1) {
                $hideforstagecount('stage' . $i . 'wordlimitenabled', $i);
                $hideforstagecount('stage' . $i . 'wordlimit', $i);
                $hideforstagecount('stage' . $i . 'requirefeedbackresponse', $i);
            }
        }

        $this->standard_grading_coursemodule_elements();
        $mform->addElement('select', 'gradebookmode', get_string('gradebookmode', 'processassign'),
            processassign_gradebook_mode_options());
        $mform->addHelpButton('gradebookmode', 'gradebookmode', 'processassign');
        $mform->setDefault('gradebookmode', 'single');
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['cutoffdate']) && !empty($data['allowsubmissionsfromdate'])
                && $data['cutoffdate'] < $data['allowsubmissionsfromdate']) {
            $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
        }
        if (!empty($data['duedate']) && !empty($data['allowsubmissionsfromdate'])
                && $data['duedate'] < $data['allowsubmissionsfromdate']) {
            $errors['duedate'] = get_string('duedatevalidation', 'assign');
        }
        if (!empty($data['cutoffdate']) && !empty($data['duedate']) && $data['cutoffdate'] < $data['duedate']) {
            $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
        }
        if (!empty($data['gradingduedate']) && !empty($data['duedate']) && $data['gradingduedate'] < $data['duedate']) {
            $errors['gradingduedate'] = get_string('gradingdueduedatevalidation', 'assign');
        }
        if (!empty($data['timelimit']) && $data['timelimit'] < 0) {
            $errors['timelimit'] = get_string('invaliddata', 'error');
        }
        for ($i = 1; $i <= 5; $i++) {
            if ($i > (int)$data['stagecount']) {
                continue;
            }
            if (empty($data['stage' . $i . 'submissiononlinetext']) && empty($data['stage' . $i . 'submissionfile'])) {
                $errors['stage' . $i . 'submissiononlinetext'] = get_string('submissiontyperequired', 'processassign');
            }
            if (!empty($data['stage' . $i . 'wordlimitenabled']) && empty($data['stage' . $i . 'wordlimit'])) {
                $errors['stage' . $i . 'wordlimit'] = get_string('required');
            }
            if (!empty($data['stage' . $i . 'timelimit']) && $data['stage' . $i . 'timelimit'] < 0) {
                $errors['stage' . $i . 'timelimit'] = get_string('invaliddata', 'error');
            }
        }

        return $errors;
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if (empty($defaultvalues['id'])) {
            $defaultvalues['activityeditor'] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $defaultvalues['stage1name'] = get_string('stage', 'processassign') . ' 1';
            $defaultvalues['stage2name'] = get_string('stage', 'processassign') . ' 2';
            $defaultvalues['stage3name'] = get_string('stage', 'processassign') . ' 3';
            $defaultvalues['stage1type'] = 'proposal';
            $defaultvalues['stage2type'] = 'draft';
            $defaultvalues['stage3type'] = 'final';
            $defaultvalues['stagecount'] = 3;
            for ($i = 1; $i <= 5; $i++) {
                $defaultvalues['stage' . $i . 'submissiononlinetext'] = 1;
                $defaultvalues['stage' . $i . 'submissionfile'] = 1;
                $defaultvalues['stage' . $i . 'maxfiles'] = 5;
                $defaultvalues['stage' . $i . 'maxbytes'] = 0;
                $defaultvalues['stage' . $i . 'acceptedfiletypes'] = '*';
            }
            return;
        }

        $draftitemid = file_get_submitted_draft_itemid('introattachments');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_processassign', 'introattachment',
            0, ['subdirs' => 0]);
        $defaultvalues['introattachments'] = $draftitemid;

        $activitydraftitemid = file_get_submitted_draft_itemid('activityeditor');
        $defaultvalues['activityeditor'] = [
            'text' => file_prepare_draft_area($activitydraftitemid, $this->context->id, 'mod_processassign',
                'activity', 0, ['subdirs' => true], $defaultvalues['activity'] ?? ''),
            'format' => $defaultvalues['activityformat'] ?? FORMAT_HTML,
            'itemid' => $activitydraftitemid,
        ];

        $stages = $DB->get_records('processassign_stages', ['processassignid' => $defaultvalues['id']], 'sortorder ASC');
        $defaultvalues['stagecount'] = max(1, min(5, count($stages)));
        foreach ($stages as $stage) {
            $i = (int)$stage->sortorder;
            if ($i < 1 || $i > 5) {
                continue;
            }
            $defaultvalues['stage' . $i . 'type'] = $stage->stagetype;
            $defaultvalues['stage' . $i . 'name'] = $stage->name;
            $defaultvalues['stage' . $i . 'instructionseditor'] = [
                'text' => $stage->instructions,
                'format' => FORMAT_HTML,
            ];
            $defaultvalues['stage' . $i . 'maxgrade'] = $stage->maxgrade;
            $defaultvalues['stage' . $i . 'duedate'] = $stage->duedate;
            $defaultvalues['stage' . $i . 'timelimit'] = $stage->timelimit;
            $defaultvalues['stage' . $i . 'submissiononlinetext'] = $stage->submissiononlinetext;
            $defaultvalues['stage' . $i . 'submissionfile'] = $stage->submissionfile;
            $defaultvalues['stage' . $i . 'maxfiles'] = $stage->maxfiles;
            $defaultvalues['stage' . $i . 'maxbytes'] = $stage->maxbytes;
            $defaultvalues['stage' . $i . 'acceptedfiletypes'] = $stage->acceptedfiletypes;
            $defaultvalues['stage' . $i . 'wordlimitenabled'] = $stage->wordlimitenabled;
            $defaultvalues['stage' . $i . 'wordlimit'] = $stage->wordlimit;
            $defaultvalues['stage' . $i . 'requirefeedbackresponse'] = $stage->requirefeedbackresponse;
        }
    }
}
