<?php

declare(strict_types=1);

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
use Hexagonal\Domain\AggregateRoot;
use Hexagonal\Domain\DomainEvent;
use Hexagonal\Domain\PolicyInterface;
use Hexagonal\Domain\ValueObject;
use Hexagonal\StaticAnalysis\AbstractionRole\ApplicationAbstractionRole;
use Hexagonal\StaticAnalysis\AbstractionRole\DomainAbstractionRole;
use Hexagonal\StaticAnalysis\AbstractionRole\Layer;
use Hexagonal\StaticAnalysis\AbstractionRole\RoleMapper;
use Hexagonal\StaticAnalysis\AbstractionRole\RoleResolution;
use Hexagonal\StaticAnalysis\AbstractionRole\TypeAbstractionRole;
use Hexagonal\StaticAnalysis\AbstractionRole\ValidationAbstractionRole;
use Hexagonal\Type\ExternalIdInterface;
use Hexagonal\Type\ExternalUid;
use Hexagonal\Type\IdInterface;
use Hexagonal\Type\InternalIdInterface;
use Hexagonal\Type\InternalUid;
use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidationGuard;
use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\Violation;
use Hexagonal\Validation\ViolationCode;
use Hexagonal\Validation\ViolationList;
use Hexagonal\Validation\ViolationPath;

require dirname(__DIR__).'/vendor/autoload.php';

$roles = [
    [Command::class, ApplicationAbstractionRole::Command],
    [CommandHandlerInterface::class, ApplicationAbstractionRole::CommandHandler],
    [DTO::class, ApplicationAbstractionRole::DTO],
    [DomainEventHandlerInterface::class, ApplicationAbstractionRole::DomainEventHandler],
    [IntegrationEvent::class, ApplicationAbstractionRole::IntegrationEvent],
    [IntegrationEventHandlerInterface::class, ApplicationAbstractionRole::IntegrationEventHandler],
    [EventIdFactoryInterface::class, ApplicationAbstractionRole::EventIdFactory],
    [EventPublisherInterface::class, ApplicationAbstractionRole::EventPublisher],
    [OutboxCoordinator::class, ApplicationAbstractionRole::OutboxCoordinator],
    [OutboxInterface::class, ApplicationAbstractionRole::Outbox],
    [OutboxRecord::class, ApplicationAbstractionRole::OutboxRecord],
    [OutboxRelay::class, ApplicationAbstractionRole::OutboxRelay],
    [TransactionInterface::class, ApplicationAbstractionRole::Transaction],
    [CommandBusInterface::class, ApplicationAbstractionRole::CommandBus],
    [QueryBusInterface::class, ApplicationAbstractionRole::QueryBus],
    [DomainEventPublisherInterface::class, ApplicationAbstractionRole::DomainEventPublisher],
    [IntegrationEventPublisherInterface::class, ApplicationAbstractionRole::IntegrationEventPublisher],
    [ReadPortInterface::class, ApplicationAbstractionRole::ReadPort],
    [RepositoryInterface::class, ApplicationAbstractionRole::Repository],
    [Query::class, ApplicationAbstractionRole::Query],
    [QueryHandlerInterface::class, ApplicationAbstractionRole::QueryHandler],
    [AggregateRoot::class, DomainAbstractionRole::AggregateRoot],
    [DomainEvent::class, DomainAbstractionRole::DomainEvent],
    [PolicyInterface::class, DomainAbstractionRole::Policy],
    [ValueObject::class, DomainAbstractionRole::ValueObject],
    [ExternalIdInterface::class, TypeAbstractionRole::ExternalId],
    [ExternalUid::class, TypeAbstractionRole::ExternalId],
    [IdInterface::class, TypeAbstractionRole::Id],
    [InternalIdInterface::class, TypeAbstractionRole::InternalId],
    [InternalUid::class, TypeAbstractionRole::InternalId],
    [RuleInterface::class, ValidationAbstractionRole::Rule],
    [ValidationGuard::class, ValidationAbstractionRole::ValidationGuard],
    [ValidationInputInterface::class, ValidationAbstractionRole::ValidationInput],
    [ValidatorInterface::class, ValidationAbstractionRole::Validator],
    [Violation::class, ValidationAbstractionRole::Violation],
    [ViolationCode::class, ValidationAbstractionRole::ViolationCode],
    [ViolationList::class, ValidationAbstractionRole::ViolationList],
    [ViolationPath::class, ValidationAbstractionRole::ViolationPath],
];

