<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use Hexagonal\Enum\AdapterConfigParameterInterface;
use Hexagonal\Enum\DomainEnumInterface;
use Hexagonal\Enum\InfrastructureConfigParameterInterface;
use Hexagonal\Enum\PortConfigParameterInterface;
use Override;

enum EnumAbstractionRole implements AbstractionRoleInterface
{
    case DomainEnum;
    case PortConfigParameter;
    case InfrastructureConfigParameter;
    case AdapterConfigParameter;

    /** @return class-string */
    #[Override]
    public function contract(): string
    {
        return match ($this) {
            self::DomainEnum => DomainEnumInterface::class,
            self::PortConfigParameter => PortConfigParameterInterface::class,
            self::InfrastructureConfigParameter => InfrastructureConfigParameterInterface::class,
            self::AdapterConfigParameter => AdapterConfigParameterInterface::class,
        };
    }
}
