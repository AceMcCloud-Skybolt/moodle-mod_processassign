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

        $this->standard_intro_elements();

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
        $mform->addElement('checkbox', 'alwaysshowdescription', get_string('alwaysshowdescription', 'assign'));
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assign');
        $mform->setDefault('alwaysshowdescription', 1);

        $mform->addElement('header', 'submissiontypes', get_string('submissiontypes', 'assign'));
        $mform->addElement('selectyesno', 'submissiononlinetext', get_string('onlinetext', 'assignsubmission_onlinetext'));
        $mform->setDefault('submissiononlinetext', 1);
        $mform->addElement('selectyesno', 'submissionfile', get_string('filesubmissions', 'assign'));
        $mform->setDefault('submissionfile', 1);

        $maxfiles = [];
        for ($i = 1; $i <= 20; $i++) {
            $maxfiles[$i] = $i;
        }
        $mform->addElement('select', 'maxfiles', get_string('maxfiles', 'assignsubmission_file'), $maxfiles);
        $mform->setDefault('maxfiles', 5);
        $mform->hideIf('maxfiles', 'submissionfile', 'eq', 0);

        $maxbytes = get_max_upload_sizes($CFG->maxbytes, 0, 0);
        $mform->addElement('select', 'maxbytes', get_string('maximumsubmissionsize', 'assignsubmission_file'), $maxbytes);
        $mform->setDefault('maxbytes', 0);
        $mform->hideIf('maxbytes', 'submissionfile', 'eq', 0);

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

        $mform->addElement('header', 'stageshdr', get_string('stages', 'processassign'));
        $mform->addHelpButton('stageshdr', 'stagecount', 'processassign');

        $stagecountoptions = [];
        for ($i = 1; $i <= 5; $i++) {
            $stagecountoptions[$i] = $i;
        }
        $mform->addElement('select', 'stagecount', get_string('numberofstages', 'processassign'), $stagecountoptions);
        $mform->setType('stagecount', PARAM_INT);
        $mform->setDefault('stagecount', 3);

        for ($i = 1; $i <= 5; $i++) {
            $mform->addElement('header', 'stagehdr' . $i, get_string('stagefieldset', 'processassign', $i));
            $mform->setExpanded('stagehdr' . $i, $i === 1);
            $mform->addElement('select', 'stage' . $i . 'type', get_string('stagetype', 'processassign'),
                processassign_stage_type_options());
            $mform->setType('stage' . $i . 'type', PARAM_ALPHANUMEXT);

            $mform->addElement('text', 'stage' . $i . 'name', get_string('stagename', 'processassign'), ['size' => '64']);
            $mform->setType('stage' . $i . 'name', PARAM_TEXT);
            if ($i === 1) {
                $mform->addRule('stage' . $i . 'name', get_string('required'), 'required', null, 'client');
            }

            $mform->addElement('editor', 'stage' . $i . 'instructionseditor',
                get_string('instructions', 'processassign'),
                null, $editoroptions);
            $mform->setType('stage' . $i . 'instructionseditor', PARAM_RAW);

            $mform->addElement('text', 'stage' . $i . 'maxgrade', get_string('maxgrade', 'processassign'), ['size' => '8']);
            $mform->setType('stage' . $i . 'maxgrade', PARAM_INT);
            $mform->setDefault('stage' . $i . 'maxgrade', $i === 5 ? 60 : 10);

            $mform->addElement('date_time_selector', 'stage' . $i . 'duedate', get_string('duedate', 'processassign'),
                ['optional' => true]);
            $mform->setDefault('stage' . $i . 'duedate', 0);

            $mform->addElement('advcheckbox', 'stage' . $i . 'wordlimitenabled',
                get_string('enablewordlimit', 'processassign'));
            $mform->addElement('text', 'stage' . $i . 'wordlimit',
                get_string('wordlimit', 'assignsubmission_onlinetext'), ['size' => '8']);
            $mform->setType('stage' . $i . 'wordlimit', PARAM_INT);
            $mform->hideIf('stage' . $i . 'wordlimitenabled', 'submissiononlinetext', 'eq', 0);
            $mform->hideIf('stage' . $i . 'wordlimit', 'submissiononlinetext', 'eq', 0);
            $mform->disabledIf('stage' . $i . 'wordlimit', 'stage' . $i . 'wordlimitenabled', 'notchecked');

            $mform->addElement('advcheckbox', 'stage' . $i . 'requirefeedbackresponse',
                get_string('requirefeedbackresponse', 'processassign'));
            $mform->addHelpButton('stage' . $i . 'requirefeedbackresponse', 'requirefeedbackresponse', 'processassign');
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

        if (empty($data['submissiononlinetext']) && empty($data['submissionfile'])) {
            $errors['submissiononlinetext'] = get_string('submissiontyperequired', 'processassign');
        }
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
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($data['stage' . $i . 'wordlimitenabled']) && empty($data['stage' . $i . 'wordlimit'])) {
                $errors['stage' . $i . 'wordlimit'] = get_string('required');
            }
        }

        return $errors;
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if (empty($defaultvalues['id'])) {
            $defaultvalues['stage1name'] = get_string('stage', 'processassign') . ' 1';
            $defaultvalues['stage2name'] = get_string('stage', 'processassign') . ' 2';
            $defaultvalues['stage3name'] = get_string('stage', 'processassign') . ' 3';
            $defaultvalues['stage1type'] = 'proposal';
            $defaultvalues['stage2type'] = 'draft';
            $defaultvalues['stage3type'] = 'final';
            $defaultvalues['stagecount'] = 3;
            return;
        }

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
            $defaultvalues['stage' . $i . 'wordlimitenabled'] = $stage->wordlimitenabled;
            $defaultvalues['stage' . $i . 'wordlimit'] = $stage->wordlimit;
            $defaultvalues['stage' . $i . 'requirefeedbackresponse'] = $stage->requirefeedbackresponse;
        }
    }
}
