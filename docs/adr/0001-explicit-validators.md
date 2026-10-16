---
status: accepted
---

# Explicit validators

Validation of typed inputs, including validation performed inside ports, belongs in named validator classes that callers receive as explicit typed dependencies. Each input type has a validator; reusable checks can be separate named rules. Validators report violations to the caller. This makes rules and their uses navigable and avoids scattered `validate*` methods. Aggregate invariants and decisions over domain state remain in domain policies.

The shared contracts live in a layer-neutral `Hexagonal\Validation` namespace. A concrete validator belongs to the layer that owns its rule: domain rules independent of input technology may live in Domain; request and source-format rules live in Application or adapters. Domain code must not depend on Application contracts merely to perform validation.

The core validation contract does not depend on Symfony. A separate integration can execute Symfony constraints and translate their violations. An importable PHPStan/Psalm rule should report `validate*` methods outside validators and domain policies, with explicit exceptions for methods required by external APIs. Checking only classes that adopt the new contract would miss legacy code; interpreting arbitrary method bodies would be unreliable.

A validator collects all independent violations in one call. When a check needs external state, the caller obtains a consistent snapshot and passes it as part of the typed validation input; the validator does not read that state itself.

Each violation carries a stable rule code and a path, represented by dedicated types. The consuming layer owns human-readable messages. The migration rule is a separate, opt-in analyzer profile that fails CI when enabled; it is not added to the existing setup by default.

The validator interface is a generic marker: each implementation declares a concrete `validate(Input): ViolationList` method, and PHPStan/Psalm enforce the signature. A validator reports violations only. Normalization happens before the typed validation input is checked.
