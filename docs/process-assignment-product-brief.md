# Process Assignment Product Brief

## Purpose of this document

This brief explains the educational and operational rationale for a Moodle-native **Process assignment** activity. It is intended to help developers, learning designers, academics, and support staff critique the concept before becoming buried in implementation detail.

The current GitHub prototype demonstrates one possible direction. This document describes the underlying problem, target use cases, minimum viable requirements, and key architecture questions.

## Problem Statement

Universities increasingly need assessment designs that make student learning processes visible, not just final artefacts. This need has become more urgent with the widespread availability of generative AI tools.

Traditional assignment submission often captures only the final product: a file upload, online text, or media artefact. Staff then grade that artefact and the result flows into the Gradebook. This model can still work for many tasks, but it is less effective when educators want to assess how students planned, drafted, responded to feedback, revised, reflected, and developed their work over time.

Process-focused assessment supports better teaching because it creates structured feedback loops. Students submit interim evidence, receive feedback while there is still time to act on it, and demonstrate how they used that feedback in later work. Staff can identify misunderstandings earlier and support students before the final deadline.

The product goal is not simply to add more submissions. The goal is to make staged learning visible, assessable, and manageable inside Moodle.

## Current Moodle Gap

Moodle Assignment already supports useful features such as attempts, due dates, file submissions, online text, feedback, rubrics, marking guides, and Gradebook integration. However, it does not provide a clear staged workflow where each milestone can have its own instructions, submission window, feedback, grading method, unlock condition, and student response requirement.

Current workarounds usually involve one of the following:

- Creating several separate Assignment activities, one for each milestone.
- Using one Assignment with multiple attempts, but relying on instructions and manual interpretation to distinguish stages.
- Creating a Gradebook category manually and placing several Assignment activities inside it.
- Combining Assignment with Forums, Workshops, Journals, Checklists, or external tools.
- Tracking student progress manually through spreadsheets, email, or ad hoc Moodle reports.

These workarounds can function, but they fragment the experience. Students see multiple activities instead of one coherent process. Staff must manage multiple grade items, dates, instructions, and feedback points. Learning designers and support teams often need to explain the setup repeatedly.

A Process assignment should preserve the familiar Moodle Assignment mental model while adding explicit stages.

## Why Not External Tools?

External platforms can provide polished staged-assessment workflows, but they introduce practical and strategic costs:

- **Licensing cost:** commercial tools may be expensive, especially at institutional scale.
- **Training burden:** staff and students must learn another platform and another workflow.
- **Student friction:** students are pushed outside the LMS, often for a core assessment task.
- **Single sign-on and integration overhead:** authentication, enrolment, groups, and role mapping require configuration and maintenance.
- **Gradebook complexity:** grades and feedback may need to sync back into Moodle, or staff must manually transfer marks.
- **Data governance:** student submissions, feedback, media, and learning-process evidence may sit outside existing Moodle governance arrangements.
- **Support burden:** university support teams must troubleshoot a second system and decide where responsibility sits.

The strategic argument for a Moodle-native solution is that staged process assessment should feel like part of the LMS, not an add-on bolted beside it.

## Target Users

### Academics and Unit Coordinators

Academics need to design assessment tasks that guide students through a process, provide timely feedback, and keep grading manageable. They need setup options that are powerful but not overwhelming.

### Tutors and Markers

Tutors need a clear view of who has submitted each stage, who needs feedback, who is waiting for feedback release, and who has responded to feedback. They need stage-aware grading workflows without needing to decode multiple separate activities.

### Students

Students need a clear timeline of what is due, what has been submitted, what feedback has been received, what they need to respond to, and what unlocks next. The activity should reduce uncertainty and support planning.

### Learning Designers

Learning designers need a Moodle-native pattern they can recommend across disciplines. They need reusable examples, stage templates, and a workflow that supports scaffolded learning without requiring extensive bespoke configuration.

### Moodle Administrators and Support Staff

Administrators need a plugin that fits Moodle conventions: capabilities, privacy API, backup/restore, Gradebook integration, activity completion, logs, events, message providers, and upgrade safety.

## Core Use Cases

### Essay Development

A student submits a thesis statement or introduction, receives feedback, responds to that feedback, and then unlocks a draft or final essay stage. The design encourages early thinking and revision instead of a single final upload.

Example sequence:

