## Why

Consumer projects accumulate ad hoc `validate*` methods across ports, DTOs, parsers, and aggregates. This obscures rule ownership and reuse. Named, typed validators and an opt-in migration check make rules discoverable and enforce a consistent boundary without moving aggregate invariants out of domain policies.

## What Changes

- Add layer-neutral validation input, validator, rule, violation, code, and path contracts with concrete input typing checked by PHPStan and Psalm.
- Define pure validation that collects independent violations; callers obtain any external-state snapshot and own human-readable messages.
- Provide a separate Symfony Validator integration for attributed inputs and named constraints, without requiring Symfony in the core package.
- Add an opt-in PHPStan/Psalm migration profile that reports `validate*` methods outside validators and domain policies, with narrowly defined external API exceptions.
- Document context-owned placement and usage from a port.

## Capabilities

### New Capabilities

- `validation-contracts`: Typed inputs, validators, reusable rules, violations, and explicit invocation.
- `symfony-validation-integration`: Optional translation of Symfony constraint results into core violations.
- `validation-migration-checks`: Opt-in analysis of validator signatures and legacy `validate*` methods.

### Modified Capabilities

None.

## Impact

New contracts and analyzer rules in this package; separate Symfony integration package; documentation and focused tests. Existing handler setup remains unchanged. No existing consumer is forced to adopt the migration profile.
