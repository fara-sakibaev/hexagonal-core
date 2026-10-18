## Purpose

Give consumers actionable, nonfatal diagnostics for deprecated read-port usage before the contract is removed in a later breaking release.

## ADDED Requirements

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

### Requirement: Actionable nonfatal message
Diagnostics SHALL use the identifier hexagonal.deprecatedReadPort and the message: "ReadPortInterface is deprecated. Consider using RepositoryInterface for aggregate persistence or QueryHandlerInterface for application queries. ReadPortInterface will be removed in the next breaking release; remaining usages will cause analysis errors." Deprecated usage alone SHALL NOT change successful exit status or increment error counts. Independent errors SHALL retain their failure behavior.

#### Scenario: Warning only
- **WHEN** valid deprecated usage is the only finding
- **THEN** analysis succeeds with a nonfatal diagnostic

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
