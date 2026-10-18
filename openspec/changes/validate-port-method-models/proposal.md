## Why

Ports define application boundaries, but current checks do not consistently reject public method parameters that carry arbitrary field lists instead of the library's typed models. This permits weak contracts that lose domain meaning and bypass the package's model boundaries.

## What Changes

- Add PHPStan and Psalm checks for public methods of library ports and consumer interfaces extending those ports.
- Require model parameters to use the appropriate library model or its subtype; reject arbitrary field collections while allowing meaningful scalar parameters.

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- `static-analysis-setup`: analyzer integrations validate typed public port method parameters.

## Impact

PHPStan and Psalm integrations, analyzer fixtures and documentation. No runtime API or dependency changes intended.
