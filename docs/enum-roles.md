# Enum roles

Use marker interfaces from `Hexagonal\Enum` to declare the architectural owner of an enum:

| Contract | Layer | Use |
| --- | --- | --- |
| `DomainEnumInterface` | Domain | Stable business concepts such as status or category |
| `PortConfigParameterInterface` | Application | Enum configuration owned by an application port contract |
| `InfrastructureConfigParameterInterface` | Infrastructure | Infrastructure configuration |
| `AdapterConfigParameterInterface` | Infrastructure | Adapter-specific configuration; specializes infrastructure configuration |

Each marker extends `UnitEnum`, so only PHP enums can implement it. `AdapterConfigParameterInterface` extends `InfrastructureConfigParameterInterface`; the analyzer reports the specialized adapter role once.

Domain enums remain Domain declarations when referenced by Application DTOs, commands, or queries. The reference does not add an Application role to the enum.

These contracts mark architectural ownership only. They do not define configuration loading, parameter names, defaults, serialization, secrets, or runtime metadata.
