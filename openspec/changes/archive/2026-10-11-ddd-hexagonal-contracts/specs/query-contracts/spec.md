## Purpose

Keeps query response types within application DTOs and makes outbound read ports accept queries consistently.

## ADDED Requirements

### Requirement: Query DTO result
A query's declared result type SHALL be a subtype of DTO, and query handlers and buses SHALL preserve that declared result relationship in static analysis.

#### Scenario: Typed query
- **WHEN** a query declares a specific DTO result
- **THEN** static analysis identifies that DTO as the expected result

### Requirement: Query-only read port
An outbound read port SHALL accept a Query as its generic input type and SHALL reject Command as that type.

#### Scenario: Command as read input
- **WHEN** a consumer supplies Command as the read port input type
- **THEN** static analysis reports a type error

