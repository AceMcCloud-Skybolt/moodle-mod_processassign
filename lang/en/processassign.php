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
 * English language strings for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['aggregategrade'] = 'Aggregate grade';
$string['allstages'] = 'All stages';
$string['attemptreopenmethod_none'] = 'Never';
$string['availableafterfeedback'] = 'Available after previous stage is graded';
$string['awaitingfeedback'] = 'Awaiting feedback';
$string['awaitingresponse'] = 'Awaiting student response';
$string['bulknotyetavailable'] = 'Bulk stage actions are not available yet';
$string['currentgradebookgrade'] = 'Current grade in gradebook';
$string['currentstage'] = 'Current stage';
$string['draftsaved'] = 'Draft saved.';
$string['duedate'] = 'Due date';
$string['enablewordlimit'] = 'Enable word limit';
$string['feedback'] = 'Feedback';
$string['feedbackcomments'] = 'Feedback comments';
$string['feedbackfiles'] = 'Feedback files';
$string['feedbackresponse'] = 'Feedback response';
$string['feedbackresponsegate'] = 'Feedback response gate';
$string['feedbackresponsegate_desc'] = 'Use this when students should actively read and respond to feedback before moving forward. This makes feedback part of the learning process rather than an end-point comment.';
$string['feedbackresponserequired'] = 'Feedback response required';
$string['feedbackresponsesaved'] = 'Feedback response saved.';
$string['grade'] = 'Grade';
$string['grade_stage1_name'] = 'Stage 1';
$string['grade_stage2_name'] = 'Stage 2';
$string['grade_stage3_name'] = 'Stage 3';
$string['grade_stage4_name'] = 'Stage 4';
$string['grade_stage5_name'] = 'Stage 5';
$string['grade_submissions_name'] = 'Process assignment total';
$string['gradeactions'] = 'Grade actions';
$string['gradeall'] = 'Grade submissions';
$string['gradebookmode'] = 'Gradebook mode';
$string['gradebookmode:category'] = 'Stage grade category';
$string['gradebookmode:single'] = 'Single aggregate grade item';
$string['gradebookmode_help'] = 'Single grade creates one Moodle gradebook item, like a normal Assignment. Stage grade category creates a grade category named after this activity and places one grade item per configured stage inside it. In stage category mode, each stage maximum grade acts as the stage weighting, so stages worth 10 and 90 points create a 10/90 split inside the category.';
$string['gradebookmodewarning'] = 'Changing gradebook mode changes the grade items Moodle displays for this activity. Review the course gradebook after changing this setting, especially if staff have manually adjusted weights, hidden states, or locks.';
$string['graded'] = 'Graded';
$string['gradeitem:stage1'] = 'Stage 1';
$string['gradeitem:stage2'] = 'Stage 2';
$string['gradeitem:stage3'] = 'Stage 3';
$string['gradeitem:stage4'] = 'Stage 4';
$string['gradeitem:stage5'] = 'Stage 5';
$string['gradeitem:submissions'] = 'Process assignment total';
$string['grademustbebetween'] = 'Grade must be between 0 and {$a}.';
$string['gradenotificationbody'] = 'Feedback has been released for {$a->stage} in {$a->activity} ({$a->course}). View it at {$a->url}';
$string['gradenotificationsubject'] = 'Feedback released: {$a}';
$string['gradesaved'] = 'Grade and feedback saved.';
$string['gradestage'] = 'Grade stage';
$string['gradingstatus'] = 'Grading status';
$string['gradingsummary'] = 'Grading summary';
$string['hiddenfromstudents'] = 'Hidden from students';
$string['howprocessworks'] = 'How this process works';
$string['howprocessworksdesc'] = 'Complete stages in order. Each stage may require teacher feedback (and sometimes your response) before the next stage unlocks.';
$string['instructions'] = 'Instructions';
$string['late'] = 'Late';
$string['locked'] = 'Locked';
$string['lockreason:availability'] = 'Locked until submissions open.';
$string['lockreason:cutoff'] = 'Locked because the submission window has closed.';
$string['lockreason:feedbackresponse'] = 'Locked until you respond to feedback on the previous stage.';
$string['lockreason:previousstage'] = 'Locked until the previous stage is complete.';
$string['maxgrade'] = 'Maximum grade';
$string['messageprovider:grader_notification'] = 'Process assignment submission notifications';
$string['messageprovider:student_notification'] = 'Process assignment feedback notifications';
$string['modulename'] = 'Process assignment';
$string['modulename_help'] = 'Use a process assignment to collect and grade staged submissions over time.';
$string['modulenameplural'] = 'Process assignments';
$string['needsgrading'] = 'Needs grading';
$string['noattempt'] = 'No attempt';
$string['nogradablesubmissions'] = 'There are no submitted or graded stage submissions to show in the grading interface yet.';
$string['nostages'] = 'No stages have been configured yet.';
$string['notgraded'] = 'Not graded';
$string['notifystudent'] = 'Notify student';
$string['notopenyet'] = 'This stage unlocks after the previous stage has been graded.';
$string['notstarted'] = 'Not started';
$string['notsubmitted'] = 'Not submitted';
$string['nudge'] = 'Nudge';
$string['nudgebody'] = 'Please check {$a->stage} in {$a->activity}.';
$string['nudgesubject'] = 'Reminder: {$a}';
$string['numberofstages'] = 'Number of stages';
$string['planned'] = 'Planned';
$string['plannedfeature'] = 'Planned feature scaffolded for review.';
$string['pluginadministration'] = 'Process assignment administration';
$string['pluginname'] = 'Process assignment';
$string['previousstagehistory'] = 'Previous stage history';
$string['privacy:metadata'] = 'The Process assignment prototype stores staged submission text, files, feedback, and grades.';
$string['privacy:metadata:core_files'] = 'Files uploaded to process assignment submissions and feedback.';
$string['privacy:metadata:processassign_subs'] = 'Stores student stage submissions, grades, and feedback.';
$string['privacy:metadata:processassign_subs:feedback'] = 'Feedback released for the stage submission.';
$string['privacy:metadata:processassign_subs:feedbackresponse'] = 'The student response to released feedback.';
$string['privacy:metadata:processassign_subs:grade'] = 'The grade awarded for the stage submission.';
$string['privacy:metadata:processassign_subs:graderid'] = 'The user who graded the stage submission.';
$string['privacy:metadata:processassign_subs:processassignid'] = 'The process assignment instance.';
$string['privacy:metadata:processassign_subs:stageid'] = 'The stage that the submission belongs to.';
$string['privacy:metadata:processassign_subs:submissiontext'] = 'The online text submitted by the student.';
$string['privacy:metadata:processassign_subs:timefeedbackresponded'] = 'The time the student responded to feedback.';
$string['privacy:metadata:processassign_subs:timegraded'] = 'The time the stage submission was graded.';
$string['privacy:metadata:processassign_subs:timesubmitted'] = 'The time the stage submission was submitted.';
$string['privacy:metadata:processassign_subs:userid'] = 'The user who made the stage submission.';
$string['processassign:addinstance'] = 'Add a new process assignment';
$string['processassign:grade'] = 'Grade process assignment stages';
$string['processassign:submit'] = 'Submit to process assignment stages';
$string['processassignname'] = 'Process assignment name';
$string['prototypehint'] = 'Prototype note: this is a stage-aware submissions workflow. Some native Assignment actions are scaffolded for reviewer discussion but are not active yet.';
$string['requirefeedbackresponse'] = 'Require student response to feedback before next stage unlocks';
$string['requirefeedbackresponse_help'] = 'If enabled, the student must write a brief response after feedback is released before the next stage becomes available.';
$string['requirefeedbackresponseassignment'] = 'Require feedback response for all stages';
$string['requirefeedbackresponseassignment_help'] = 'If enabled, students must respond to feedback on each graded stage before the next stage unlocks. Stage-level settings remain available when this is disabled.';
$string['savefeedbackresponse'] = 'Save feedback response';
$string['scalegradingnotsupported'] = 'Process assignment currently supports point grading only. Scale grading will be considered after the gradebook model is finalised.';
$string['searchusers'] = 'Search users';
$string['stage'] = 'Stage';
$string['stagecount'] = 'Stages';
$string['stagecount_help'] = 'A process assignment is made up of up to five sequential stages. Choose how many stages to use and configure each one below. Students must complete each stage before the next one is unlocked.';
$string['stagefieldset'] = 'Stage {$a}';
$string['stagename'] = 'Stage name';
$string['stagenotavailable'] = 'This stage is not currently available for submission.';
$string['stages'] = 'Stages';
$string['stagesubmissiontypesnote'] = 'Submission types are configured per stage because different milestones may require different evidence, such as text for a proposal and files for a final product.';
$string['stagetype'] = 'Stage type';
$string['stagetype:custom'] = 'Custom';
$string['stagetype:draft'] = 'Draft';
$string['stagetype:final'] = 'Final submission';
$string['stagetype:media'] = 'Media prototype';
$string['stagetype:outline'] = 'Outline';
$string['stagetype:proposal'] = 'Proposal';
$string['stagetype:reflection'] = 'Reflection';
$string['stagetype:researchlog'] = 'Research log';
$string['stagetype:revisionplan'] = 'Revision plan';
$string['stagetypeinstructions:custom'] = '';
$string['stagetypeinstructions:draft'] = 'Submit a draft of your work. Include enough detail for feedback on structure, evidence, and direction.';
$string['stagetypeinstructions:final'] = 'Submit the final version of your work with all feedback incorporated.';
$string['stagetypeinstructions:media'] = 'Submit a storyboard, rough cut, prototype, or media sample that shows the current direction of your project.';
$string['stagetypeinstructions:outline'] = 'Submit an outline that shows the structure, main sections, and intended evidence for your work.';
$string['stagetypeinstructions:proposal'] = 'Submit a proposal that explains your topic, purpose, audience, planned approach, and any questions you need feedback on.';
$string['stagetypeinstructions:reflection'] = 'Explain what you changed, why you changed it, and what the process shows about your learning.';
$string['stagetypeinstructions:researchlog'] = 'Submit a research or reading log that summarises sources, key ideas, and how they may inform your work.';
$string['stagetypeinstructions:revisionplan'] = 'Respond to feedback and identify the specific revision tasks you will complete next.';
$string['status'] = 'Status';
$string['studentaction'] = 'Current action';
$string['studentactioncomplete'] = 'All stages are complete.';
$string['studentactionfeedbackresponse'] = 'Respond to feedback to unlock the next stage.';
$string['studentactionsubmit'] = 'Submit your current stage.';
$string['studentactionwaitfeedback'] = 'Wait for feedback on your submitted stage.';
$string['studentcanedit'] = 'Student can edit this stage submission';
$string['studentprogress'] = 'Progress';
$string['studentprogressvalue'] = 'Stage {$a->current} of {$a->total}';
$string['submission'] = 'Submission';
$string['submissionactions'] = 'Submission actions';
$string['submissionfiles'] = 'Submission files';
$string['submissionnotificationbody'] = '{$a->student} submitted {$a->stage} in {$a->activity} ({$a->course}). Review it at {$a->url}';
$string['submissionnotificationsubject'] = 'New process assignment submission: {$a}';
$string['submissionposition'] = 'Submission {$a->current} of {$a->total}';
$string['submissionreceipt'] = 'Submission receipt';
$string['submissionrequirements'] = 'Submission requirements';
$string['submissions'] = 'Submissions';
$string['submissionsaved'] = 'Submission saved.';
$string['submissionsclosed'] = 'Submissions are closed for this stage.';
$string['submissionsnotopen'] = 'Submissions open on {$a}.';
$string['submissionstatussummary'] = 'Submission status summary';
$string['submissionsummary'] = 'Submission summary';
$string['submissiontext'] = 'Submission text';
$string['submissiontyperequired'] = 'Enable online text, file submissions, or both.';
$string['submitstage'] = 'Submit stage';
$string['submitted'] = 'Submitted';
$string['submittedforgrading'] = 'Submitted for grading. You can edit this submission while the stage is still open.';
$string['teacherdashboard'] = 'Teacher dashboard';
$string['teacherreview'] = 'Teacher review';
$string['timeleft'] = '{$a} remaining';
$string['timelimitnotice'] = 'Prototype note: this time limit is stored for review but countdown enforcement is not implemented yet.';
$string['uploadorwrite'] = 'Upload a file, enter text, or do both.';
$string['viewgradebook'] = 'View gradebook';
$string['viewsubmission'] = 'View submission';
$string['wordlimitexceeded'] = 'The word limit for this stage is {$a->limit} words and this submission contains {$a->count} words.';
