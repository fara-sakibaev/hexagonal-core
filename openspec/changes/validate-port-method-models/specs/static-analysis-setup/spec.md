## MODIFIED Requirements

### Requirement: Handler contract checks
The configured PHPStan and Psalm integrations SHALL report missing or incompatible domain-specific handler methods using the handler's declared generic input and result types. They SHALL also validate public method parameters on library port interfaces and consumer interfaces that extend those ports. Parameters representing application or domain models SHALL use the appropriate library model base type or a subtype. Arbitrary field collections SHALL NOT be accepted as substitutes. Scalar parameters with independent meaning MAY be used.

#### Scenario: Invalid handler
- **WHEN** an implementation declares a handler interface but has an incompatible method
- **THEN** analysis reports an actionable error

#### Scenario: Port method uses a library model
- **WHEN** a public method on a library port or its consumer interface accepts the appropriate library model or a subtype
- **THEN** PHPStan and Psalm accept the parameter

#### Scenario: Port method uses arbitrary fields
- **WHEN** a public method on a library port or its consumer interface accepts an array or map-like structure as a substitute for a typed model
- **THEN** PHPStan and Psalm report an actionable diagnostic

#### Scenario: Port method has a meaningful scalar parameter
- **WHEN** a public port method accepts a scalar parameter with independent meaning
- **THEN** PHPStan and Psalm do not reject it solely for being scalar

#### Scenario: Unrelated interface method
- **WHEN** a public method belongs to an interface that does not extend a library port contract
- **THEN** this port parameter check does not report it
