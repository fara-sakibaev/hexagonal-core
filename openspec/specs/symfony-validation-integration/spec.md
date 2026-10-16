# symfony-validation-integration Specification

## Purpose

Allows Symfony applications to use attributed input constraints through an optional integration while preserving the framework-independent core result.

## Requirements

### Requirement: Optional attributed validation
Consumers SHALL be able to validate attributed inputs through an optional integration without making Symfony a required dependency of the core package.

#### Scenario: Consumer without Symfony
- **WHEN** a consumer installs only the core package
- **THEN** core validation contracts remain usable without Symfony Validator

#### Scenario: Consumer with Symfony constraints
- **WHEN** an attributed input violates field or named custom constraints
- **THEN** the integration reports those violations through the core result with stable codes and paths

### Requirement: Named reusable custom constraints
The integration SHALL support named custom constraints as reusable rules that can be referenced from attributed inputs.

#### Scenario: Reused constraint
- **WHEN** multiple inputs apply the same custom constraint class
- **THEN** its definition and application sites remain discoverable by class references
