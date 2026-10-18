## Why

Consumer enums currently have no explicit architectural role. Four public marker contracts let the analyzer distinguish domain values from port and infrastructure configuration enums and enforce their layer ownership.

## What Changes

- Add `DomainEnumInterface`, `PortConfigParameterInterface`, `InfrastructureConfigParameterInterface`, and `AdapterConfigParameterInterface` marker contracts extending `UnitEnum`.
- Classify the markers as roles, with the adapter role specializing infrastructure configuration.
- Permit Domain enums to be referenced by Application DTOs, commands, and queries without changing the enum declaration's Domain role.
- Add PHPStan/Psalm fixtures and public usage documentation.

## Capabilities

### New Capabilities
- `enum-architecture-roles`: Public enum marker contracts and their analyzer behavior.

### Modified Capabilities
- `context-role-classification`: Extend role classification and layer compatibility for enum roles.

## Impact

Public PHP interfaces under `src/`; shared role mapping and layer policy; PHPStan/Psalm architecture fixtures; OpenSpec and architecture documentation. Requires PHP 8.5, already required by the package.
