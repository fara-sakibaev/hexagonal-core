## Context

See proposal.md. The core currently has empty generic handler markers enforced by PHPStan/Psalm and a setup command that imports handler checks. It has no validation contracts or Symfony Validator dependency. See ADR 0001 for the accepted boundary.

## Goals / Non-Goals

**Goals:** preserve concrete parameter types, enable named rules and direct references, keep the core framework-neutral, and make migration diagnostics opt-in.

**Non-Goals:** automatic source rewriting, global validator discovery, replacing aggregate invariants with input validation, or performing normalization during validation.

## Decisions

1. Put `ValidationInputInterface`, `ValidatorInterface<TInput>`, `RuleInterface<TInput>`, `Violation`, `ViolationCode`, `ViolationPath`, and immutable `ViolationList` under `Hexagonal\Validation`. Input and validator interfaces are markers; analyzer extensions require `public validate(ConcreteInput): ViolationList` on validators and a concrete input signature on reusable rules. PHP cannot implement a broad interface method with a narrower parameter. The alternative broad signature would force runtime narrowing and weaken references.
2. A validator is explicitly injected into its caller. It composes named rules and collects independent violations in a stable order. Each violation has a code and path, including a root path for object-level errors; the caller maps codes to localized messages. Avoid a service locator and presentation text in the core.
3. Validation inputs carry any external facts as a typed snapshot obtained by the caller. Validator and rule implementations do not fetch state or transform values. Keep business-state consistency checks in domain policies; place a concrete validator according to rule ownership, not a universal directory.
4. Build the Symfony bridge as an independently installable Composer package under `integrations/symfony-validator/`, with its own `symfony/validator` requirement and a core dependency. It runs Symfony's attribute metadata and converts violations into core codes/paths. Generic built-in field constraints may stay as attributes; reusable composite behavior uses named custom constraints. The bridge must define a deterministic code for constraints whose Symfony violation code is absent and preserve property paths, including class-level violations. The core Composer requirements stay unchanged.
5. Add signature checks to the existing PHPStan/Psalm integrations, but put legacy-name checks in separate importable profiles. Report declarations beginning with `validate` outside validator classes and `PolicyInterface` implementations; exempt only methods proven to implement or override a required external API signature. Symfony `#[Assert\Callback]` methods are not such a required signature and are migration candidates. A diagnostic should identify the class/method and suggest extraction. A method-name check is a migration aid, not proof that all ad hoc checks were found; `assert*` and inline checks require review.

## Risks / Trade-offs

- Analyzer checks are necessary for the narrowed concrete signature; document that runtime PHP alone cannot enforce it.
- A name-based migration check can miss renamed or inline validation; pair it with a documented inventory and representative Metrix examples.
- Converting Symfony codes can lose constraint-specific detail; test field, class, nested, and custom constraints, and define stable fallback codes.
- An external-state snapshot can become stale before a write; callers must obtain it in the same consistency boundary when correctness depends on current state.

## Migration Plan

1. Release the core contracts and signature checks without enabling the legacy-name profile.
2. Release the optional bridge and examples. Existing consumers retain their current setup.
3. Inventory `validate*` methods in a consumer; move input checks to named validators, retain domain policies, and split normalization from checking.
4. Enable the strict profile in CI after that consumer's migration. Rollback is removal of the optional profile; the core contracts do not alter existing call sites.
