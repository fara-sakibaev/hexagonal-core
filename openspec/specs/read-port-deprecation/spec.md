# read-port-deprecation Specification

## Purpose

Give consumers actionable errors for deprecated read-port usage and migration guidance before the contract is removed in a later breaking release.

## Requirements

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

### Requirement: Contract reference detection
The integrations SHALL detect ReadPortInterface usage in analyzed consumer declarations through direct or indirect extends/implements, parameter/property/return types, and PHPDoc type references. Names SHALL be resolved with namespaces and aliases. Diagnostics SHALL identify the source file and line. Unrelated types with the same short name SHALL NOT match.

#### Scenario: Inherited port
- **WHEN** a class implements a consumer interface extending ReadPortInterface
- **THEN** analysis reports deprecated usage at that class declaration and at the consumer interface reference

#### Scenario: Native and documented types
- **WHEN** a parameter, property, return type or PHPDoc type references the deprecated contract, including aliased names and compound types
- **THEN** analysis identifies the deprecated reference and its location

#### Scenario: Unrelated name
- **WHEN** analyzed code references an unrelated interface named ReadPortInterface
- **THEN** no read-port deprecation diagnostic is produced for that reference

### Requirement: Actionable strict message
Diagnostics SHALL use the identifier hexagonal.deprecatedReadPort and the message: "ReadPortInterface is deprecated. Consider using RepositoryInterface for aggregate persistence or QueryHandlerInterface for application queries. ReadPortInterface will be removed in the next breaking release; remaining usages will cause analysis errors." Deprecated usage SHALL fail analysis. Independent errors SHALL retain their failure behavior.

#### Scenario: Deprecated usage only
- **WHEN** valid deprecated usage is the only finding
- **THEN** analysis fails with a located diagnostic

#### Scenario: Warning with genuine error
- **WHEN** deprecated usage accompanies an invalid handler or generic type
- **THEN** analysis fails for the genuine error while retaining the deprecation diagnostic

### Requirement: Analyzer reporting
PHPStan's library human formatter SHALL display deprecation warnings by default when no consumer formatter override applies. The hexagonalJson formatter SHALL produce valid JSON with a separate warnings array containing identifier, message, file and line, preserving ordinary error information and exit behavior. Stock PHPStan JSON SHALL remain usable without these warnings. Psalm SHALL report this diagnostic as info and display its details when --show-info=true is selected; it SHALL NOT force info visibility by bypassing reporting configuration.

#### Scenario: Human output
- **WHEN** a consumer uses the ordinary PHPStan integration without overriding its formatter
- **THEN** deprecated references appear as warnings with file and line

#### Scenario: Structured output
- **WHEN** PHPStan runs with --error-format=hexagonalJson
- **THEN** stdout is valid JSON containing separate warning records and unchanged actual error counts

#### Scenario: Explicit stock format
- **WHEN** PHPStan runs with --error-format=json
- **THEN** stock JSON remains valid and deprecated usage does not become an error

#### Scenario: Psalm info visibility
- **WHEN** Psalm runs with --show-info=true
- **THEN** its info output includes the deprecation message and location without making warning-only analysis fail

### Requirement: Stable repeated analysis
Diagnostics SHALL remain available on repeated analysis with cached results. Removed references SHALL stop producing warnings, and duplicate reports of the same reference from overlapping detection paths SHALL be collapsed.

#### Scenario: Warm cache
- **WHEN** unchanged deprecated code is analyzed again with caches enabled
- **THEN** the same warning locations remain available

#### Scenario: Migration between runs
- **WHEN** a deprecated reference is removed or its file is deleted before the next analysis
- **THEN** no stale diagnostic remains for that reference