1. Thesis statement.
2. Introduction draft.
3. Full draft.
4. Revision plan.
5. Final essay.

### Research Proposal

A student builds a proposal in stages, allowing staff to identify weak research questions or unsuitable methods before the final submission.

Example sequence:

1. Background/problem statement.
2. Proposed research question.
3. Annotated bibliography.
4. Method or project plan.
5. Final proposal.

### Project-Based Learning

Students submit evidence of planning, prototyping, testing, refinement, and final output. Staff can grade the process as well as the final artefact.

Example sequence:

1. Project plan.
2. Prototype or wireframe.
3. Feedback response.
4. Revised prototype.
5. Final project.

### Media and Multimodal Assessment

Students move from concept to storyboard to rough cut to final media artefact, with feedback opportunities before high-effort production work is complete.

Example sequence:

1. Concept pitch.
2. Storyboard.
3. Rough cut.
4. Revision reflection.
5. Final video, podcast, or media artefact.

### Reflective Portfolio

Students submit recurring pieces of evidence and reflection, showing development across a teaching period. This supports metacognition and learning transfer.

Example sequence:

1. Learning goals.
2. Evidence checkpoint.
3. Feedback response.
4. Revised evidence.
5. Final reflection.

### Professional Practice or Placement Evidence

Students can submit logs, plans, supervisor feedback, reflections, and final artefacts in a structured order. Staff can assess readiness and intervene earlier.

## MVP Requirements

The minimum viable version should focus on a Moodle-native staged assignment workflow rather than trying to solve every process-assessment scenario.

### Activity Setup

- Staff can create a Process assignment from the Moodle activity picker.
- Staff can configure a fixed number of ordered stages.
- Each stage has a clear title and instructions.
- Stage instructions support the Moodle editor.
- Staff can configure per-stage availability, due date, and cutoff behaviour.
- Staff can choose which submission types are enabled per stage:
  - online text
  - file submissions
- Staff can configure file limits, maximum file size, accepted file types, and optional word limits where relevant.

### Student Workflow

- Students see the full staged pathway, not just the current upload box.
- Students can clearly identify:
  - current stage
  - completed stages
  - locked future stages
  - due dates
  - feedback status
  - next required action
- Students submit only the currently unlocked stage.
- Submitted stages lock down by default, with any edit/resubmit behaviour controlled by settings.
- Students can view prior submissions and released feedback.
- Where enabled, students must respond to feedback before the next stage unlocks.

### Feedback and Grading

- Teachers can grade and provide feedback per stage.
- Teachers can choose whether stage feedback requires a student response.
- Teachers can use Moodle Advanced grading methods, such as Rubric and Marking guide, for each stage where feasible.
- Teachers can see which students are:
  - not started
  - submitted and awaiting feedback
  - awaiting student feedback response
  - late
  - complete
- Teachers can filter or sort by stage status.

### Gradebook Integration

The MVP should support two gradebook models:

1. **Single grade item mode:** the activity appears as one grade item in the Moodle Gradebook, similar to a normal Assignment.
2. **Stage category mode:** the activity can create or manage a Gradebook category with one child grade item per configured stage.

Stage category mode should support assessment patterns such as:

- Essay category worth 50% of a unit.
- Thesis statement worth 10% of the essay category.
- Final submission worth 90% of the essay category.

The default should be simple and familiar. More complex gradebook structures should be optional and clearly explained.

### Moodle Integration

The MVP should follow Moodle conventions for:

- capabilities
- activity completion
- Gradebook
- Advanced grading
- privacy API
- backup and restore
- logs/events
- message providers
- course index and activity navigation
- Moodle editor and file API

## Future and Non-MVP Ideas

These ideas are valuable but should not distract from proving the core staged assignment model.

### Stage Templates

Provide reusable templates for common assessment designs:

- essay draft sequence
- research proposal
- media project
- reflective portfolio
- design prototype
- lab report

Templates should scaffold setup but remain editable.

### Peer Review

Peer review could be added as a stage type or optional stage activity. This may overlap with Moodle Workshop, so the design would need careful scoping.

### Analytics Dashboard

A dashboard could highlight bottlenecks and risk signals:

- many students stuck on one stage
- overdue feedback
- students repeatedly late
- students who have not responded to feedback
- stages with unusually low grades

The dashboard should include actions, not just reports. For example, staff should be able to nudge students or filter directly to a marking queue.

