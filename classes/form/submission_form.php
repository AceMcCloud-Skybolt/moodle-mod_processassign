<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_processassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class submission_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $stage = $this->_customdata['stage'];
        $options = $this->_customdata['options'];
        $processassign = $this->_customdata['processassign'];

        $mform->addElement('hidden', 'stageid');
        $mform->setType('stageid', PARAM_INT);
        $mform->setDefault('stageid', $stage->id);

        if (!empty($stage->submissiononlinetext)) {
            $mform->addElement('editor', 'submissioneditor', get_string('submissiontext', 'processassign'), null,
                $options['editor']);
            $mform->setType('submissioneditor', PARAM_RAW);
        }

        if (!empty($stage->submissionfile)) {
            $mform->addElement('filemanager', 'submissionfiles', get_string('submissionfiles', 'processassign'), null,
                $options['filemanager']);
        }

        if (!empty($processassign->requiresubmissionstatement)) {
            $mform->addElement('advcheckbox', 'submissionstatement',
                get_string('submissionstatement', 'assign'),
                get_string('submissionstatementdefault', 'assign'));
        }

        if (!empty($processassign->submissiondrafts)) {
            $buttonarray = [
                $mform->createElement('submit', 'savedraft', get_string('savechanges')),
                $mform->createElement('submit', 'submitstage', get_string('submitstage', 'processassign')),
            ];
            $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        } else {
            $this->add_action_buttons(false, get_string('submitstage', 'processassign'));
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $processassign = $this->_customdata['processassign'];
        $stage = $this->_customdata['stage'];
        $text = trim($data['submissioneditor']['text'] ?? '');
        $filecount = 0;
        if (!empty($stage->submissionfile)) {
            $fileinfo = file_get_draft_area_info($data['submissionfiles']);
            $filecount = $fileinfo['filecount'];
        }

        if ($text === '' && empty($filecount)) {
            if (!empty($stage->submissiononlinetext)) {
                $errors['submissioneditor'] = get_string('uploadorwrite', 'processassign');
            } else {
                $errors['submissionfiles'] = get_string('uploadorwrite', 'processassign');
            }
        }

        if (!empty($stage->wordlimitenabled) && !empty($stage->wordlimit) && $text !== '') {
            $count = count_words($text, FORMAT_HTML);
            if ($count > (int)$stage->wordlimit) {
                $errors['submissioneditor'] = get_string('wordlimitexceeded', 'processassign',
                    (object)['limit' => $stage->wordlimit, 'count' => $count]);
            }
        }

        if (!empty($processassign->requiresubmissionstatement) && empty($data['submissionstatement'])) {
            $errors['submissionstatement'] = get_string('submissionstatementrequired', 'assign');
        }

        return $errors;
    }
}
