# Context Role Classification Specification

## Purpose

Enforce explicit architectural context boundaries and unique, layer-compatible abstraction roles in consumer application source code.

## Requirements

### Requirement: Explicit context discovery
The integration SHALL discover candidate context roots within configured first-party search paths from the immediate parent of at least one Application, Domain or Infrastructure directory. Context roots SHALL be explicitly declared; undeclared candidates SHALL produce diagnostics independent of namespace and Composer package layout.

#### Scenario: Incomplete package context
- **WHEN** packages/Orders/src/Domain exists without other layers
- **THEN** packages/Orders/src is a candidate and an undeclared-root diagnostic is emitted until explicitly configured

#### Scenario: Multiple source layouts
- **WHEN** declared contexts exist at src/App and packages/Orders/src
- **THEN** both are checked using their declared boundaries without assuming a common namespace

### Requirement: Unique contract-based role
The integration SHALL inspect named concrete classes, abstract classes, interfaces and enums using analyzer-resolved ancestry. It SHALL detect all independent matching roles, without runtime loading of consumer declarations. Adapter configuration role SHALL absorb its inherited infrastructure configuration role. Traits SHALL NOT receive standalone role classification.

#### Scenario: Cross-family ambiguity
- **WHEN** a class extends Command and implements ValidationInputInterface
- **THEN** an ambiguous-role diagnostic reports both roles rather than choosing the first

#### Scenario: Adapter specialization
- **WHEN** an enum implements AdapterConfigParameterInterface
- **THEN** classification reports the adapter role once, not both adapter and infrastructure configuration roles

### Requirement: Identifier specialization
InternalId and ExternalId SHALL each absorb inherited Id matches. InternalUid and ExternalUid SHALL resolve respectively to InternalId and ExternalId through their interfaces, without separate UID roles.

#### Scenario: Specialized identifier
- **WHEN** a type implements InternalIdInterface and IdInterface
- **THEN** it has exactly the InternalId role

#### Scenario: Conflicting identifiers
- **WHEN** a type implements InternalIdInterface and ExternalIdInterface
- **THEN** it has an ambiguous role error

### Requirement: Layer compatibility and cardinality
Application SHALL require exactly one Application, Type or Validation role. Domain SHALL require exactly one Domain or Type role, including DomainEnumInterface. Infrastructure SHALL permit zero roles or one port, Type, Validation or infrastructure configuration role, and SHALL reject independent multiple-role combinations and disallowed known roles. Application configuration parameter enums SHALL be permitted in Application. Domain enums SHALL NOT acquire an Application role merely because Application declarations reference them.

#### Scenario: Unknown required role
- **WHEN** a Domain or Application declaration has no recognized role
- **THEN** an unknown-role diagnostic is emitted

#### Scenario: Unclassified infrastructure helper
- **WHEN** an Infrastructure class has no matching role
- **THEN** no role diagnostic is emitted

#### Scenario: Misplaced known role
- **WHEN** a Command is declared in Domain or Infrastructure
- **THEN** a layer-compatibility diagnostic is emitted

#### Scenario: Infrastructure port implementation
- **WHEN** Infrastructure declares an implementation with exactly one repository port role
- **THEN** classification succeeds

#### Scenario: Infrastructure ambiguity
- **WHEN** an Infrastructure class implements two independent port roles
- **THEN** an ambiguous-role diagnostic is emitted

#### Scenario: Port configuration parameter
- **WHEN** an Application enum implements PortConfigParameterInterface
- **THEN** it satisfies Application role cardinality

#### Scenario: Infrastructure configuration parameter
- **WHEN** an Infrastructure enum implements InfrastructureConfigParameterInterface
- **THEN** it satisfies Infrastructure role compatibility

#### Scenario: Domain enum referenced from Application
- **WHEN** an Application DTO, command, or query references a Domain enum
- **THEN** each declaration is classified by its own contracts and the Domain enum remains valid

### Requirement: Context placement and anonymous classes
Classified declarations inside a context SHALL belong to a recognized layer. Anonymous classes SHALL be forbidden throughout contexts regardless of their roles.

#### Scenario: Declaration outside layers
- **WHEN** Orders/Helper.php declares a class directly within the Orders context root
- **THEN** an out-of-layer diagnostic is emitted

#### Scenario: Anonymous implementation
- **WHEN** context code creates an anonymous class, including inside a trait
- **THEN** an anonymous-class diagnostic is emitted

### Requirement: Explicit test exemption
Explicit test_paths SHALL exempt test code from architecture classification, placement and anonymous-class checks while retaining ordinary analyzer checks. Names alone SHALL NOT grant exemptions.

#### Scenario: Test fixture
- **WHEN** code in a configured test path contains anonymous or intentionally multi-role classes
- **THEN** architecture diagnostics are suppressed while ordinary analysis remains active

#### Scenario: Misleading test name
- **WHEN** production context code declares an anonymous class inside a class named ExampleTest
- **THEN** the anonymous-class diagnostic is still emitted

### Requirement: Complete actionable analysis
PHPStan and Psalm integrations SHALL provide equivalent architecture diagnostics with stable categories and source locations where applicable, continue after classification failures, and report configured scope not covered by analysis rather than silently skipping it.

#### Scenario: Several violations
- **WHEN** multiple declarations violate role rules
- **THEN** the analyzer reports their diagnostics without a classifier exception terminating the run

#### Scenario: Newly added context outside analyzer paths
- **WHEN** a new layer directory appears inside search_paths but outside analyzer file paths
- **THEN** discovery reports the undeclared candidate rather than silently accepting the analysis as complete
