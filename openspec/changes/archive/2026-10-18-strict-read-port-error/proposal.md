## Why

The existing ReadPortInterface diagnostic is informational and does not fail analysis, so consumers can continue adding dependencies on an abstraction scheduled for removal. Make every analyzed use fail PHPStan and Psalm with an actionable migration message.

## What Changes

- **BREAKING** Treat detected ReadPortInterface usage as an analysis error in PHPStan and Psalm.
- Preserve analyzer-resolved detection locations, including direct and indirect inheritance and native/PHPDoc references.
- Keep unrelated same-name types excluded and preserve existing architecture diagnostics.
- Update setup and migration guidance to describe the failing diagnostic and migration options.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `read-port-deprecation`: change diagnostics from non-fatal warnings to strict analysis errors.
- `static-analysis-setup`: document the error behavior and migration guidance.

## Impact

PHPStan/Psalm ReadPort integrations, fixtures and analyzer assertions, setup and migration documentation, and OpenSpec requirements. Existing consumers with ReadPort usage will need to migrate before analysis passes.
