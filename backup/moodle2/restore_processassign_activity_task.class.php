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
 * Restore task definition for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/backup/moodle2/restore_processassign_stepslib.php');

/**
 * Restore task for a Process Assignment activity.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class restore_processassign_activity_task extends restore_activity_task {

    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_processassign_activity_structure_step('processassign_structure', 'processassign.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return restore_decode_content[] decode contents
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('processassign', ['intro'], 'processassign'),
            new restore_decode_content('processassign_stages', ['instructions'], 'processassign_stage'),
            new restore_decode_content('processassign_subs', ['submissiontext', 'feedback', 'feedbackresponse'],
                'processassign_submission'),
        ];
    }

    /**
     * Define the decoding rules for links belonging to this activity.
     *
     * @return restore_decode_rule[] decode rules
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('PROCESSASSIGNVIEWBYID', '/mod/processassign/view.php?id=$1', 'course_module'),
            new restore_decode_rule('PROCESSASSIGNINDEX', '/mod/processassign/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Define the restore log rules for this activity.
     *
     * @return restore_log_rule[] log rules
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Define the course-level restore log rules for this activity.
     *
     * @return restore_log_rule[] log rules
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
