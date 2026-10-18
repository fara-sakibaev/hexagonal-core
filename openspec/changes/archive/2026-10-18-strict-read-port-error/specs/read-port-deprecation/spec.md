## ADDED Requirements

### Requirement: Strict ReadPort usage errors
PHPStan and Psalm integrations SHALL report every detected ReadPortInterface consumer usage as an analysis error with source file, line, and actionable migration guidance. Detection SHALL retain the existing contract matching rules: direct and indirect inheritance, native and PHPDoc types, aliases and compound types; unrelated same-name types SHALL NOT match.

#### Scenario: ReadPort usage alone
- **WHEN** analyzed code uses ReadPortInterface and has no other findings
- **THEN** PHPStan and Psalm analysis fail and report the ReadPort diagnostic at the detected location

#### Scenario: Existing detection forms
- **WHEN** analyzed code references ReadPortInterface through inheritance, native types, PHPDoc, aliases, or compound types
- **THEN** the corresponding usages remain detected and each diagnostic fails analysis

#### Scenario: Unrelated same-name type
- **WHEN** analyzed code references an unrelated interface named ReadPortInterface
- **THEN** no ReadPort diagnostic is emitted

#### Scenario: Mixed findings
- **WHEN** ReadPort usage appears with independent analyzer errors
- **THEN** ReadPort diagnostics and independent errors are reported, and analysis fails
