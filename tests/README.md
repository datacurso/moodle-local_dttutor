# Test suite of local_dttutor

The suite implements the test case definitions of
[`cases_data/dttutor/dttutor-2.0.10.md`](https://github.com/datacurso/test-cases-definition/blob/main/cases_data/dttutor/dttutor-2.0.10.md)
in the `test-cases-definition` repository.

Every case identifier appears in the PHPDoc of the PHPUnit methods that implement it, and as a tag
on the Behat scenarios. Search for the identifier to find its tests:

```bash
grep -rn "MDL-INT-009" tests/
```

## Running the suite

```bash
# PHPUnit, whole plugin
php vendor/bin/phpunit --testsuite=local_dttutor_testsuite

# PHPUnit, one file
php vendor/bin/phpunit local/dttutor/tests/proxy/course_knowledge_gaps_test.php

# Behat, whole plugin
php admin/tool/behat/cli/run.php --tags=@local_dttutor

# Behat, one case
php admin/tool/behat/cli/run.php --tags=@MDL-E2E-001

```

## Pending items of the scope

The definition document classifies each `[Pendiente]` item of the scope by severity. All
thirty-seven are now implemented and their tests describe the behaviour that exists. No test is
skipped for want of a feature any more.

Two of them were closed outside this plugin, so what is checked here is only the half that lives
here:

| Case | Where the rest of it lives |
|---|---|
| MDL-INT-039 | The licence region is resolved once inside `aiprovider_datacurso` |
| API-CTR-005 | The bulk deletion of conversations belongs to the Datacurso AI service |

`SYS-E2E-005` asks for a response time target, defined and measured step by step. The target is a
setting with a default of twenty seconds, every answer is timed step by step against it, and one
that goes over is written to the error log. Measuring the real service still belongs to a run
against it, which no test stands in for: the baseline of the pilot site is in the milestone
documents.

API-CTR-005 is served by `POST /chat/sessions/purge` in the Datacurso AI service, which deletes the
conversations of a person (or of a whole course) including the ones this plugin never registered.
The endpoint has its own tests in that repository; the half that lives here, the privacy requests
and the deletion observers that call it, is covered by `tests/purge_conversations_test.php`. The
reading of the documents of a course works the same way, with `tests/proxy/file_content_test.php`
on this side.

No test fails on purpose any more.

## What this suite does not cover

| Cases | Reason |
|---|---|
| `API-CTR-001`, `API-CTR-002`, `API-CTR-003`, `API-CTR-004`, `API-INT-001`, `API-INT-002`, `API-INT-003`, `API-INT-004` | They belong to the Datacurso AI service (Python), not to this plugin |
| `SYS-E2E-001`, `SYS-E2E-002`, `SYS-E2E-003`, `SYS-E2E-004`, `SYS-EVAL-001`, `SYS-EVAL-002`, `SYS-EVAL-003`, `SYS-EVAL-004`, `SYS-EVAL-005`, `SYS-EVAL-006` | They need the real service, a valid licence and the golden datasets |
| `MDL-E2E-003`, `MDL-E2E-004`, `MDL-E2E-005`, `MDL-E2E-006`, `MDL-E2E-007`, `MDL-E2E-016` | They drive a conversation, so they need a stubbed AI service behind `chatproxy.php` |
| `MDL-E2E-015`, `MDL-E2E-022`, `MDL-E2E-023`, `MDL-E2E-024` | Corrected in the chat module and its styles. Checking them needs a browser with a stubbed AI service, so only the server side of each is covered here |
| `MDL-UNIT-009`, `MDL-UNIT-010`, `MDL-UNIT-012`, `MDL-UNIT-013` | They live in the chat JavaScript and the plugin has no JavaScript unit runner; listed in `tests/frontend_coverage_test.php` |

Closing the third row (a stub for the AI service in Behat) would also close the two `[Pendiente:fail]`
rows below it, and is the single change that would add the most coverage.
