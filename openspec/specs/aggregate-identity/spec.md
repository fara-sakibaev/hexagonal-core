# aggregate-identity Specification

## Purpose

Provides a readable identity contract for aggregate roots while allowing each domain aggregate to choose its concrete internal identifier.

## Requirements

### Requirement: Aggregate identity
Every concrete aggregate root SHALL expose an identity compatible with InternalIdInterface through a readable property.

#### Scenario: Concrete identifier
- **WHEN** a concrete aggregate provides its own InternalIdInterface subtype
- **THEN** callers can read that identity through the aggregate root contract
