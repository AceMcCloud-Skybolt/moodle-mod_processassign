# Process Assignment product brief

## Purpose

Process Assignment is a proposed Moodle activity for staged, process-oriented assessment. It is intended for assignments where the learning process matters as much as the final artefact: students submit work in planned stages, receive feedback, respond or revise, and progressively unlock later stages before a final grade is completed.

The prototype in this repository is not intended to be production-ready. It exists to make the idea concrete enough for developers, learning designers, academic staff, and LMS administrators to critique the workflow, requirements, and Moodle architecture.

## Why this is needed

Many university assessments still operate as a single final submission: students upload a finished artefact, the teacher grades it, and the mark flows to the gradebook. That model is simple and familiar, but it can hide the learning process.

Generative AI has made this gap more visible. If assessment relies heavily on a final artefact, staff may have limited evidence of how the student developed the work, responded to feedback, made decisions, or demonstrated discipline-specific thinking over time.

At the same time, many good pedagogical responses to AI are not really about detecting AI. They are about designing richer assessment processes:

- proposals before final submissions
- outlines before essays
- research logs before reports
- storyboards before videos
- rough cuts before final media
- revision plans after feedback
- reflections on decisions, changes, and learning

Moodle already has many pieces of this puzzle, but they are spread across Assignment attempts, Workshop, Rubrics, Marking guides, Completion, Gradebook, Reports, Notifications, Groups, and manual teacher workflows. Process Assignment explores whether a single activity could make this pattern easier for staff and students.

## Problems this aims to solve

### 1. Single-submission assessment hides process evidence

Normal Assignment can collect a final artefact well, but the process leading to that artefact is often invisible or managed outside Moodle. Staff may not see early misunderstandings, late starts, weak planning, or whether feedback was actually used.

### 2. Existing Moodle workarounds are fragmented

Staff can approximate staged assessment by creating multiple Assignment activities, manually arranging grade categories, setting completion restrictions, and writing instructions that explain the sequence. This works, but it is fiddly. It also creates extra gradebook complexity, which many staff already find difficult.

### 3. Attempts are not the same as meaningful stages

Assignment attempts can support resubmission, but attempts are usually versions of the same submission, not intentionally different milestones with separate instructions, criteria, feedback, and pedagogical purpose.

### 4. External platforms create friction

Platforms such as Cadmus demonstrate the value of process-based assessment, but external systems can add cost, procurement work, SSO complexity, privacy review, staff training, student training, and support burden. A Moodle-native approach keeps assessment inside the existing LMS ecosystem.

### 5. Feedback loops are often incomplete

Feedback is most useful when students must do something with it. A staged workflow can require students to read, acknowledge, respond to, or apply feedback before moving on. This makes feedback part of the assessment design rather than a comment at the end.

## Target users

### Academic staff

Need a simple way to design staged assignments without manually constructing several Moodle activities and gradebook items.

### Tutors and markers

Need a clear dashboard showing who is waiting for feedback, who is late, who needs a nudge, and where each student is in the process.

### Students

Need a clear timeline of what is due, what is locked, what feedback has been received, what action is required next, and how each stage contributes to the overall assessment.

### Learning designers

Need reusable staged-assignment patterns aligned to good pedagogy, such as proposal-to-draft-to-final, research-log-to-report, storyboard-to-media, or reflection-based assessment.

### LMS administrators and developers

Need the activity to respect Moodle conventions for gradebook, completion, advanced grading, backup/restore, privacy, capabilities, groups, accessibility, and maintainability.

## Example use cases

### Essay development

A student submits a thesis statement, then an outline, then a draft, then a final essay. The thesis statement receives low-stakes feedback and a small grade. The final essay carries most of the marks.

### Research report

A student submits a research question, then an annotated bibliography, then a methods plan, then a draft report, then the final report. Staff can catch weak research direction early.

### Media project

A student submits a proposal, storyboard, rough cut, and final video. Feedback is targeted at each stage, and students can record audio or video reflections using Moodle's editor/media tools where available.

### Design or prototype project

A student submits a problem definition, sketches, prototype, user feedback summary, and final design. The assessment captures iteration and decision-making rather than only the final product.

### Reflective professional practice

A student submits staged reflections, evidence logs, or workplace learning artefacts. Feedback can require a student response before the next reflection opens.

### Large first-year assignment

A scaffolded process reduces anxiety and improves equity for students unfamiliar with university assessment. Students get early direction before the final deadline.

### Capstone or honours-style project

Longer projects can be broken into structured milestones, giving supervisors and coordinators a clearer view of progress without relying entirely on informal check-ins.

## Core workflow

1. A teacher creates one Process Assignment activity.
2. The teacher chooses the number of stages.
3. Each stage has its own title, instructions, due date, submission type, file rules, word limit, maximum grade, and optionally its own advanced grading method.
4. Students see a timeline-style view of all stages.
5. Only the current stage is open for submission.
6. After submitting a stage, the student waits for feedback or grading.
7. The teacher grades the stage and gives feedback.
8. If enabled, the student must respond to the feedback before the next stage unlocks.
9. The final Moodle gradebook outcome is either:
   - one aggregate grade item, like a normal Assignment, or
   - a grade category with weighted stage items for more complex assessment designs.

