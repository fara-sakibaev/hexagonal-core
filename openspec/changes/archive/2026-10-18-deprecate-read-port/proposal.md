## Why

Consumers need time to migrate ReadPortInterface before its removal. Nonfatal diagnostics should explain whether aggregate persistence belongs behind RepositoryInterface or a DTO query belongs in an Application QueryHandlerInterface, as agreed in ADR-0003.

## What Changes

- Deprecate ReadPortInterface while retaining its generic contract and architectural role during this release.
- Detect direct and inherited usage, native type references and PHPDoc references, including analyzed tests, through ordinary PHPStan/Psalm integration without hexagonal.json.
- Report file/line warnings without changing error counts or failing otherwise successful analysis.
- Provide default PHPStan human output and opt-in hexagonalJson output; use Psalm info with --show-info=true for visibility.
- Document migration and announce removal in the next breaking release. **BREAKING (future release only):** remove the interface and reject remaining usage; removal is outside this change.

## Capabilities

### New Capabilities

- `read-port-deprecation`: detection, diagnostics, reporting, cache behavior and migration notice.

### Modified Capabilities

- `query-contracts`: retain query-only read ports during the deprecation window and define their migration lifecycle.
- `static-analysis-setup`: enable deprecation detection with ordinary integrations and document warning output configuration.

## Impact

ReadPortInterface documentation; PHPStan collectors/formatters and extension configuration; Psalm plugin issues/hooks; analyzer setup guidance; consumer migration docs and CLI integration tests. The runtime dependency phpstan/phpdoc-parser ^2 supplies the PHPDoc AST parser for both integrations, including consumers that install only Psalm. Existing architecture classification and unrelated diagnostics remain intact. Existing custom formatter overrides continue to control output, with the documented warning visibility limitation.
