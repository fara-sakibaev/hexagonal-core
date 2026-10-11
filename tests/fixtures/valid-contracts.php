<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEvent;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Port\Inbound\QueryBusInterface;
use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Hexagonal\Domain\DomainEvent;

final readonly class ExampleResult extends DTO {}
/** @extends Query<ExampleResult> */
final readonly class ExampleQuery extends Query {}
final readonly class ExampleCommand extends Command {}
final readonly class ExampleDomainEvent extends DomainEvent {}
final readonly class ExampleIntegrationEvent extends IntegrationEvent {}

/** @implements CommandHandlerInterface<ExampleCommand> */
final class ValidCommandHandler implements CommandHandlerInterface
{
    public function handle(ExampleCommand $command): void {}
}

/** @implements QueryHandlerInterface<ExampleResult, ExampleQuery> */
final class ValidQueryHandler implements QueryHandlerInterface
{
    public function handle(ExampleQuery $query): ExampleResult { return new ExampleResult(); }
}

/** @implements DomainEventHandlerInterface<ExampleDomainEvent> */
final class ValidDomainEventHandler implements DomainEventHandlerInterface
{
    public function handle(ExampleDomainEvent $event): void {}
}

/** @implements IntegrationEventHandlerInterface<ExampleIntegrationEvent> */
final class ValidIntegrationEventHandler implements IntegrationEventHandlerInterface
{
    public function handle(ExampleIntegrationEvent $event): void {}
}

/** @extends ReadPortInterface<ExampleQuery> */
interface ValidReadPort extends ReadPortInterface {}

function inferredResult(QueryBusInterface $bus): ExampleResult
{
    return $bus->ask(new ExampleQuery());
}
