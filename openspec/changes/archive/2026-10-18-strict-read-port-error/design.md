## Context

See proposal.md and specs/read-port-deprecation/spec.md. Current PHPStan integration collects ReadPort records for non-fatal formatters; Psalm registers DeprecatedReadPort at info severity. Detection already resolves aliases, inheritance and native/PHPDoc type references.

## Goals / Non-Goals

**Goals:** Make the existing diagnostics participate in each analyzer's normal failure and error reporting paths while preserving locations and filtering.

**Non-Goals:** Remove ReadPortInterface, change architecture-role allowances, or redesign detection.

## Decisions

- PHPStan: convert collected ReadPort findings into standard analyzer errors in the library's formatter integration, ensuring error totals and exit status fail. Preserve compatibility with explicit formatter overrides and structured output; warnings-only side channels are no longer sufficient.
- Psalm: report DeprecatedReadPort as an error by default through Psalm's issue configuration, retaining native locations and normal suppression/reporting behavior. Remove prior info-only default.
- Reuse existing resolved detection and message contract. Avoid duplicate diagnostics by choosing one reporting path per analyzer; retain mixed-error reporting through standard analyzer output.
- Update fixture expectations and migration docs to match hard failure. Do not add baselines or blanket suppressions.

## Risks / Trade-offs

- Existing consumer analysis will fail immediately → document replacements (RepositoryInterface for aggregate persistence, QueryHandlerInterface for DTO queries) and provide precise source locations.
- PHPStan cache/formatter APIs may differ across supported versions → verify cold and warm runs against declared dependency constraints and retain extension public APIs.
- Analyzer-native suppression could hide intended enforcement → test default strictness and document only targeted migration options if supported.

## Migration Plan

Implement and verify both integrations, update docs, then run the repository's relevant analyzer and test gates. Consumers migrate usages before upgrading; rollback is reverting the library version.
