# Verification

- tests/abstraction-roles.php: all catalog contracts, inherited and duplicate ancestry, no autoload, identifier specialization/conflicts, every role against each layer.
- tests/architecture-scope.php: source/package layouts, single-layer discovery, explicit root and test precedence, invalid scope, coverage, placement and anonymous policy.
- tests/architecture-analysis.php: real PHPStan/Psalm runs with source-only named concrete/abstract classes, interfaces and enums; identical diagnostic counts and positive source lines; anonymous class inside unused trait; test exemption; unresolved parent remains a native analyzer error; cached rediscovery; files outside analyzer paths; malformed scope.
- tests/setup.php: preview, explicit apply, idempotence, package paths, undeclared candidate preview and invalid scope without overwriting configuration.
- Existing static-analysis fixtures verify handler and validation compatibility.

Passed: composer cs:check, analyse:phpstan, analyse:psalm, test:outbox, test:setup, test:validation, test:analysis, test:roles, test:architecture:scope, test:architecture:analysis; strict OpenSpec validation and git diff --check.

Analyzer integration tests required local TCP access for PHPStan worker processes and ran outside the filesystem/network sandbox with approval. These are local consumer fixtures, not evidence of remote CI execution or deployment.
