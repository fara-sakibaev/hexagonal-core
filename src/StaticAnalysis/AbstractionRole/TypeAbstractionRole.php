<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use Hexagonal\Type\ExternalIdInterface;
use Hexagonal\Type\IdInterface;
use Hexagonal\Type\InternalIdInterface;
use Override;

enum TypeAbstractionRole implements AbstractionRoleInterface
{
    case ExternalId;
    case InternalId;
    case Id;

    /** @return class-string */
    #[Override]
    public function contract(): string
    {
        return match ($this) {
            self::ExternalId => ExternalIdInterface::class,
            self::InternalId => InternalIdInterface::class,
            self::Id => IdInterface::class,
        };
    }
}
