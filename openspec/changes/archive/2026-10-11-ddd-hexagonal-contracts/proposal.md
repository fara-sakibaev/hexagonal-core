## Why

Consumers need precise DDD contracts while retaining domain-specific handler signatures. Static analysis setup, aggregate identity, query result types, and reliable event publication currently lack a shared design.

## What Changes

- Add optional Psalm and PHPStan rules for marker-interface implementations, plus an explicit guided setup command.
- Add an aggregate identity read contract based on `InternalIdInterface`.
- Constrain query results to `DTO` and remove commands from `ReadPortInterface`. **BREAKING** for consumers relying on other query results or command read ports.
- Define optional transactional outbox coordination for integration events, including at-least-once delivery semantics.

## Capabilities

### New Capabilities

- `static-analysis-setup`: Discover and configure consumer analyzers and validate handler contracts.
- `aggregate-identity`: Expose aggregate identity through a get-only abstract property contract.
- `query-contracts`: Align query result and read-port types.
- `event-outbox`: Coordinate aggregate persistence with durable event publication.

### Modified Capabilities

None.

## Impact

Application and domain contracts, optional analyzer integrations, consumer configuration, documentation, and an optional outbox adapter boundary. Existing staged work remains outside this proposal.
