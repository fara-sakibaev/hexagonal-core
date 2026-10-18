<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use function array_fill_keys;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;
use function ltrim;
use function strtolower;

final readonly class RoleMapper
{
    /** @return list<AbstractionRoleInterface> */
    public function catalog(): array
    {
        return [...ApplicationAbstractionRole::cases(), ...DomainAbstractionRole::cases(), ...EnumAbstractionRole::cases(), ...TypeAbstractionRole::cases(), ...ValidationAbstractionRole::cases()];
    }

    /**
     * Ancestry comes from the analyzer, never runtime autoload.
     *
     * @param list<string> $ancestors
     */
    public function resolve(string $name, array $ancestors): RoleResolution
    {
        $names = array_fill_keys(array_map(static fn (string $name): string => strtolower(ltrim($name, '\\')), [$name, ...$ancestors]), true);
        $roles = array_values(array_filter($this->catalog(), static fn (AbstractionRoleInterface $role): bool => isset($names[strtolower($role->contract())])));
        if (in_array(TypeAbstractionRole::InternalId, $roles, true) || in_array(TypeAbstractionRole::ExternalId, $roles, true)) {
            $roles = array_values(array_filter($roles, static fn (AbstractionRoleInterface $role): bool => TypeAbstractionRole::Id !== $role));
        }
        if (in_array(EnumAbstractionRole::AdapterConfigParameter, $roles, true)) {
            $roles = array_values(array_filter($roles, static fn (AbstractionRoleInterface $role): bool => EnumAbstractionRole::InfrastructureConfigParameter !== $role));
        }

        return new RoleResolution($roles);
    }
}
