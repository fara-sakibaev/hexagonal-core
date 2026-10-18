<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use function count;

final readonly class RoleResolution
{
    /** @param list<AbstractionRoleInterface> $roles */
    public function __construct(public array $roles)
    {
    }

    public function role(): ?AbstractionRoleInterface
    {
        return 1 === count($this->roles) ? $this->roles[0] : null;
    }
}
