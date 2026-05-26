<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/processassign/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'processassign'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'processassign'));

if (!$processassigns = get_all_instances_in_course('processassign', $course)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'processassign')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name')];
foreach ($processassigns as $processassign) {
    $table->data[] = [html_writer::link(new moodle_url('/mod/processassign/view.php', ['id' => $processassign->coursemodule]),
        format_string($processassign->name))];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
