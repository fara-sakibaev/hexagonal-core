## Context

See proposal.md. The package targets PHP 8.5 and currently exposes marker handler interfaces, an event-recording aggregate base, and DTO-based query bus. The consumer owns analyzer configuration and infrastructure adapters.

## Goals / Non-Goals

**Goals:** Preserve concrete handler signatures; provide explicit, repeatable analyzer setup; expose aggregate identity; align query types; support durable integration events.

**Non-Goals:** Force analyzers to run in all consumer projects; ship database schema or broker adapter; guarantee exactly-once delivery.

## Decisions

1. Keep marker interfaces empty. Ship separate PHPStan rules and Psalm plugin checking an agreed public `handle` method against each implementation's template arguments. Prove rules with positive and negative consumer fixtures. A broad PHP method parameter would prohibit specialized signatures.
2. Provide an explicit Composer binary for setup. Detect standard configs and explicit paths in root scripts where possible; show a diff before edits; support dry run and repeat invocation. PHPStan adds an include; Psalm enables the plugin. When none exists, offer starter configs. Dependency Composer scripts do not execute in consumers; a Composer plugin would require `allow-plugins` consent.
3. Use `abstract public InternalIdInterface $id { get; }` on AggregateRoot. Concrete roots may use a narrower ID type. Document stable identity as a domain invariant; the get-only contract alone does not enforce immutability.
4. Restrict `Query<TResult>` to `TResult of DTO`; retain the query-to-result relationship in analyzer annotations. Restrict `ReadPortInterface` input to Query. Document migration from non-DTO query results and command-based read ports.
5. Keep outbox optional. An application coordinator persists aggregate and outbox records in one transaction. A relay publishes committed records with stable event IDs and retry; consumers deduplicate. Define event release/acknowledgment so failed persistence does not silently discard pending events. Avoid broker and SQL assumptions in core contracts.

## Risks / Trade-offs

- Analyzer not enabled in consumer CI → setup reports the needed CI command; documentation states enforcement boundary.
- Multiple analyzer configs → prompt for active path; do not guess or overwrite.
- Get-only property implemented with mutable storage → document and test identity stability at aggregate level.
- Relay publishes twice after crash → stable event ID and consumer deduplication.
- Breaking query template bound → migration examples and release note.

## Migration Plan

Release query/read-port changes as a breaking version; provide examples for DTO wrapping and separating command ports. Analyzer integration and outbox remain opt-in. Consumers run the setup command and review its diff before CI adoption.
