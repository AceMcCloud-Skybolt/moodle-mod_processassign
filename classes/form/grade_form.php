<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_processassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class grade_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $stage = $this->_customdata['stage'];
        $options = $this->_customdata['options'];
        $processassign = $this->_customdata['processassign'];
        $gradinginstance = $this->_customdata['gradinginstance'] ?? null;
        $showshownext = !empty($this->_customdata['showshownext']);

        $mform->addElement('hidden', 'submissionid');
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement('static', 'maxgradedisplay', get_string('maxgrade', 'processassign'), $stage->maxgrade);
        if ($gradinginstance) {
            $mform->addElement('grading', 'advancedgrading', get_string('grade', 'processassign'),
                ['gradinginstance' => $gradinginstance]);
            $mform->addElement('hidden', 'advancedgradinginstanceid', $gradinginstance->get_id());
            $mform->setType('advancedgradinginstanceid', PARAM_INT);
        } else {
            $mform->addElement('text', 'grade', get_string('grade', 'processassign'), ['size' => '8']);
            $mform->setType('grade', PARAM_FLOAT);
            $mform->addRule('grade', get_string('required'), 'required', null, 'client');
        }

        if (!empty($processassign->feedbackcomments)) {
            $mform->addElement('editor', 'feedback', get_string('feedback', 'processassign'), null, $options['editor']);
            $mform->setType('feedback', PARAM_RAW);
        }
        if (!empty($processassign->feedbackfiles)) {
            $mform->addElement('filemanager', 'feedbackfiles', get_string('feedbackfiles', 'processassign'),
                null, $options['feedbackfilemanager']);
        }

        $mform->addElement('advcheckbox', 'notifystudent', get_string('notifystudent', 'processassign'));
        $mform->setDefault('notifystudent', 1);

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if ($showshownext) {
            $buttons[] = $mform->createElement('submit', 'saveandshownext', get_string('savenext', 'assign'));
        }
        $buttons[] = $mform->createElement('reset', 'resetbutton', get_string('reset'));
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $stage = $this->_customdata['stage'];
        if (!empty($this->_customdata['gradinginstance'])) {
            return $errors;
        }
        $grade = (float)($data['grade'] ?? 0);

        if ($grade < 0 || $grade > (float)$stage->maxgrade) {
            $errors['grade'] = get_string('grademustbebetween', 'processassign', $stage->maxgrade);
        }

        return $errors;
    }
}
