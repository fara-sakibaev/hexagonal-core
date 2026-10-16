<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEvent;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Hexagonal\Domain\DomainEvent;

final readonly class BadCommand extends Command
{
}
final readonly class BadResult extends DTO
{
}
/** @extends Query<BadResult> */
final readonly class BadQuery extends Query
{
}
final readonly class BadDomainEvent extends DomainEvent
{
}
final readonly class BadIntegrationEvent extends IntegrationEvent
{
}

/** @implements CommandHandlerInterface<BadCommand> */
final class MissingCommandHandler implements CommandHandlerInterface
{
}

/** @implements QueryHandlerInterface<BadResult, BadQuery> */
final class WrongQueryHandler implements QueryHandlerInterface
{
    public function handle(BadQuery $query): string
    {
        return '';
    }
}

/** @implements DomainEventHandlerInterface<BadDomainEvent> */
final class WrongDomainEventHandler implements DomainEventHandlerInterface
{
    public function handle(BadQuery $event): void
    {
    }
}

/** @implements IntegrationEventHandlerInterface<BadIntegrationEvent> */
final class MissingIntegrationEventHandler implements IntegrationEventHandlerInterface
{
}
