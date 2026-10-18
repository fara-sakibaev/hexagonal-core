## Context

The role mapper already resolves analyzer-provided ancestry into catalog roles, and `Layer` enforces per-layer cardinality. The current contract catalogs are enums implementing `AbstractionRoleInterface`; the new consumer markers are PHP interfaces implemented by consumer enums.

## Goals / Non-Goals

**Goals:** add four public markers, integrate them into shared PHPStan/Psalm classification, preserve one-role semantics with adapter specialization, and document legal placement.

**Non-Goals:** runtime configuration loading, enum metadata, serialization, secret handling, or changes to how DTO/command/query payloads are transported.

## Decisions

- Declare public markers under `Hexagonal\Enum` and extend `UnitEnum`; this prevents ordinary classes from implementing enum-only roles.
- Treat each marker as a catalog role. Make adapter configuration inherit infrastructure configuration and normalize that parent match as explicit specialization, analogous to InternalId/ExternalId handling.
- Extend layer policy to allow the four roles in their intended layers; Domain enum references from Application are type dependencies, not declarations and do not affect role resolution.
- Add both analyzer fixtures for direct and transitive ancestry, pure and backed enums, wrong layers, ambiguous independent roles, specialization, and DTO/command/query references.

## Risks / Trade-offs

- Public names and namespace become compatibility commitments; use one documented namespace and stable names.
- PHPStan and Psalm may expose enum ancestors differently; test both against equivalent source fixtures.
- PHP enum interfaces inherit `UnitEnum`; verify parser/reflection ancestry handling and role normalization end to end.

## Migration Plan

Add contracts and catalog entries, update layer matrix and diagnostics, then add analyzer fixtures and public docs. Existing consumers need no migration unless they choose to mark enums.
