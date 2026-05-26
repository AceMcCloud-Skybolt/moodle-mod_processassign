<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_processassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class feedback_response_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $options = $this->_customdata['options'];

        $mform->addElement('hidden', 'submissionid');
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement('editor', 'feedbackresponseeditor', get_string('feedbackresponse', 'processassign'), null,
            $options['editor']);
        $mform->setType('feedbackresponseeditor', PARAM_RAW);
        $mform->addRule('feedbackresponseeditor', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(false, get_string('savefeedbackresponse', 'processassign'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['feedbackresponseeditor']['text'] ?? '') === '') {
            $errors['feedbackresponseeditor'] = get_string('required');
        }

        return $errors;
    }
}
