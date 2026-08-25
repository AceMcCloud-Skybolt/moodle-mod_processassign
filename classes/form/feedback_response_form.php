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
 * Form for a student's required response to stage feedback.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_processassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for a student's required response to stage feedback.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_response_form extends \moodleform {
    /**
     * Define the form elements.
     */
    public function definition() {
        $mform = $this->_form;
        $options = $this->_customdata['options'];

        $mform->addElement('hidden', 'submissionid');
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement(
            'editor',
            'feedbackresponseeditor',
            get_string('feedbackresponse', 'processassign'),
            null,
            $options['editor']
        );
        $mform->setType('feedbackresponseeditor', PARAM_RAW);
        $mform->addRule('feedbackresponseeditor', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(false, get_string('savefeedbackresponse', 'processassign'));
    }

    /**
     * Validate that a response was entered.
     *
     * @param array $data submitted form data
     * @param array $files submitted files
     * @return array field name => error message
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['feedbackresponseeditor']['text'] ?? '') === '') {
            $errors['feedbackresponseeditor'] = get_string('required');
        }

        return $errors;
    }
}
