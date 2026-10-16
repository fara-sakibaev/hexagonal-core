## 1. Core contracts

- [x] 1.1 Add neutral input, generic validator, and reusable rule contracts under `Hexagonal\Validation`; verify a domain-owned example has no Application or Symfony dependency.
- [x] 1.2 Add immutable violation code, path, violation, and list types; verify root and field paths, valid empty results, and accumulation of independent failures in focused tests.
- [x] 1.3 Add a typed example with explicit validator injection and an external-state snapshot; verify it reports violations without fetching or normalizing data.

## 2. Static analysis

- [x] 2.1 Extend PHPStan and Psalm checks for concrete validator and rule signatures; verify valid fixtures pass and missing or incompatible methods fail in both analyzers.
- [x] 2.2 Implement separate opt-in legacy-method profiles for PHPStan and Psalm; verify `validateShape()` in a DTO fails, validator and policy methods pass, and required external API overrides pass.
- [x] 2.3 Verify the existing setup command leaves the legacy profile disabled and existing handler checks pass unchanged.

## 3. Optional Symfony integration

- [x] 3.1 Add an independently installable Composer package under `integrations/symfony-validator/`; verify its dependency metadata requires Symfony Validator while the core package does not.
- [x] 3.2 Implement attributed-input validation and conversion to core code/path violations, including deterministic fallback codes; verify field, class, nested, and custom constraint cases in integration tests.
- [x] 3.3 Document a named reusable custom constraint and an attributed input using the bridge; verify the example runs and class references point to the shared rule.

## 4. Documentation and gates

- [x] 4.1 Document layer ownership, explicit port injection, normalization before validation, state-snapshot consistency, and consumer migration steps; verify examples match the public contracts.
- [x] 4.2 Run package tests, PHPStan, Psalm, Composer validation for both packages, and strict OpenSpec validation; fix findings and rerun the full gate until clean.
