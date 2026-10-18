## 1. Detection contract and fixtures

- [x] 1.1 Add shared warning message/identifier and location records; verify deterministic ordering and deduplication with focused tests.
- [x] 1.2 Add consumer fixtures for direct/indirect inheritance, aliases, native types and nested PHPDoc types, analyzed tests and unrelated same-name types; verify expected file/line findings are documented in executable assertions.

## 2. PHPStan integration

- [x] 2.1 Implement reference collection independent of architecture configuration; verify all detection fixtures through PHPStan with no hexagonal.json.
- [x] 2.2 Implement and register the default human formatter and hexagonalJson formatter; verify visible warning-only output, valid JSON warning records, unchanged actual error information and successful exit status.
- [x] 2.3 Preserve explicit consumer formatter overrides; verify stock JSON validity and setup behavior using existing and starter configurations.

## 3. Psalm integration

- [x] 3.1 Implement matching reference detection with dedicated info diagnostics; verify parity of locations and message with PHPStan fixtures without architecture configuration.
- [x] 3.2 Wire default info severity and native output visibility; verify --show-info=true displays findings and default visibility settings do not turn them into errors.

## 4. Compatibility and migration

- [x] 4.1 Document interface deprecation while preserving generics and architecture role; verify valid Query usage remains successful, invalid Command generics still fail and no duplicate built-in deprecation errors appear.
- [x] 4.2 Update README/setup and migration guidance with both output commands, aggregate-versus-DTO examples and future removal notice; verify examples respect current layer rules and documentation agrees with ADR-0003.

## 5. Integration verification

- [x] 5.1 Exercise cold and warm caches, edited references and deleted files for both analyzers; verify stable warnings and absence of stale or duplicate findings.
- [x] 5.2 Run mixed warning/error CLI cases for both analyzers; verify real handler/generic/architecture errors retain nonzero exit status while warnings never inflate error counts.
- [x] 5.3 Run repository formatting, analyzer and test scripts including static-analysis, setup, role and architecture suites; record commands/results and supported analyzer-version evidence without weakening existing assertions.
- [x] 5.4 Validate the completed change with openspec validate deprecate-read-port --strict and review the final diff against every scenario; record verification evidence before requesting sync/archive in the appropriate workflow.
