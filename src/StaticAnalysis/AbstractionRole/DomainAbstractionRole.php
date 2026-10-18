<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use Hexagonal\Domain\AggregateRoot;
use Hexagonal\Domain\DomainEvent;
use Hexagonal\Domain\PolicyInterface;
use Hexagonal\Domain\ValueObject;
use Override;

enum DomainAbstractionRole implements AbstractionRoleInterface
{
    case AggregateRoot;
    case DomainEvent;
    case Policy;
    case ValueObject;

    /** @return class-string */
    #[Override]
    public function contract(): string
    {
        return match ($this) {
            self::AggregateRoot => AggregateRoot::class,
            self::DomainEvent => DomainEvent::class,
            self::Policy => PolicyInterface::class,
            self::ValueObject => ValueObject::class,
        };
    }
}
