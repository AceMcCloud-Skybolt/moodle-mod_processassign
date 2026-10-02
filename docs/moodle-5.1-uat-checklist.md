# Process Assignment: Moodle 5.1 upgrade UAT

## Preparation

- Record the Moodle patch version, PHP/database versions, theme, plugin commit and enabled editor plugins.
- Back up the database, moodledata and existing plugin code. Confirm rollback can restore all three consistently.
- Install the complete reviewed package as `public/mod/processassign` on Moodle 5.1. Do not layer selected files over an older copy.
- Run Moodle's upgrade and purge caches. Check Notifications, cron and developer debugging for errors.
- Use synthetic data and distinct teacher and student accounts; switching to a student role alone does not exercise every permission boundary.

## Workflow checks

Record Pass / Fail / Not supported, evidence and owner against every row. Unsupported requirements need an explicit deployment decision, not a silent pass.

| Check | Expected result | Result / evidence |
| --- | --- | --- |
| Create and edit a 3-stage activity | Only configured stages display; instructions, dates, file restrictions and feedback-response settings round-trip. | |
| Five-stage settings and completion | All grade choices have translated labels; no double-bracket strings or debugging notices. | |
| Staff dashboard and search | Students and correct stage/status appear; search/filter/reset work; no renderer/type errors. | |
| Ellipsis actions | Menus open with mouse and keyboard under the institutional theme; permitted actions execute, forbidden actions cannot be forced by URL/POST. | |
| Draft submission | Draft status is readable; saved online text and files persist after reload. | |
| Submit and edit | Confirmation is clear; permitted edits preserve submitted status; cutoff and attempt rules apply server-side. | |
| TinyMCE content | Text, supported audio/video and attached files save, reload and display to the intended roles only. | |
| Availability and limits | Opening, due and cutoff dates, extensions, word/file limits and configured timers behave at their boundaries. | |
| Stage progression | Locked stages cannot accept submissions; feedback-response requirements unlock the intended stage and can be disabled without sticky flags. | |
| Direct grading | Grade/feedback save, survive reload and display according to permissions and gradebook visibility. | |
| Rubric / marking guide | Configure different supported methods by stage, grade a submission, reopen the grader and verify the saved evaluation and numeric result. | |
| Single-item gradebook | Weighted stage results aggregate to the expected one item; empty submissions do not invent grades. | |
| Category gradebook | Stage items and category totals match the intended weights; ordinary settings edits preserve overrides, hidden/locked flags and weights. | |
| Gradebook mode switch | Document the warning and effect on existing items/grades; test only in a disposable activity. | |
| Notifications and groups | Grader/student notifications obey preferences and grouping restrictions; no student data leaks between groups. | |
| Completion and timeline | Configured completion rules, calendar dates and timeline entries behave as documented for this release. Record absent features explicitly. | |
| Backup / restore / copy | Restore into another course; validate stages, grading definitions, permissions, files and grades, both with and without user data. | |
| Course reset | Selected reset options remove the intended user data and gradebook values; unrelated activities and course structure remain intact. | |
| Privacy and file access | Export/delete requests work; unauthorised accounts cannot download another student's files or feedback. | |
| Cohort and accessibility | Test a realistic cohort, pagination, keyboard navigation, focus, screen-reader labels and narrow screens. | |

## Sign-off

Retest failures after fixes. Have a Moodle developer and an academic tester approve the exact commit/package on the institution's upgrade environment before teaching use. Automated CI checks are supporting evidence, not sign-off for these workflows.
