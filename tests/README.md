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

The definition document classifies each `[Pendiente]` item of the scope by severity, and the suite
follows that classification:

| Severity | How it is implemented | Effect on CI |
|---|---|---|
| `[Pendiente:fail]` | A test written with the behaviour the scope requires | **Fails** until the defect is corrected |
| `[Pendiente:skip]` | `markTestSkipped()` with the reason | Reported as skipped until the feature exists |

Tests that fail on purpose today:

| Case | Where | What it protects |
|---|---|---|
| MDL-INT-009 | `tests/proxy/course_knowledge_gaps_test.php` | Hidden grades must not reach the AI service |
| MDL-INT-034 | `tests/privacy/provider_test.php` | The privacy declaration must match what is really sent |
| MDL-INT-038 | `tests/provider_availability_test.php` | Disabling the AI provider must stop the tutor |
| MDL-E2E-008 | `tests/proxy/system_message_test.php` | The selected fragment must travel with the question |
| MDL-E2E-010 | `tests/scope_gaps_test.php` | The refusal must name the real situation |
| MDL-E2E-017 | `tests/proxy/handler_failures_test.php` | No failure may end in silence |
| MDL-E2E-018 | `tests/proxy/handler_failures_test.php` | Licence and credit refusals must keep their own message |

## What this suite does not cover

| Cases | Reason |
|---|---|
| `API-CTR-001`, `API-CTR-002`, `API-CTR-003`, `API-CTR-004`, `API-CTR-005`, `API-INT-001`, `API-INT-002`, `API-INT-003`, `API-INT-004` | They belong to the Datacurso AI service (Python), not to this plugin |
| `SYS-E2E-001`, `SYS-E2E-002`, `SYS-E2E-003`, `SYS-E2E-004`, `SYS-EVAL-001`, `SYS-EVAL-002`, `SYS-EVAL-003`, `SYS-EVAL-004`, `SYS-EVAL-005`, `SYS-EVAL-006` | They need the real service, a valid licence and the golden datasets |
| `MDL-E2E-003`, `MDL-E2E-004`, `MDL-E2E-005`, `MDL-E2E-006`, `MDL-E2E-007`, `MDL-E2E-016` | They drive a conversation, so they need a stubbed AI service behind `chatproxy.php` |
| `MDL-E2E-013`, `MDL-E2E-020` to `MDL-E2E-026` (`[Pendiente:skip]`) | Interface gaps with no behaviour to drive yet; listed as skipped tests in `tests/scope_gaps_test.php` |
| `MDL-E2E-015` (`[Pendiente:fail]`) | The defect only shows with a conversation already stored in the service; checked by hand |
| `MDL-E2E-019` (`[Pendiente:fail]`) | Its server side is covered by `MDL-INT-021`; the missing notice is browser side and needs the same stub |
| `MDL-UNIT-009`, `MDL-UNIT-010`, `MDL-UNIT-012`, `MDL-UNIT-013` | They live in the chat JavaScript and the plugin has no JavaScript unit runner; listed in `tests/frontend_coverage_test.php` |

Closing the third row (a stub for the AI service in Behat) would also close the two `[Pendiente:fail]`
rows below it, and is the single change that would add the most coverage.
