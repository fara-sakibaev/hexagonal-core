## Context

See proposal.md and ADR 0002. Existing HandlerRule uses PHPStan reflection; the draft enums instead use runtime is_a and first-match dispatch. Setup currently defaults starter configurations to src. Both analyzers already have contract checks that must remain intact.

## Goals / Non-Goals

Goals: deterministic classification across both analyzers, explicit architectural scope, and complete diagnostics without loading consumer classes.

Non-goals: runtime enforcement, dependency/import restrictions, automatic modification of consumer types, and role classification of traits or test code.

## Decisions

### Shared mapper and analyzer adapters

Keep four enum families as catalogs. RoleMapper consumes an analyzer-neutral description of a named declaration and its ancestors, collects distinct matching roles, and applies only InternalId/ExternalId-over-Id specialization. It returns a unique role, unknown result, or ambiguity with all conflicting roles. Analyzer rules apply layer policy and generate stable diagnostic identifiers with source locations. Do not use runtime autoload/reflection on consumer declarations. Unresolved ancestry remains an analyzer error and must not be accepted as legitimate zero-role Infrastructure code.

A merged first-match enum and sequential exception-driven probing are rejected because both hide independent matches. Remove draft UID cases and enum-owned fromClassString recognition; these are uncommitted APIs, not released compatibility promises.

### Scope configuration and discovery

Use search_paths, context_roots and test_paths, relative to the consumer configuration base. Normalize paths and reject invalid or ambiguous overlapping declarations rather than relying on traversal order. Test paths take precedence. Search first-party areas, excluding dependencies/generated vendor trees, for exact layer directory names; each immediate parent is a candidate. One layer suffices. Newly discovered undeclared candidates generate diagnostics on every analysis invocation, including files outside the current analyzer file list. Report scope coverage gaps rather than silently claiming complete analysis.

The same scope service supports setup previews and analyzer startup. Composer autoload can suggest search paths but cannot establish context boundaries. This supports src/App, packages/Orders/src and several contexts under one source root. Explicit configuration is preferred to filename heuristics or requiring three complete layers.

### Layer policy

Application allows Application, Type and Validation roles; Domain allows Domain and Type. Both require exactly one. Infrastructure allows zero roles, or one port/Type/Validation role. Its port catalog includes CommandBus, QueryBus, DomainEventPublisher, IntegrationEventPublisher, ReadPort, Repository, EventIdFactory, EventPublisher, Outbox and Transaction. Domain currently defines no additional port roles. Other known Application/Domain roles are forbidden in Infrastructure, including handlers and model objects.

Apply this to named concrete/abstract classes, interfaces and enums. Traits have no standalone role classification. Anonymous-class prohibition is a separate syntax check across context files, including anonymous classes inside traits. Classified declarations inside a context but outside its layers are errors. Explicit test_paths bypass these architecture checks while retaining ordinary analysis and existing handler checks.

### Diagnostics and acceptance

Use separate stable identifiers for unknown role, ambiguous roles, incompatible layer, out-of-layer declaration, anonymous class, undeclared context and invalid/incomplete scope. Collect failures; do not abort on an ordinary classification error. Both adapters must agree on semantic results, including declarations unavailable to runtime autoload and multi-declaration files.

## Risks / Trade-offs

- Directory names can yield false candidates: explicit root declarations and actionable discovery diagnostics make decisions visible.
- Analyzer paths/cache can hide files: validate scope coverage and rerun discovery even when class analysis is cached; test additions between runs.
- Broad test exclusions weaken enforcement: require explicit paths and document their priority; never infer exemptions from class names.
- Existing consumer code may fail new checks: activate architecture enforcement through explicit scope configuration while preserving existing handler-only integration.

## Migration Plan

Reconcile the draft role code, introduce the shared model and both adapters, then extend setup previews and documentation. Consumers declare scope, review discovered roots, run the analyzer and resolve diagnostics. Rollback removes architecture scope activation while retaining existing handler validation. No application data migration is involved.