## Minimum viable requirements

### Activity setup

- Create a Process Assignment from the Moodle activity picker.
- Support a small fixed number of stages initially, probably three to five.
- Provide per-stage instructions using the Moodle editor.
- Provide per-stage online text and file submission options.
- Provide per-stage due dates and word/file limits.
- Provide sensible templates for common stage types, such as proposal, outline, draft, revision plan, reflection, media prototype, and final submission.

### Student workflow

- Show all stages in a clear sequence.
- Make the current required action obvious.
- Lock future stages until requirements are met.
- Close submitted stages by default, with controlled edit/resubmit behaviour.
- Show previous submissions, feedback, grades, and feedback responses where appropriate.
- Use Moodle editor/media features for online text where available.

### Teacher workflow

- Show a submissions dashboard with stage-aware filters.
- Highlight students who are not started, submitted, awaiting feedback, awaiting student response, late, or complete.
- Provide direct actions from the dashboard, including grade, view submission, and nudge.
- Provide a familiar grading interface that aligns with normal Assignment expectations as much as possible.
- Support feedback comments and feedback files.

### Grading and gradebook

- Support one simple aggregate grade item by default.
- Optionally support an auto-created grade category with one child grade item per stage.
- Support per-stage maximum marks or weighting.
- Support Moodle Advanced Grading methods, including Rubric and Marking guide, per stage.
- Avoid making staff manually construct gradebook categories for common staged-assessment patterns.

### Moodle platform expectations

- Use Moodle capabilities and roles correctly.
- Support backup and restore.
- Implement privacy API coverage.
- Respect group mode and groupings in future versions.
- Support activity completion and course completion conventions.
- Avoid custom UI patterns where core Moodle conventions already exist.
- Maintain accessibility and responsive behaviour.

## Non-goals for the first serious review

- Replacing the normal Assignment activity.
- Building AI detection.
- Building a full external-platform equivalent.
- Supporting every Assignment feature immediately.
- Solving group collaboration and peer evaluation in the same first plugin.
- Creating complex analytics before the core workflow is sound.

## Important design principles

### Keep it Moodle-native

Staff and students should feel like they are still using Moodle. The activity should reuse core patterns wherever possible: settings forms, submissions lists, gradebook, completion, advanced grading, file handling, editor behaviour, and navigation.

### Do not overwhelm staff

The activity should make staged assessment easier, not turn it into a project-management tool. Defaults, templates, and progressive disclosure are important.

### Make the next action obvious

For students, the key question is: what do I need to do next? For teachers, the key question is: who needs my attention now?

### Treat feedback as part of learning

Feedback should not be a dead-end comment. The workflow should optionally require students to acknowledge, respond to, or apply feedback before moving forward.

### Gradebook must remain understandable

Gradebook complexity is a major risk. The default should be one grade item. More complex stage-category behaviour should be optional, explicit, and easy to explain.

## Open questions for developers

### Architecture

- Should this be a standalone activity module, as prototyped, or should some/all functionality be implemented as Assignment subplugins?
- Is a fixed maximum number of stages acceptable for a first production version, or does the architecture need truly dynamic stages from the start?
- What is the cleanest Moodle-native way to support stage-level advanced grading areas?

### Gradebook

- Is the optional auto-created grade category model technically safe and maintainable?
- Should stage weights be represented by maximum marks, explicit weights, or gradebook aggregation settings?
- How should the activity behave if teachers manually alter the generated grade category or grade items?

### Workflow

- What should happen if a teacher changes stages after students have submitted?
- Should students be able to edit a submitted stage before the due date, or only through formal attempts?
- Should stages unlock on submission, on grading, on released feedback, or on student feedback response?
- Should teachers be able to manually unlock stages for individual students?

### Assignment feature parity

- Which core Assignment features are essential before pilot use?
- Which can be deferred?
- How should marking workflow, blind marking, marker allocation, overrides, groups, and extensions fit?

### Reporting and notifications

- What teacher dashboard actions are genuinely useful without becoming clutter?
- Should nudges use Moodle message providers rather than direct email?
- What reports would help staff act, not just observe?

### Pedagogy and templates

- Should the plugin include reusable assignment recipes?
- Should templates be configurable at site/category level?
- How much guidance text should be built into the activity versus left to learning designers?

## Risks

- The activity could become too complex for ordinary staff to configure.
- Gradebook automation could create confusion if not designed carefully.
- Attempting full Assignment parity may significantly increase development scope.
- Stage-level advanced grading may require careful architecture to stay Moodle-native.
- Backup/restore, privacy, accessibility, and group workflows need proper developer review before any pilot.

## Suggested next step

Use the current prototype as a conversation artefact, not as the production plan. The next useful step is a structured review with developers, learning designers, and a small number of academic staff:

1. Validate the problem and use cases.
2. Decide whether the standalone activity architecture is appropriate.
3. Prioritise MVP requirements.
4. Identify Moodle APIs and core patterns that should be reused.
5. Define what would be required for a small sandbox pilot.

