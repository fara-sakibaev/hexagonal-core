<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Port\Inbound\CommandBusInterface;
use Hexagonal\Application\Port\Inbound\QueryBusInterface;
use Hexagonal\Application\Port\Outbound\RepositoryInterface;
use Hexagonal\Domain\AggregateRoot;
use Hexagonal\Type\InternalIdInterface;

final class FixtureInternalId implements InternalIdInterface
{
    public function __toString(): string
    {
        return 'id';
    }
}

class OrderAggregate extends AggregateRoot
{
    public InternalIdInterface $id { get => new FixtureInternalId(); }
}
final class PremiumOrderAggregate extends OrderAggregate
{
}

/** @extends RepositoryInterface<OrderAggregate> */
interface OrderRepository extends RepositoryInterface
{
    public function save(OrderAggregate $order): void;

    public function savePremium(PremiumOrderAggregate $order): void;

    /** @param array<string, scalar> $order */
    public function import(array $order): void;

    public function find(string $id): ?OrderAggregate;
}

interface ValidConsumerBus extends CommandBusInterface
{
    #[Override]
    public function dispatch(Command $command, ?int $priority = null): void;
}

interface InvalidConsumerBus extends QueryBusInterface
{
    /** @param array<string, scalar> $fields */
    #[Override]
    public function ask(array $fields): mixed;
}

interface UnrelatedContract
{
    /** @param array<string, scalar> $fields */
    public function search(array $fields): mixed;
}

interface ScalarIndependentQueryBus extends CommandBusInterface
{
    #[Override]
    public function dispatch(Command $command, ?string $tenant = null): void;
}
