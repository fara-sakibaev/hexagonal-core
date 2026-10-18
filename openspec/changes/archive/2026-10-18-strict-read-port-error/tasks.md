## 1. PHPStan enforcement

- [x] 1.1 Change ReadPort reporting to standard PHPStan errors and verify ReadPort-only analysis exits nonzero with file and line.
- [x] 1.2 Verify PHPStan mixed findings preserve independent errors and structured output contains ReadPort errors with accurate totals.

## 2. Psalm enforcement

- [x] 2.1 Configure DeprecatedReadPort as an error by default and verify ReadPort-only analysis exits nonzero with file and line.
- [x] 2.2 Verify Psalm detects existing aliases, inheritance, PHPDoc/native references and excludes unrelated same-name types.

## 3. Migration and integration

- [x] 3.1 Update setup and migration documentation with strict failure and replacements; verify guidance matches analyzer behavior.
- [x] 3.2 Run focused ReadPort suites and repository analyzer/check gates; record command results and any unavailable stages.
