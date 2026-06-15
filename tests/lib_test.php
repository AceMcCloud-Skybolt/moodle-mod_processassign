<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_processassign;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/processassign/lib.php');

final class lib_test extends \advanced_testcase {

    protected function create_processassign(array $record = []): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_processassign');
        return $generator->create_instance(['course' => $course->id] + $record);
    }

    protected function get_stages(\stdClass $processassign): array {
        global $DB;
        return array_values($DB->get_records('processassign_stages',
            ['processassignid' => $processassign->id], 'sortorder ASC'));
    }

    public function test_generator_creates_default_stages(): void {
        $this->resetAfterTest();

        $processassign = $this->create_processassign();
        $stages = $this->get_stages($processassign);

        $this->assertCount(3, $stages);
        $this->assertSame('Stage 1', $stages[0]->name);
        $this->assertSame(1, (int)$stages[0]->submissiononlinetext);
        $this->assertSame(0, (int)$stages[0]->submissionfile);
    }

    public function test_save_stages_removes_unused_unsubmitted_stage(): void {
        global $DB;

        $this->resetAfterTest();

        $processassign = $this->create_processassign(['stagecount' => 3]);
        $data = (object)[
            'stagecount' => 2,
            'stage1name' => 'Plan',
            'stage1type' => 'proposal',
            'stage1instructions' => 'Plan instructions',
            'stage1maxgrade' => 25,
            'stage1submissiononlinetext' => 1,
            'stage2name' => 'Final',
            'stage2type' => 'final',
            'stage2instructions' => 'Final instructions',
            'stage2maxgrade' => 75,
            'stage2submissiononlinetext' => 1,
            'stage3name' => '',
        ];

        processassign_save_stages($processassign->id, $data);

        $stages = $DB->get_records('processassign_stages', ['processassignid' => $processassign->id], 'sortorder ASC');
        $this->assertCount(2, $stages);
        $this->assertSame(['Plan', 'Final'], array_values(array_map(fn($stage) => $stage->name, $stages)));
    }

    public function test_save_stages_keeps_stage_that_has_submission(): void {
        global $DB;

        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $processassign = $this->create_processassign(['stagecount' => 3]);
        $stages = $this->get_stages($processassign);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_processassign');
        $generator->create_stage_submission([
            'processassignid' => $processassign->id,
            'stageid' => $stages[2]->id,
            'userid' => $student->id,
        ]);

        $data = (object)[
            'stagecount' => 2,
            'stage1name' => 'Plan',
            'stage1type' => 'proposal',
            'stage1instructions' => 'Plan instructions',
            'stage1maxgrade' => 25,
            'stage1submissiononlinetext' => 1,
            'stage2name' => 'Final',
            'stage2type' => 'final',
            'stage2instructions' => 'Final instructions',
            'stage2maxgrade' => 75,
            'stage2submissiononlinetext' => 1,
            'stage3name' => '',
        ];

        processassign_save_stages($processassign->id, $data);

        $this->assertTrue($DB->record_exists('processassign_stages', ['id' => $stages[2]->id]));
    }

    public function test_get_user_grade_aggregates_graded_stages(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $processassign = $this->create_processassign([
            'stagecount' => 2,
            'stage1maxgrade' => 10,
            'stage2maxgrade' => 90,
            'grade' => 100,
        ]);
        $stages = $this->get_stages($processassign);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_processassign');
        $generator->create_stage_submission([
            'processassignid' => $processassign->id,
            'stageid' => $stages[0]->id,
            'userid' => $student->id,
            'grade' => 5,
            'status' => PROCESSASSIGN_STATUS_GRADED,
        ]);
        $generator->create_stage_submission([
            'processassignid' => $processassign->id,
            'stageid' => $stages[1]->id,
            'userid' => $student->id,
            'grade' => 81,
            'status' => PROCESSASSIGN_STATUS_GRADED,
        ]);

        $grade = processassign_get_user_grade($processassign, $student->id);

        $this->assertEqualsWithDelta(86.0, $grade->rawgrade, 0.00001);
    }

    public function test_get_user_grade_honours_nullifnone(): void {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $processassign = $this->create_processassign();

        $this->assertNull(processassign_get_user_grade($processassign, $student->id)->rawgrade);
        $this->assertEquals(0.0, processassign_get_user_grade($processassign, $student->id, false)->rawgrade);
    }

    public function test_global_feedback_response_is_not_saved_into_stage_rows(): void {
        $this->resetAfterTest();

        $processassign = $this->create_processassign([
            'stagecount' => 1,
            'requirefeedbackresponse' => 1,
            'stage1requirefeedbackresponse' => 0,
        ]);
        $stage = $this->get_stages($processassign)[0];

        $this->assertSame(0, (int)$stage->requirefeedbackresponse);
        $this->assertSame(1, (int)$processassign->requirefeedbackresponse);
    }
}
