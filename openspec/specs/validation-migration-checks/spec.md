# validation-migration-checks Specification

## Purpose

Lets consumers statically verify concrete validator contracts and opt into a migration check for scattered legacy validation methods.

## Requirements

### Requirement: Concrete validator signature check
The package SHALL provide PHPStan and Psalm checks that report a validator whose public method does not accept its declared concrete input type or return the structured violation result.

#### Scenario: Incompatible validator
- **WHEN** a validator declares one input type but its method accepts an incompatible type
- **THEN** analysis reports an actionable error

### Requirement: Opt-in legacy method check
The package SHALL provide a separate importable PHPStan and Psalm profile that reports methods named `validate*` outside named validators and domain policies. The check SHALL allow methods whose signatures are required by external APIs and SHALL NOT be enabled by the standard setup command.

#### Scenario: Ad hoc validation method
- **WHEN** an opted-in consumer analyzes a `validateShape()` method in a DTO
- **THEN** analysis reports a migration error

#### Scenario: Domain policy
- **WHEN** an opted-in consumer analyzes `validate()` in a domain policy
- **THEN** the migration check does not report that method

#### Scenario: Existing setup
- **WHEN** a consumer enables only the standard handler setup
- **THEN** the legacy-method migration check is not active
