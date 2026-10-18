## 1. Shared classification

- [x] 1.1 Reconcile existing draft role enums into catalogs and remove UID-specific roles/runtime recognition; verify every existing contract has its intended catalog mapping.
- [x] 1.2 Implement analyzer-neutral ancestry input and RoleMapper results; verify direct/inherited roles, cross-family ambiguity, duplicate matches, InternalId/ExternalId specialization and unknown declarations with focused tests.
- [x] 1.3 Implement explicit layer compatibility and cardinality policy; verify every catalog role against all three layers, including zero-role Infrastructure and forbidden known roles.

## 2. Context scope

- [x] 2.1 Implement normalized search_paths, context_roots and test_paths configuration; verify invalid/ambiguous paths and explicit test precedence with configuration tests.
- [x] 2.2 Implement candidate discovery and declared-root resolution; verify src/App, multiple contexts, packages/Orders/src, single-layer contexts and undeclared candidates through directory fixtures.
- [x] 2.3 Implement declaration placement and anonymous-class policy; verify out-of-layer declarations, traits containing anonymous classes, explicit tests and misleading Test names.

## 3. Analyzer integrations

- [x] 3.1 Add PHPStan ancestry and diagnostic adapter; verify named concrete/abstract classes, interfaces, enums and source-only classes through real analyzer fixtures without runtime autoload.
- [x] 3.2 Add equivalent Psalm adapter; run the same acceptance cases and verify diagnostic-category parity with PHPStan.
- [x] 3.3 Integrate startup discovery and scope coverage validation; verify newly added directories are detected between cached runs and excluded analyzer paths cannot silently bypass scope checks.
- [x] 3.4 Verify unresolved ancestors retain analyzer errors, multiple violations are reported in one run, and existing handler/validation fixtures retain their expected outcomes.

## 4. Consumer setup and documentation

- [x] 4.1 Extend setup preview/application for explicit architecture scope while preserving handler-only use; verify dry-run, idempotence, existing configs and package layouts through setup tests.
- [x] 4.2 Document configuration, all diagnostic categories, role/layer matrix, test exclusions and migration; verify examples against executable consumer fixtures and align ADR/CONTEXT with final behavior.

## 5. Final verification

- [x] 5.1 Integrate new regression suites into Composer scripts and CI; verify CI configuration invokes role, discovery and both analyzer acceptance checks.
- [x] 5.2 Run composer cs:check, analyse:phpstan, analyse:psalm, test:outbox, test:setup, test:validation, test:analysis and test:roles plus newly introduced suites; record results without treating library self-analysis as consumer coverage proof.
- [x] 5.3 Run strict OpenSpec validation and git diff --check; verify all spec scenarios have test evidence before marking implementation tasks complete.
