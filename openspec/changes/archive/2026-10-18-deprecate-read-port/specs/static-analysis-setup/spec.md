## ADDED Requirements

### Requirement: Default deprecation integration
Ordinary PHPStan/Psalm integration SHALL enable read-port deprecation detection without requiring hexagonal.json or architecture checks. Detection SHALL include analyzed test files and respect analyzer input scope. Setup guidance SHALL explain PHPStan's default human formatter, hexagonalJson for structured warnings, and Psalm's --show-info=true visibility option. Existing consumer formatter overrides SHALL remain effective with their documented warning visibility limitations.

#### Scenario: No architecture configuration
- **WHEN** a consumer enables the ordinary integration and analyzes deprecated usage without hexagonal.json
- **THEN** deprecation detection runs

#### Scenario: Analyzed tests
- **WHEN** analyzed test code references ReadPortInterface
- **THEN** it is eligible for deprecation diagnostics even if exempt from architectural role checks

#### Scenario: Consumer-selected output
- **WHEN** setup encounters an explicit consumer formatter choice
- **THEN** it preserves that choice and documents how to enable structured warning output
