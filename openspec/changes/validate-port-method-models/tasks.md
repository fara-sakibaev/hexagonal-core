## 1. Analyzer rules

- [x] 1.1 Inspect current PHPStan/Psalm plugin registration and port contracts; define port discovery and model-parameter classification, verifying coverage for each library port.
- [x] 1.2 Implement PHPStan public port parameter validation and verify valid/invalid fixtures produce the expected diagnostics.
- [x] 1.3 Implement equivalent Psalm validation and verify matching valid/invalid fixtures.

## 2. Regression coverage and guidance

- [x] 2.1 Add fixtures for accepted library model subtypes and meaningful scalars, rejected arbitrary field collections, and unrelated interfaces; verify both analyzers agree.
- [x] 2.2 Document the port parameter rule and diagnostic remediation; verify documentation matches analyzer behavior.
- [x] 2.3 Run `tests/static-analysis.php`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer cs:check`, and `openspec validate validate-port-method-models`; all passed.
