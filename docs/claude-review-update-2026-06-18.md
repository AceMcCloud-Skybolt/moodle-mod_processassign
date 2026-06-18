# Claude Review Update - 2026-06-18

## Current status

`mod_processassign` is a Moodle 5.1-compatible prototype activity for staged/process-oriented assignments. It supports multiple ordered stages, stage-level submission settings, staff grading per stage, optional feedback-response gating, Moodle message-provider notifications, privacy API support, backup/restore scaffolding, and two gradebook models:

- **Single aggregate grade item** by default, like core Assignment.
- **Stage grade category** mode, which creates a grade category plus weighted child grade items for configured stages.

The repository is currently pushed to GitHub and GitHub Actions is passing against **Moodle 5.1.5 (Build: 20260608)** on PHP 8.3/MariaDB.

## Most recent implementation work

- Added initial PHPUnit coverage for grade aggregation, stage save/delete behaviour, `$nullifnone`, and privacy provider behaviour.
- Added GitHub Actions using `moodle-plugin-ci`, with the plugin checked out separately from CI tooling so lint only scans plugin files.
- Fixed Moodle 5.1 CI setup issues:
  - MariaDB uses `dbtype=mariadb`.
  - `max_input_vars` is set to `5000`.
  - `en_AU.UTF-8` locale is generated for Moodle PHPUnit.
  - Moodle install skips Behat init and explicitly initialises PHPUnit only.
- Replaced direct support-user emails with Moodle message providers and added basic group-aware grader notification filtering.
- Hardened student POST handling so submissions and feedback responses are processed before page output.
- Added server-side checks that submitted `stageid` matches the currently unlocked stage.
- Prevented submitted work from silently downgrading back to draft on edit.
- Made assignment-level feedback-response gating live rather than permanently baking the setting into individual stages.
- Guarded gradebook mode switching so stage/category gradebook items are only deleted when the gradebook mode actually changes.
- Added `$nullifnone` handling for gradebook updates.
- Blocked scale grading for now because aggregate math currently assumes point grades.

## CI evidence

Latest verified passing run:

- Commit: `f2608ef`
- Workflow: Moodle Plugin CI
- Result: install, PHP lint, and PHPUnit all passed.

## Review focus

Suggested next review questions:

1. Is the standalone activity-module architecture still the right Moodle shape, or should some parts be Assignment subplugins?
2. Is the gradebook design acceptable: single aggregate grade by default, optional auto-created stage category for heavier assessment designs?
3. What is the best Moodle-native path for dynamic stage-level advanced grading areas beyond the current static `stage1` to `stage5` prototype?
4. Which core Assignment features are must-have before a university pilot?
5. Is the feedback-response gate pedagogically useful as currently modelled, or should it become a richer reflection/checkpoint object?
6. Are the privacy provider, pluginfile checks, backup/restore scaffolding, and message-provider approach aligned with Moodle expectations?

## Known remaining work

- Course reset support (`processassign_reset_userdata`) is not implemented.
- `course_module_viewed` event support is still missing.
- Calendar/timeline integration is still missing.
- Custom completion rules such as â€œcomplete when all stages gradedâ€ are still missing.
- Group submissions, marking workflow, blind marking, marking allocation, extension overrides, and full attempt history are not implemented.
- Stage time-limit fields exist but countdown/enforcement behaviour is not implemented.
- Allowed attempts and grant-attempt settings are captured but need deeper implementation.
- Backup/restore exists but needs broader test coverage.
- Current test suite is useful but still small; Behat coverage is not yet present.
