# Test suite of local_dttutor

The suite implements the test case definitions of
[`cases_data/dttutor/dttutor-2.0.9.md`](https://github.com/datacurso/test-cases-definition/blob/main/cases_data/dttutor/dttutor-2.0.9.md)
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

The definition document classifies each `[Pendiente]` item of the scope by severity. Thirty-three of
the thirty-seven are now implemented and their tests describe the behaviour that exists. The four
left are skipped with their reason, and every one of them is waiting on something outside this
plugin:

| Case | Waiting on |
|---|---|
| MDL-INT-039 | The licence region is resolved inside the `aiprovider_datacurso` plugin |
| API-CTR-005 | Bulk deletion of conversations belongs to the Datacurso AI service |
| MDL-E2E-025 | A decision on what the tutor may discuss during a quiz attempt |
| SYS-E2E-005 | A response time target agreed with the client |

No test fails on purpose any more.

## What this suite does not cover

| Cases | Reason |
|---|---|
| `API-CTR-001`, `API-CTR-002`, `API-CTR-003`, `API-CTR-004`, `API-CTR-005`, `API-INT-001`, `API-INT-002`, `API-INT-003`, `API-INT-004` | They belong to the Datacurso AI service (Python), not to this plugin |
| `SYS-E2E-001`, `SYS-E2E-002`, `SYS-E2E-003`, `SYS-E2E-004`, `SYS-EVAL-001`, `SYS-EVAL-002`, `SYS-EVAL-003`, `SYS-EVAL-004`, `SYS-EVAL-005`, `SYS-EVAL-006` | They need the real service, a valid licence and the golden datasets |
| `MDL-E2E-003`, `MDL-E2E-004`, `MDL-E2E-005`, `MDL-E2E-006`, `MDL-E2E-007`, `MDL-E2E-016` | They drive a conversation, so they need a stubbed AI service behind `chatproxy.php` |
| `MDL-E2E-015`, `MDL-E2E-019`, `MDL-E2E-020`, `MDL-E2E-022`, `MDL-E2E-023`, `MDL-E2E-024` | Corrected in the chat module and its styles. Checking them needs a browser with a stubbed AI service, so only the server side of each is covered here |
| `MDL-UNIT-009`, `MDL-UNIT-010`, `MDL-UNIT-012`, `MDL-UNIT-013` | They live in the chat JavaScript and the plugin has no JavaScript unit runner; listed in `tests/frontend_coverage_test.php` |

Closing the third row (a stub for the AI service in Behat) would also close the two `[Pendiente:fail]`
rows below it, and is the single change that would add the most coverage.
