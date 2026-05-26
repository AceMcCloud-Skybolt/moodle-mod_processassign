# Process Assignment Review Change Log

## 2026-05-26 page review

- Fix missing language string in activity navigation: `[[submissions]]` should display as `Submissions`.
- Do not keep both `Process assignment` and `Submissions` as duplicate tabs for instructors. The current `[[submissions]]` tab displays the same page as `Process assignment`; normal Moodle Assignment gives instructors a meaningful `Submissions` view, so make this tab distinct or remove the duplicate.
- Fix missing language string/action label in grading summary: `[[grade]]` should display as `Grade`.
- Make the grading summary `Grade` button functional. It currently does nothing; in normal Assignment this opens the grading interface where instructors can move through student submissions.
- Add a more Moodle-native grading interface, not only the Teacher Dashboard. Keep the dashboard as a high-level/action overview, but provide the familiar instructor workflow for cycling through students/stages and grading.
- Restore the `Nudge` action in the Teacher Dashboard; it was useful but is now missing.
- Prevent teacher/admin users from seeing the student submission workflow on the same activity page when they are viewing as staff.
- Review completion condition wording/display: currently shows `To do: Receive a grade`; decide whether this needs a cleaner process-assignment-specific completion condition later.
- Clarify dashboard summary counts: `Submitted` counts students while dashboard filters count student-stage rows, so labels may need to make that distinction clearer.

## 2026-05-26 settings page review

- Confirmed during guided browser review.
- Fix broken/missing activity icon shown beside the activity title on the settings page.
- Fix missing navigation string `[[submissions]]` in the activity secondary navigation; this appears on both view and settings pages.
- Stage instructions field is still a plain textarea with a format dropdown, not TinyMCE; make stage instructions use the same rich text editor experience as the activity description.
- Stage word limit control is visually awkward: the enable checkbox appears above the field with no label context; align it more like Moodle’s normal word limit controls.
- Stage time limit control is visible but not yet enforced; either implement the timer behaviour properly or mark/remove it until implemented.
- Review stage form layout density: each stage section is long and may feel heavy for staff once five stages are configured.

## 2026-05-26 instructor pass implemented

- Staff default activity page now shows a Moodle-style grading summary instead of the student submission workflow.
- `Submissions` is now a distinct instructor dashboard view with filters, counts, `Grade stage`, and `Nudge` actions.
- `Grade submissions` now opens a functional grader workflow with previous/next navigation through stage submissions.
- Stage instructions and grading feedback now use the configured TinyMCE editor, including media recording buttons where available.
- Broken `[[...]]` strings and the activity icon regression are fixed; unenforced stage time limit UI is hidden for now.

## 2026-05-26 native Assignment comparison

- Native settings still include features Process assignment only partially mirrors: Activity instructions, Additional files, Feedback types, Group submission settings, anonymous submissions, marking workflow, marking allocation, and partial release settings.
- Native submissions view includes user search, suspended participant toggle, Quick grading, bulk Actions, per-user row selection, sortable columns, paging size, submission comments, last modified grade, feedback comments, and final grade.
- Native submissions row menus expose separate actions: top `Actions` links to `View gradebook`; row `Submission actions` includes `Edit submission`, `Prevent submission changes`, and `Grant extension`; row `Grade actions` includes `Grade`.
- Native `Advanced` filter currently exposes `Include suspended participants`.
- Native grader page opens at `action=grader&userid=...` with a focused single-student grading layout: course/activity breadcrumb, `View all submissions`, due date/time remaining, previous/change user/next controls, submission status panel, grade field, current gradebook grade, TinyMCE feedback comments, Notify student checkbox, and `Save changes`, `Save and show next`, `Reset`.
- Process assignment now has the preferred `Submissions` tab, user search, status/stage filters, table rows by student-stage, Grade/Nudge actions, and stage-aware submission/feedback columns.
- Next implementation candidates: feedback files/comments parity, bulk table actions, group submissions, marking workflow/allocation, and fuller grade table sorting/paging.

## 2026-05-26 native-style actions/grader pass implemented

- Process assignment `Submissions` now has native-style top `Actions`, row `Submission actions`, and row `Grade actions` menus.
- Top `Actions` links to the Moodle gradebook; row submission menus expose `View submission`, planned `Edit submission`, planned `Prevent submission changes`, planned `Grant extension`, and `Nudge`; row grade menus expose `Grade`.
- Process assignment grader now includes `View all submissions`, previous/next navigation, change-user selector, student/stage header, due-date context, status summary, current gradebook grade, TinyMCE feedback, Notify student, Save changes, Save and show next, and Reset.
