# Hexagonal Core

Shared language for architectural roles, validation contracts, and domain rules.

## Language

**Abstraction role**:
An architectural responsibility assigned to a type in an application using Hexagonal Core. Types in Application and Domain have exactly one role; types in Infrastructure may have no role. Multiple roles are forbidden in all three layers.

**Context root**:
The explicitly declared boundary of a consumer application's architectural context, containing its Application, Domain, or Infrastructure layers. A context may contain only some of these layers.

**Validation input**:
A typed object carrying facts to be checked across or within layers. It does not own the checking rules.

**Validator**:
A named checker for a validation input. It reports violations so the caller can decide how to respond.

**Domain policy**:
A domain rule concerning aggregate invariants or decisions over domain state. It remains distinct from validation of an application input.
