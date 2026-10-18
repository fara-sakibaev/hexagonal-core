<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEvent;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Event\Outbox\EventIdFactoryInterface;
use Hexagonal\Application\Event\Outbox\EventPublisherInterface;
use Hexagonal\Application\Event\Outbox\OutboxCoordinator;
use Hexagonal\Application\Event\Outbox\OutboxInterface;
use Hexagonal\Application\Event\Outbox\OutboxRecord;
use Hexagonal\Application\Event\Outbox\OutboxRelay;
use Hexagonal\Application\Event\Outbox\TransactionInterface;
use Hexagonal\Application\Port\Inbound\CommandBusInterface;
use Hexagonal\Application\Port\Inbound\QueryBusInterface;
use Hexagonal\Application\Port\Outbound\DomainEventPublisherInterface;
use Hexagonal\Application\Port\Outbound\IntegrationEventPublisherInterface;
use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\Application\Port\Outbound\RepositoryInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Override;

enum ApplicationAbstractionRole implements AbstractionRoleInterface
{
    case Command;
    case CommandHandler;
    case DTO;
    case DomainEventHandler;
    case IntegrationEvent;
    case IntegrationEventHandler;
    case EventIdFactory;
    case EventPublisher;
    case OutboxCoordinator;
    case Outbox;
    case OutboxRecord;
    case OutboxRelay;
    case Transaction;
    case CommandBus;
    case QueryBus;
    case DomainEventPublisher;
    case IntegrationEventPublisher;
    case ReadPort;
    case Repository;
    case Query;
    case QueryHandler;

    /** @return class-string */
    #[Override]
    public function contract(): string
    {
        return match ($this) {
            self::Command => Command::class,
            self::CommandHandler => CommandHandlerInterface::class,
            self::DTO => DTO::class,
            self::DomainEventHandler => DomainEventHandlerInterface::class,
            self::IntegrationEvent => IntegrationEvent::class,
            self::IntegrationEventHandler => IntegrationEventHandlerInterface::class,
            self::EventIdFactory => EventIdFactoryInterface::class,
            self::EventPublisher => EventPublisherInterface::class,
            self::OutboxCoordinator => OutboxCoordinator::class,
            self::Outbox => OutboxInterface::class,
            self::OutboxRecord => OutboxRecord::class,
            self::OutboxRelay => OutboxRelay::class,
            self::Transaction => TransactionInterface::class,
            self::CommandBus => CommandBusInterface::class,
            self::QueryBus => QueryBusInterface::class,
            self::DomainEventPublisher => DomainEventPublisherInterface::class,
            self::IntegrationEventPublisher => IntegrationEventPublisherInterface::class,
            self::ReadPort => ReadPortInterface::class,
            self::Repository => RepositoryInterface::class,
            self::Query => Query::class,
            self::QueryHandler => QueryHandlerInterface::class,
        };
    }
}
