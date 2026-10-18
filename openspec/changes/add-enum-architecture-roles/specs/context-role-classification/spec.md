## MODIFIED Requirements

### Requirement: Unique contract-based role
The integration SHALL inspect named concrete classes, abstract classes, interfaces and enums using analyzer-resolved ancestry. It SHALL detect all independent matching roles, without runtime loading of consumer declarations. Adapter configuration role SHALL absorb its inherited infrastructure configuration role. Traits SHALL NOT receive standalone role classification.

#### Scenario: Cross-family ambiguity
- **WHEN** a class extends Command and implements ValidationInputInterface
- **THEN** an ambiguous-role diagnostic reports both roles rather than choosing the first

#### Scenario: Adapter specialization
- **WHEN** an enum implements AdapterConfigParameterInterface
- **THEN** classification reports the adapter role once, not both adapter and infrastructure configuration roles

### Requirement: Layer compatibility and cardinality
Application SHALL require exactly one Application, Type or Validation role. Domain SHALL require exactly one Domain or Type role, including DomainEnumInterface. Infrastructure SHALL permit zero roles or one port, Type, Validation or infrastructure configuration role, and SHALL reject independent multiple-role combinations and disallowed known roles. Application configuration parameter enums SHALL be permitted in Application. Domain enums SHALL NOT acquire an Application role merely because Application declarations reference them.

#### Scenario: Port configuration parameter
- **WHEN** an Application enum implements PortConfigParameterInterface
- **THEN** it satisfies Application role cardinality

#### Scenario: Infrastructure configuration parameter
- **WHEN** an Infrastructure enum implements InfrastructureConfigParameterInterface
- **THEN** it satisfies Infrastructure role compatibility

#### Scenario: Domain enum referenced from Application
- **WHEN** an Application DTO, command, or query references a Domain enum
- **THEN** each declaration is classified by its own contracts and the Domain enum remains valid