$mapper = new RoleMapper();
foreach ($roles as [$class, $role]) {
    $reflection = new ReflectionClass($class);
    $ancestors = $reflection->getInterfaceNames();
    for ($parent = $reflection->getParentClass(); false !== $parent; $parent = $parent->getParentClass()) {
        $ancestors[] = $parent->getName();
    }
    assert($role === $mapper->resolve($class, $ancestors)->role(), $class);
}
assert(count($mapper->catalog()) === count($roles) - 2);

// Source-only names must never trigger autoload.
$autoloaded = [];
$loader = static function (string $name) use (&$autoloaded): void { $autoloaded[] = $name; };
spl_autoload_register($loader);
assert(ApplicationAbstractionRole::Command === $mapper->resolve('Consumer\\SourceOnly', [Command::class])->role());
assert(ApplicationAbstractionRole::Command === $mapper->resolve('Consumer\\Child', ['Consumer\\Parent', Command::class, Command::class])->role());
assert(ApplicationAbstractionRole::Command === $mapper->resolve('Consumer\\Lower', [strtolower(Command::class)])->role());
assert([] === $mapper->resolve('Consumer\\Unknown', [])->roles);
assert([] === $autoloaded);
spl_autoload_unregister($loader);

$ambiguous = $mapper->resolve('Consumer\\Both', [Command::class, ValidationInputInterface::class]);
assert(2 === count($ambiguous->roles));
assert(null === $ambiguous->role());
$internal = $mapper->resolve('Consumer\\Internal', [InternalIdInterface::class, IdInterface::class]);
assert([TypeAbstractionRole::InternalId] === $internal->roles);
$external = $mapper->resolve('Consumer\\External', [ExternalIdInterface::class, IdInterface::class]);
assert([TypeAbstractionRole::ExternalId] === $external->roles);
$conflict = $mapper->resolve('Consumer\\ConflictingId', [InternalIdInterface::class, ExternalIdInterface::class, IdInterface::class]);
assert(2 === count($conflict->roles));
assert(!in_array(TypeAbstractionRole::Id, $conflict->roles, true));
assert(TypeAbstractionRole::Id === $mapper->resolve('Consumer\\Id', [IdInterface::class])->role());

$ports = ['CommandBus', 'QueryBus', 'DomainEventPublisher', 'IntegrationEventPublisher', 'ReadPort', 'Repository', 'EventIdFactory', 'EventPublisher', 'Outbox', 'Transaction'];
foreach ($mapper->catalog() as $role) {
    $resolution = new RoleResolution([$role]);
    $application = $role instanceof ApplicationAbstractionRole || $role instanceof TypeAbstractionRole || $role instanceof ValidationAbstractionRole;
    $domain = $role instanceof DomainAbstractionRole || $role instanceof TypeAbstractionRole;
    $infrastructure = $role instanceof TypeAbstractionRole || $role instanceof ValidationAbstractionRole || ($role instanceof ApplicationAbstractionRole && in_array($role->name, $ports, true));
    foreach (['Application' => $application, 'Domain' => $domain, 'Infrastructure' => $infrastructure] as $layer => $allowed) {
        $policy = Layer::from($layer);
        assert($allowed === $policy->allows($role), $layer.': '.$role->name);
        assert(($allowed ? null : 'hexagonal.incompatibleLayer') === $policy->violation($resolution));
    }
}
foreach (Layer::cases() as $layer) {
    assert('hexagonal.ambiguousRole' === $layer->violation($ambiguous));
    assert('hexagonal.ambiguousRole' === $layer->violation($conflict));
    assert(('Infrastructure' === $layer->value ? null : 'hexagonal.unknownRole') === $layer->violation(new RoleResolution([])));
}
echo "Abstraction role tests passed.\n";