### Nudges and Messaging

Teachers could send Moodle messages to students based on stage status. This should use Moodle message providers rather than direct email in a production version.

### Group Process Assignments

There may be a future relationship with a group assignment workflow where groups move through staged submissions together. This should be treated as related but separate until the individual process assignment model is stable.

### Process Evidence Integrations

Possible integrations could include writing analytics, version history, media capture, external repositories, or tools such as writing-process detection systems. These should be treated carefully due to privacy, ethics, governance, and student trust considerations.

### AI Transparency and Reflection

The activity could include structured reflection prompts about how students used tools, feedback, research, collaboration, and revision. This may be more pedagogically useful than attempting to detect AI use.

## Non-Goals for MVP

The MVP should not attempt to:

- replicate every feature of Moodle Assignment immediately
- replace Moodle Workshop
- replace full portfolio tools
- automatically detect generative AI use
- automatically judge whether a student's process is authentic
- create complex grade redistribution formulas
- become a general workflow engine for all activities
- solve group assignment management at the same time

The first goal is a robust, understandable, Moodle-native staged assignment.

## Pedagogical Rationale

A staged assignment supports:

- formative feedback loops
- scaffolded learning
- revision and metacognition
- earlier identification of misunderstandings
- reduced student anxiety
- greater equity for students unfamiliar with the task genre
- clearer expectations
- authentic professional workflows

The activity should encourage students to treat feedback as part of learning, not as a final judgement after the work is already complete.

The most important design principle is that feedback should require or invite action. A feedback response gate is valuable because it closes the loop: students acknowledge, interpret, or respond to feedback before continuing.

## Questions for Developers

### Architecture

- Should this remain a standalone activity module, or should it be implemented as extensions to core Assignment?
- If standalone, which Assignment behaviours must be copied, reused, or intentionally omitted?
- Can stage-level submissions and grading be modelled cleanly within Moodle's existing grading and advanced grading APIs?

### Advanced Grading

- What is the best architecture for per-stage Rubric and Marking guide support?
- Should each stage be a separate advanced grading area?
- Can the number of stage grading areas be dynamic, or must it remain bounded?
- How should advanced grading data be stored, restored, and exported?

### Gradebook

- Should the default be one grade item, with stage-level grades aggregated internally?
- Should the plugin create a Gradebook category automatically when stage weighting is enabled?
- What should happen if staff manually edit, move, hide, or delete generated grade items?
- How should switching between gradebook modes be handled after students have been graded?

### Workflow and State

- What is the safest state model for submitted, graded, feedback released, feedback response required, and unlocked?
- Should students ever be able to edit a submitted stage?
- How should extensions and overrides work at stage level?
- Should late logic be per stage, whole activity, or both?

### Backup, Restore, and Privacy

- Which user data must be included in backup and restore?
- How should submission files, editor files, feedback files, and advanced grading data be restored?
- What privacy exports and deletions are required for submissions, feedback, feedback responses, and grading data?

### Scale and Performance

- How will the teacher dashboard perform in large classes?
- Should stage status summaries be calculated live, cached, or stored?
- What indexes are needed for common filters?
- How should groups, cohorts, and separate groups mode affect the dashboard?

### Moodle UX

- How close should the submissions page remain to core Assignment?
- Should the activity use Moodle's secondary navigation tabs exactly like Assignment?
- How can the settings page stay powerful without overwhelming staff?
- Should templates hide advanced options until needed?

## Suggested Review Framing

When asking developers and learning designers to review this concept, the most useful questions are:

- Is the underlying problem real and common enough to justify a plugin?
- Would this reduce staff workload compared with current Moodle workarounds?
- Would students understand the workflow without extensive training?
- Does the gradebook model match how academics actually mark staged work?
- Which parts should be Moodle-native from day one?
- Which parts should remain future ideas?
- What is the smallest pilotable version?

## Summary

The current prototype demonstrates a possible Moodle-native staged assignment. The next step is not just more coding; it is requirements critique.

The core idea is:

> Staff need one Moodle-native activity for staged submissions, feedback loops, feedback response, stage-aware grading, and Gradebook integration so they can assess learning process as well as final artefacts.

The prototype is useful because it makes the idea tangible. This brief is useful because it gives developers and stakeholders something to challenge before implementation decisions harden.
