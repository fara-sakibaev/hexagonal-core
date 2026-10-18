# query-contracts Specification

## Purpose

Keeps query response types within application DTOs and makes outbound read ports accept queries consistently.

## Requirements

### Requirement: Query DTO result
A query's declared result type SHALL be a subtype of DTO, and query handlers and buses SHALL preserve that declared result relationship in static analysis.

#### Scenario: Typed query
- **WHEN** a query declares a specific DTO result
- **THEN** static analysis identifies that DTO as the expected result

### Requirement: Query-only read port
An outbound read port SHALL accept a Query as its generic input type and SHALL reject Command as that type. ReadPortInterface SHALL remain available with this contract during its deprecation release, with nonfatal usage diagnostics. Migration guidance SHALL direct aggregate persistence to RepositoryInterface and DTO queries to QueryHandlerInterface in Application, explaining that Infrastructure adapters require responsibility and dependency review. Documentation SHALL announce removal in the next breaking release; this change SHALL NOT remove the interface or its existing architectural role.

#### Scenario: Command as read input
- **WHEN** a consumer supplies Command as the read port input type
- **THEN** static analysis reports a type error

#### Scenario: Valid deprecated read input
- **WHEN** a consumer uses ReadPortInterface with a valid Query input
- **THEN** the contract remains usable and its deprecation alone does not fail analysis

#### Scenario: Infrastructure migration guidance
- **WHEN** a consumer reads the migration documentation for an Infrastructure read adapter
- **THEN** it explains aggregate versus DTO responsibilities and that QueryHandlerInterface belongs in Application rather than being a universal marker replacement
