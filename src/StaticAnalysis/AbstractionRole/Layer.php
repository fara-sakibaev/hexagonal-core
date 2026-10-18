<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use function count;
use function in_array;

enum Layer: string
{
    case Application = 'Application';
    case Domain = 'Domain';
    case Infrastructure = 'Infrastructure';

    public function allows(AbstractionRoleInterface $role): bool
    {
        if ($role instanceof TypeAbstractionRole) {
            return true;
        }

        return match ($this) {
            self::Application => $role instanceof ApplicationAbstractionRole || $role instanceof ValidationAbstractionRole,
            self::Domain => $role instanceof DomainAbstractionRole,
            self::Infrastructure => $role instanceof ValidationAbstractionRole || in_array($role, [
                ApplicationAbstractionRole::CommandBus,
                ApplicationAbstractionRole::QueryBus,
                ApplicationAbstractionRole::DomainEventPublisher,
                ApplicationAbstractionRole::IntegrationEventPublisher,
                ApplicationAbstractionRole::ReadPort,
                ApplicationAbstractionRole::Repository,
                ApplicationAbstractionRole::EventIdFactory,
                ApplicationAbstractionRole::EventPublisher,
                ApplicationAbstractionRole::Outbox,
                ApplicationAbstractionRole::Transaction,
            ], true),
        };
    }

    public function violation(RoleResolution $resolution): ?string
    {
        if (count($resolution->roles) > 1) {
            return 'hexagonal.ambiguousRole';
        }
        $role = $resolution->role();
        if (null === $role) {
            return self::Infrastructure === $this ? null : 'hexagonal.unknownRole';
        }

        return $this->allows($role) ? null : 'hexagonal.incompatibleLayer';
    }
}
