## Why

Consumer applications currently have no enforced architectural role coverage. The uncommitted role enums return first matches through runtime inheritance checks, which can conceal multiple roles and cannot establish context boundaries.

## What Changes

- Discover context candidates within configured first-party search paths and require explicit context roots.
- Add analyzer-backed RoleMapper classification shared by PHPStan and Psalm; keep enums as role catalogs.
- Enforce role cardinality, layer compatibility, explicit identifier specialization, and named declaration placement.
- Forbid anonymous classes in contexts, with explicit test-path exemptions.
- Extend setup and documentation for consumer layouts including src/App and packages.
- Replace the uncommitted enum fromClassString design and UID-specific roles; existing released handler checks remain supported.

## Capabilities

### New Capabilities
- `context-role-classification`: Context discovery, role resolution, layer rules, diagnostics and test exemptions.

### Modified Capabilities
- `static-analysis-setup`: Configure and validate consumer architecture scope without assuming src.

## Impact

StaticAnalysis role enums, PHPStan/Psalm adapters, setup command, analyzer configuration, consumer documentation and integration fixtures. No runtime application behavior or cross-layer dependency rules change. Existing working-tree changes must be reconciled rather than overwritten.
