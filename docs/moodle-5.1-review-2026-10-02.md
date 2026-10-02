# Process Assignment: Moodle 5.1 compatibility review

## Scope and environment

Reviewed on 2 October 2026 against Moodle 5.1.4+ (Build: 20260604), PHP 8.2.4, MariaDB 10.11.11 and PHPUnit 11.5.55 in an isolated Windows test installation. No university-site data was accessed or changed.

This is compatibility evidence, not production certification. The institution's target Moodle patch release, theme, role configuration, integrations and upgrade path still require acceptance testing. See Moodle's [5.1 developer update](https://moodledev.io/docs/5.1/devupdate) for the upstream API changes.

## Automated evidence

- PHP syntax, Moodle coding standard (zero warnings), PHPDoc, plugin structure and upgrade savepoint checks pass.
- Literal language-string references were checked against the installed Moodle 5.1 string manager; no missing references remain. Dynamic identifiers need workflow testing too.
- PHPUnit: **14 tests, 28 assertions**, zero failures/errors, in the final combined run (65 tests across the workspace). No PHPUnit warnings or deprecations were reported in that final run.
- Process Assignment has no Mustache files; template lint reports no relevant files. The workflow retains this check for future templates.

## Changes

- Explicitly load Moodle's forms library before using editor constants on the activity page.
- Fix the draft-status language-string component.
- Add Bootstrap 5 dropdown attributes and visually-hidden labels while retaining Bootstrap 4 attributes for Moodle 4.5.
- Remove an unnecessary internal-access guard that caused the last GitHub coding check to fail.
- Add regression coverage for settings/completion rendering, the filtered staff submissions table, draft labels and dropdown markup.
- Normalize context IDs in a privacy assertion, since the database can return numeric strings; no privacy-provider behavior was changed.
- Extend CI to PHP 8.2 and 8.3; fail on coding and PHPUnit warnings. Moodle's PHPUnit configuration enables failure on deprecations; moodle-plugin-ci does not expose a separate deprecation CLI option.

Release: **0.1.10**, plugin version **2026100200**. No database schema change.

## Deployment and acceptance

The local demo installation was older than this repository and still contained an obsolete renderer. Installing only selected files is unsafe: deploy a complete, reviewed plugin package, run Moodle's CLI upgrade and purge caches. Back up code, database and moodledata first.

Before university rollout, exercise staff and student submissions, TinyMCE/media/files, feedback responses and stage locking, rubric/marking-guide grading, both gradebook modes, notifications/group restrictions, completion/timeline, backup/restore and reset with realistic roles. Confirm ellipsis dropdowns and keyboard access under the institutional theme. Automated tests are not a substitute for those journeys.

Use the [Moodle 5.1 UAT checklist](moodle-5.1-uat-checklist.md) to record these results. This review does not deploy to the local demo site or the university environment.
