# static-analysis-setup Specification

## Purpose

Lets consumers enable precise checks for domain-specific handler contracts while retaining their chosen static analyzer configuration.

## Requirements

### Requirement: Explicit analyzer setup
The package SHALL provide an explicit setup command that detects the consumer's active Psalm or PHPStan configuration and presents the proposed changes before applying them.

#### Scenario: Existing configuration
- **WHEN** a consumer runs setup with an existing analyzer configuration
- **THEN** the command shows the configuration it found and the proposed integration

#### Scenario: No configuration
- **WHEN** setup finds no analyzer configuration
- **THEN** it offers a choice of Psalm or PHPStan starter configuration

### Requirement: Handler contract checks
The configured analyzer integration SHALL report missing or incompatible domain-specific handler methods using the handler's declared generic input and result types.

#### Scenario: Invalid handler
- **WHEN** an implementation declares a handler interface but has an incompatible method
- **THEN** analysis reports an actionable error

### Requirement: Default deprecation integration
Ordinary PHPStan/Psalm integration SHALL enable read-port deprecation detection without requiring hexagonal.json or architecture checks. Detection SHALL include analyzed test files and respect analyzer input scope. Setup guidance SHALL explain PHPStan's default human formatter, hexagonalJson for structured warnings, and Psalm's --show-info=true visibility option. Existing consumer formatter overrides SHALL remain effective with their documented warning visibility limitations.

#### Scenario: No architecture configuration
- **WHEN** a consumer enables the ordinary integration and analyzes deprecated usage without hexagonal.json
- **THEN** deprecation detection runs

#### Scenario: Analyzed tests
- **WHEN** analyzed test code references ReadPortInterface
- **THEN** it is eligible for deprecation diagnostics even if exempt from architectural role checks

#### Scenario: Consumer-selected output
- **WHEN** setup encounters an explicit consumer formatter choice
- **THEN** it preserves that choice and documents how to enable structured warning output
