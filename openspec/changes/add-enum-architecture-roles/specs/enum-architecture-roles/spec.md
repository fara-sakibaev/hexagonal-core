## Purpose

Define explicit architectural roles for domain and configuration enums.

## ADDED Requirements

### Requirement: Enum role contracts
The library SHALL provide `DomainEnumInterface`, `PortConfigParameterInterface`, `InfrastructureConfigParameterInterface`, and `AdapterConfigParameterInterface` contracts that can only be implemented by PHP enums.

#### Scenario: Valid enum declaration
- **WHEN** a pure or backed enum implements one role contract
- **THEN** the declaration is valid for that role

### Requirement: Domain enum use in Application
Application DTOs, commands, and queries SHALL be permitted to reference a Domain enum without changing the enum declaration's Domain role or requiring it to implement an Application role.

#### Scenario: Domain enum in command
- **WHEN** a command property is typed as a Domain enum
- **THEN** the enum remains classified as Domain and the command remains classified by its own Application contract

### Requirement: Adapter configuration specialization
`AdapterConfigParameterInterface` SHALL specialize `InfrastructureConfigParameterInterface`.

#### Scenario: Adapter configuration classification
- **WHEN** an enum implements the adapter configuration contract
- **THEN** it resolves to the specialized adapter role without an ambiguity from its parent role
