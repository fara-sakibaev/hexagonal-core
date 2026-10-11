## Purpose

Lets consumers enable precise checks for domain-specific handler contracts while retaining their chosen static analyzer configuration.

## ADDED Requirements

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

