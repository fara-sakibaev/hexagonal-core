# Verification: deprecate-read-port

## Approved decisions

The user approved the formatter name hexagonalJson (Nette rejects hyphens in service names) and the runtime dependency phpstan/phpdoc-parser ^2. Specs, ADR-0003, README, migration guidance and setup output agree. Composer moved parser 2.3.6 from packages-dev to packages without changing package versions.

## Environment

Verified on PHP 8.5.9, PHPStan 2.3.1, Psalm 6.20.0, phpstan/phpdoc-parser 2.3.6 and nikic/php-parser 5.9.0. These are versions within the declared constraints; this evidence does not claim every historical analyzer release was tested.

## Requirement evidence

| Requirement | Evidence |
| --- | --- |
| Exact warning message, identifier, serialization, ordering and deduplication | tests/read-port-warnings.php tests shared records and exact Psalm message |
| Direct/indirect inheritance, aliased native parameter/property/return types, nested PHPDoc, union/intersection | tests/fixtures/read-port-references.php and exact 14-location assertions in tests/read-port-warnings.php, identical in both analyzers |
| Unrelated names, strings, bare imports, shape keys | Negative fixtures and assertions; shape-key false positive reproduced and fixed in both visitors |
| No architecture config; analyzed tests included | phpstan-extension.neon defaults architecture to null; psalm-read-port.xml has no architecture setting; analyzed fixture lives under tests |
| Human warnings and success | tests/read-port-warnings.php invokes default PHPStan output and asserts exit 0 |
| Structured warnings without error inflation | tests/read-port-cache.php asserts zero totals; tests/read-port-mixed.php compares custom JSON minus warnings exactly with stock JSON |
| Consumer formatter choice and setup | tests/setup.php asserts preservation of errorFormat: json; tests/read-port-mixed.php checks config override, stock JSON and nonzero error exits |
| Psalm native INFO and visibility | tests/read-port-reporting.php covers default info, hidden info, suppression and explicit error override |
| Generic and role preservation | Valid Query fixture succeeds; invalid Command generic fails in tests/read-port-mixed.php; existing abstraction-role suite explicitly checks ReadPort role and outbound layer allowance |
| Genuine handler/generic/architecture errors | tests/read-port-mixed.php asserts error identifiers/types and nonzero exits beside warnings |
| Cold/warm caches, edits, deletion, no duplicates | tests/read-port-cache.php checks exact records across cold, warm, edited, restored and deleted phases; PHPStan warm invocation uses --fail-without-result-cache |
| Migration and removal notice | Interface prose notice; README; docs/read-port-migration.md; docs/adr/0003-read-port-deprecation.md; setup output with both reporting options |
| Independent Psalm installation | Fresh temporary Composer consumer requires only this package dev-main and vimeo/psalm ^6, using a path repository. Lock contains neither phpstan/phpstan nor CS Fixer. Psalm returned 0 and exactly 14 INFO locations on the copied fixture. |

## Commands and results

All 14 root scripts passed on the implementation: cs:check, analyse:phpstan, analyse:psalm, test:outbox, test:setup, test:validation, test:roles, test:architecture:scope, test:architecture:analysis, test:analysis, test:read-port, test:read-port:cache, test:read-port:reporting and test:read-port:mixed.

Also passed: composer validate --no-check-publish; integrations/symfony-validator composer test; openspec validate deprecate-read-port --strict; git diff --check. Setup guidance was then added and its targeted test, cs:check, Composer validation and strict OpenSpec validation passed again.

Tests requiring PHPStan parallel workers ran with sandbox escalation for loopback TCP. No assertions were weakened, no baseline or error suppression was introduced. Pre-existing CONTEXT.md and staged/deleted UnknownRoleException state were preserved.

## Delivery boundary

Implementation is local and uncommitted. Canonical spec synchronization and archive are separate workflow steps, not part of this apply operation.
