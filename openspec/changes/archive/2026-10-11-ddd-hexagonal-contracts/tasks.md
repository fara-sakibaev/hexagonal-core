## 1. Handler analysis

- [x] 1.1 Define the generic handler method conventions and verify valid and invalid consumer fixtures cover commands, queries, and events.
- [x] 1.2 Implement PHPStan rules and verify invalid fixtures fail while valid fixtures pass.
- [x] 1.3 Implement Psalm plugin checks and verify the same fixtures produce equivalent results.

## 2. Consumer setup

- [x] 2.1 Add an explicit setup binary that discovers active analyzer configs and verify detection with standard, custom, and ambiguous paths.
- [x] 2.2 Add preview, approved edits, starter-config choice, and idempotency; verify a second run makes no changes.
- [x] 2.3 Document root CI/script integration and verify both analyzers run against an example consumer.

## 3. Domain and query contracts

- [x] 3.1 Add aggregate identity get contract and verify concrete InternalIdInterface subtype access and PHP syntax checks.
- [x] 3.2 Constrain Query result to DTO and align handler/bus annotations; verify concrete DTO inference and rejection of non-DTO results.
- [x] 3.3 Restrict ReadPortInterface to Query and verify Command input is rejected by static analysis.

## 4. Durable events

- [x] 4.1 Define optional transaction/outbox coordination and stable event identity; verify rollback leaves no committed outbox record in an adapter fixture.
- [x] 4.2 Define safe event acknowledgment around failed persistence; verify retry retains the pending events.
- [x] 4.3 Demonstrate relay retry and duplicate delivery with an in-memory adapter; verify consumer deduplication by event ID.

## 5. Release readiness

- [x] 5.1 Document breaking query/read-port migrations and optional analyzer/outbox adoption; verify examples match public contracts.
- [x] 5.2 Run package checks and OpenSpec validation; verify no planning requirement remains unimplemented.
