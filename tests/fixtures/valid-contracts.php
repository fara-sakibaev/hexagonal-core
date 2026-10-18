<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEvent;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Port\Inbound\QueryBusInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Hexagonal\Domain\DomainEvent;
use Hexagonal\Enum\AdapterConfigParameterInterface;
use Hexagonal\Enum\DomainEnumInterface;
use Hexagonal\Enum\InfrastructureConfigParameterInterface;
use Hexagonal\Enum\PortConfigParameterInterface;

final readonly class ExampleResult extends DTO
{
}
/** @extends Query<ExampleResult> */
final readonly class ExampleQuery extends Query
{
}
final readonly class ExampleCommand extends Command
{
}
final readonly class ExampleDomainEvent extends DomainEvent
{
}
final readonly class ExampleIntegrationEvent extends IntegrationEvent
{
}

enum ExampleDomainStatus: string implements DomainEnumInterface
{
    case Active = 'active';
}

enum ExamplePortMode implements PortConfigParameterInterface
{
    case Sync;
}

enum ExampleInfrastructureMode implements InfrastructureConfigParameterInterface
{
    case Local;
}

enum ExampleAdapterMode implements AdapterConfigParameterInterface
{
    case Fast;
}

final readonly class ExampleDomainEnumCommand extends Command
{
    public function __construct(public ExampleDomainStatus $status)
    {
    }
}

/** @implements CommandHandlerInterface<ExampleCommand> */
final class ValidCommandHandler implements CommandHandlerInterface
{
    public function handle(ExampleCommand $command): void
    {
    }
}

/** @implements QueryHandlerInterface<ExampleResult, ExampleQuery> */
final class ValidQueryHandler implements QueryHandlerInterface
{
    public function handle(ExampleQuery $query): ExampleResult
    {
        return new ExampleResult();
    }
}

/** @implements DomainEventHandlerInterface<ExampleDomainEvent> */
final class ValidDomainEventHandler implements DomainEventHandlerInterface
{
    public function handle(ExampleDomainEvent $event): void
    {
    }
}

/** @implements IntegrationEventHandlerInterface<ExampleIntegrationEvent> */
final class ValidIntegrationEventHandler implements IntegrationEventHandlerInterface
{
    public function handle(ExampleIntegrationEvent $event): void
    {
    }
}

function inferredResult(QueryBusInterface $bus): ExampleResult
{
    return $bus->ask(new ExampleQuery());
}
