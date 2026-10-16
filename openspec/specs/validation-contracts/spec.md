# validation-contracts Specification

## Purpose

Provides a typed, layer-neutral way for callers to check input facts through named validators and consume structured violations.

## Requirements

### Requirement: Typed validation input
The package SHALL let a caller pass a typed validation input to a named validator without requiring that input to extend an application DTO or depend on a framework.

#### Scenario: Input in a domain context
- **WHEN** a domain-owned validator checks an input containing domain facts
- **THEN** the domain code can use the validation contract without depending on the application layer

### Requirement: Explicit validator and reusable rules
The package SHALL support one named validator for an input type and named reusable rules that can be referenced by multiple validators. A validator SHALL accept its declared concrete input type and SHALL be callable as an explicit dependency from a port or other caller.

#### Scenario: Validating inside a port
- **WHEN** a port receives its validator and supplies the validator's declared input type
- **THEN** the call returns a structured result without locating the validator through a global registry

#### Scenario: Reusing a rule
- **WHEN** two validators apply the same rule
- **THEN** both can reference the same named rule class

### Requirement: Complete structured violations
The validator SHALL return all independent violations found during one check. Each violation SHALL identify a stable rule code and a path; the caller SHALL be able to supply human-readable messages separately.

#### Scenario: Multiple invalid fields
- **WHEN** two independent rules reject an input
- **THEN** the result contains both violations with their codes and paths

#### Scenario: Valid input
- **WHEN** no rule rejects an input
- **THEN** the result contains no violations

### Requirement: Pure checking
Validation SHALL report violations without normalizing input or fetching external state. A caller SHALL be able to pass previously obtained external facts as typed input data.

#### Scenario: State-dependent check
- **WHEN** a check needs a value from a port or data store
- **THEN** the caller obtains a consistent snapshot and includes it in the validation input before invoking the validator
