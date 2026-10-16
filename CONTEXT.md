# Hexagonal Core

Shared language for validation contracts and domain rules.

## Language

**Validation input**:
A typed object carrying facts to be checked across or within layers. It does not own the checking rules.

**Validator**:
A named checker for a validation input. It reports violations so the caller can decide how to respond.

**Domain policy**:
A domain rule concerning aggregate invariants or decisions over domain state. It remains distinct from validation of an application input.
