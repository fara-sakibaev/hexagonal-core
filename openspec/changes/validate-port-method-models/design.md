## Context

The package already provides parallel PHPStan and Psalm integrations for architecture and handler contract diagnostics. Port contracts include generic model boundaries, such as `RepositoryInterface<TAggregate of AggregateRoot>`, `QueryBusInterface::ask(Query): DTO`, and `CommandBusInterface::dispatch(Command)`. Consumer interfaces may extend these library contracts.

## Goals / Non-Goals

**Goals:**
- Detect public port method parameters that replace typed models with arbitrary field collections.
- Apply equivalent behavior in PHPStan and Psalm.
- Limit checks to library ports and consumer interfaces extending library port contracts.

**Non-Goals:**
- Runtime validation or changes to port APIs.
- Rejecting meaningful scalar parameters.
- Enforcing model rules on unrelated interfaces.

## Decisions

- The library port inventory is `RepositoryInterface<TAggregate of AggregateRoot>`, `ReadPortInterface<TInput of Query<DTO>>` (deprecated marker), `QueryBusInterface::ask(Query): DTO`, `CommandBusInterface::dispatch(Command)`, `DomainEventPublisherInterface::publish(DomainEvent ...$event)`, and `IntegrationEventPublisherInterface::publish(IntegrationEvent ...$event)`. Only the latter four declare callable public methods today; repository/read-port are empty generic markers. Outbox `EventPublisherInterface` is an event service, not a port contract.
- Discover consumer interfaces through inheritance from the six `Application\\Port\\...` contracts. Expected parameter models come from the method signatures or the port's generic model role; scalar parameters remain unrestricted.
- For methods declared by consumer interfaces of generic repository/read ports, validate each model parameter against the resolved generic binding (`TAggregate` or `TInput`) and its library base family. Scalar parameters with independent meaning are excluded from model classification; arrays and map-like parameter types cannot stand in for the generic model.

- Use the existing static-analysis integrations and diagnostics rather than adding a runtime mechanism.
- Determine port scope from library port contracts and interface inheritance; inspect effective public methods, including methods declared by consumer interfaces.
- Validate model-typed parameters against the model role declared by the corresponding port contract. Permit the library base model and its subtypes; reject array/map-like substitutes. Keep scalar parameters outside this model substitution rule.
- Add matching valid and invalid fixtures for both analyzers so the behavior remains aligned.

## Risks / Trade-offs

- PHPStan and Psalm expose inherited generic method types differently; the implementations must normalize their views to preserve equivalent diagnostics.
- The library's current port set is small, but future port contracts must declare enough generic/type information for the check to identify the expected model role.
