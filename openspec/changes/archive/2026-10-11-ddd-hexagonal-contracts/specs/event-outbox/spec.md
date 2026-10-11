## Purpose

Enables reliable integration event delivery when an aggregate change and an outgoing event must survive or roll back together.

## ADDED Requirements

### Requirement: Atomic event recording
The optional outbox coordination SHALL persist aggregate changes and corresponding outgoing event records in one transaction.

#### Scenario: Transaction rollback
- **WHEN** aggregate persistence fails and the transaction rolls back
- **THEN** no outgoing event record is committed

#### Scenario: Successful commit
- **WHEN** the aggregate and event record commit
- **THEN** the event remains available for later publication

### Requirement: At-least-once publication
The event relay SHALL retry unpublished records and SHALL permit duplicate delivery; consumers SHALL be given a stable event identifier for deduplication.

#### Scenario: Crash after publish
- **WHEN** the relay publishes an event but crashes before marking it delivered
- **THEN** it can publish the same identified event again

