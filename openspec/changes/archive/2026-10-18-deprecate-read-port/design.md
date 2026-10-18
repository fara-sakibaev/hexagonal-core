## Context

See proposal.md and ADR-0003 for motivation and agreed policy. ReadPortInterface is currently an empty generic marker and an allowed Infrastructure port. Existing architecture checks require explicit configuration; deprecation detection must work independently. PHPStan rules currently return errors, while its public ErrorFormatter API can read cached collected data and return an error-derived exit code. Psalm supports info diagnostics but hides their details by default.

## Goals / Non-Goals

**Goals:** separate reference detection from reporting, use analyzer-resolved source types, preserve errors and cached diagnostics, and keep output machine-readable.

**Non-Goals:** remove the interface in this release, relax layer rules, automate consumer migrations, add a CLI wrapper, or report through direct stderr writes from rules. No blanket suppression or baseline is introduced.

## Decisions

### Collect resolved source references independently of architecture

Use dedicated analyzer adapters for native and PHPDoc types, inheritance, aliases and compound types. Match the fully qualified library contract rather than short-name text or runtime consumer autoload. Preserve reference locations and deduplicate overlapping visits by source position and identifier. For indirect inheritance report the affected declaration; the library's own defining declaration is not a consumer usage. Bare unused imports and arbitrary strings are not type usage; reflection strings are outside this change. Consumer descendants referenced as types count when analyzer-resolved ancestry reaches the deprecated contract.

Keep a shared message/identifier and warning record (identifier, message, file, line), rather than forcing warnings into the existing error-only architecture diagnostics. Analyzed tests remain eligible; analyzer scope determines which consumer files are examined.

### PHPStan collector and formatters

Collect serializable warning records via the collector API and consume AnalysisResult::getCollectedData() in library formatters so warm-cache results remain visible. Register a human formatter (hexagonal) as the integration default while respecting explicit consumer overrides; register hexagonalJson separately. Delegate ordinary error output/exit behavior wherever possible. JSON retains the stock error structure and adds a top-level warnings array with deterministic ordering. Do not mutate private/internal AnalysisResult state or inject warnings into error counts.

The alternative of standard RuleErrorBuilder diagnostics fails the nonfatal requirement. Direct stderr printing bypasses formatter policy. Stock --error-format=json intentionally omits these warnings; it remains a supported override, not a promise of warning visibility.

### Psalm native info

Register a dedicated issue with info as its default severity and include hexagonal.deprecatedReadPort in the message. Use native issue locations and reporting, retaining normal explicit consumer issue configuration. Document --show-info=true. Verify cold and cached CLI runs; choose hooks that actually run or restore diagnostics on incremental analysis, rather than assuming an AST callback alone suffices.

Document deprecation on the interface without unintentionally introducing an additional default error through analyzer built-in deprecated-symbol handling. Verify this interaction before selecting annotation details; the shared warning contract is authoritative.

### Preserve current contracts

Keep ReadPort role classification, generic Query constraint and current Infrastructure allowance. Existing invalid generics, handlers and layer violations continue to fail. Migration examples distinguish aggregate repositories from Application DTO queries and show that Infrastructure dependencies may need restructuring.

## Risks / Trade-offs

- Warning visibility depends on reporting settings. Document stock JSON omission and Psalm info visibility next to installation commands.
- Default formatter selection can conflict with existing consumer configuration. Test explicit overrides and setup previews; do not silently rewrite consumer choices.
- PHPDoc locations and inheritance can create duplicates or missed nested references. Test aliases, unions/intersections, nested generics, inherited contracts and unrelated same-name types.
- Cached diagnostics can disappear or persist after migration. Exercise warm runs, edited references and deleted files for both analyzers.
- Analyzer APIs differ across supported versions. Use public extension points and verify compatibility with the supported Composer constraints before delivery.

## Migration Plan

Ship detection, formatters, docs and tests together in the deprecation release. Consumers migrate aggregate ports to repositories or move DTO query handling into Application, then rerun analysis. They opt into hexagonalJson or Psalm info visibility as needed. Rollback restores the prior library version; this change requires no data migration. The following breaking release removes the interface and enforces failures for remaining usage in a separate change; no release number is invented here.

### Approved integration compatibility decisions

Use hexagonalJson: PHPStan/Nette service names reject hyphens. Declare phpstan/phpdoc-parser:^2 as a runtime dependency so Psalm-only consumers receive the parser. Both refinements were approved by the user.
