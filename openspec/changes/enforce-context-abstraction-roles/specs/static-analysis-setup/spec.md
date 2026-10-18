## ADDED Requirements

### Requirement: Explicit consumer architecture scope setup
Setup SHALL preview architecture search paths, context roots and test paths for the selected analyzer, preserve existing analyzer configuration and handler checks, and require explicit application of changes. It SHALL support package and source-root layouts without assuming src or App. Architecture enforcement SHALL activate through explicit scope configuration.

#### Scenario: Package application preview
- **WHEN** setup is run for an application with contexts in packages and src
- **THEN** it previews discovered candidates and configurable scope without treating package roots as confirmed contexts or writing without explicit application

#### Scenario: Existing handler-only configuration
- **WHEN** the consumer has not configured architecture scope
- **THEN** existing handler validation remains available without mandatory context-role checks

#### Scenario: Invalid scope
- **WHEN** configured paths cannot define an unambiguous analysis scope
- **THEN** configuration diagnostics identify the problem rather than silently excluding affected declarations
