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
 * View, submit and grade a Process Assignment activity.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_processassign\local\stage_manager;
use mod_processassign\local\view_controller;
use mod_processassign\local\view_builder;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/processassign/lib.php');
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

$cansubmit = has_capability('mod/processassign:submit', $context);
$cangrade = has_capability('mod/processassign:grade', $context);
if ($cangrade && ($action === 'view' || $action === 'submissions')) {
    $PAGE->set_secondary_active_tab('mod_processassign_submissions');
}

$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context' => $context,
];

$stages = stage_manager::get_stages($processassign->id);

if ($cansubmit && !$cangrade && $action === 'view' && $stages) {
    view_controller::handle_student_post($processassign, $cm, $course, $context, $stages, $editoroptions);
}

if ($action === 'grade' && $submissionid) {
    view_controller::handle_grade_view($processassign, $cm, $course, $context, $submissionid, $editoroptions);
}
if ($action === 'grader') {
    $submissionid = stage_manager::pick_grader_submissionid($processassign, $submissionid);
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
    view_controller::handle_grade_view($processassign, $cm, $course, $context, $submissionid, $editoroptions, true);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($processassign->name));
if (
    !empty($processassign->alwaysshowdescription)
        || empty($processassign->allowsubmissionsfromdate)
        || time() >= $processassign->allowsubmissionsfromdate
        || $cangrade
) {
    echo format_module_intro('processassign', $processassign, $cm->id);
    echo view_builder::render_activity_instructions($processassign, $context);
    echo view_builder::render_intro_attachments($context);
}

if (!$stages) {
    echo $OUTPUT->notification(get_string('nostages', 'processassign'), 'warning');
} else {
    if ($cangrade) {
        if ($action === 'submissions') {
            view_builder::render_teacher_table(
                $processassign,
                $cm,
                $context,
                $stages,
                $statusfilter,
                $stagefilter,
                $search
            );
        } else {
            view_builder::render_grading_summary($processassign, $cm, $context, $stages);
        }
    } else if ($cansubmit) {
        view_builder::render_student_view(
            $processassign,
            $cm,
            $course,
            $context,
            $stages,
            $cansubmit,
            $editoroptions,
            $editstageid
        );
    }
}

echo $OUTPUT->footer();
