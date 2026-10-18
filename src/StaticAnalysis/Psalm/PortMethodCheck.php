<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Event\IntegrationEvent;
use Hexagonal\Application\Port\Inbound\CommandBusInterface;
use Hexagonal\Application\Port\Inbound\QueryBusInterface;
use Hexagonal\Application\Port\Outbound\DomainEventPublisherInterface;
use Hexagonal\Application\Port\Outbound\IntegrationEventPublisherInterface;
use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\Application\Port\Outbound\RepositoryInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Domain\AggregateRoot;
use Hexagonal\Domain\DomainEvent;
use Override;
use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\Type\Atomic\TNamedObject;

use function array_intersect;
use function array_map;
use function in_array;
use function strtolower;

final class PortMethodCheck implements AfterClassLikeAnalysisInterface
{
    private const PORTS = [CommandBusInterface::class, QueryBusInterface::class, DomainEventPublisherInterface::class, IntegrationEventPublisherInterface::class, RepositoryInterface::class, ReadPortInterface::class];

    #[Override]
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        $storage = $event->getClasslikeStorage();
        $implemented = array_map(strtolower(...), [...$storage->parent_interfaces, ...$storage->class_implements]);
        $templates = $storage->template_extended_params ?? [];
        foreach ([RepositoryInterface::class => ['TAggregate', AggregateRoot::class], ReadPortInterface::class => ['TInput', Query::class]] as $port => [$template, $base]) {
            $binding = $templates[strtolower($port)] ?? $templates[$port] ?? null;
            $expected = $binding[$template] ?? null;
            if (null === $expected) {
                continue;
            }
            foreach ($storage->methods as $method) {
                foreach ($method->params as $parameter) {
                    if (null === $parameter->type || $parameter->type->isMixed()) {
                        continue;
                    }
                    $valid = $parameter->type->isObjectType();
                    if ($valid) {
                        foreach ($parameter->type->getAtomicTypes() as $actual) {
                            if (!$actual instanceof TNamedObject || !$event->getCodebase()->classExtendsOrImplements($actual->getId(), $base)) {
                                $valid = false;
                                break;
                            }
                            foreach ($expected->getAtomicTypes() as $model) {
                                if ($model instanceof TNamedObject && !$event->getCodebase()->classExtendsOrImplements($actual->getId(), $model->getId())) {
                                    $valid = false;
                                }
                            }
                        }
                    }
                    if (!$valid) {
                        IssueBuffer::maybeAdd(new PortMethodIssue($storage->name.'::'.$method->cased_name.'() parameter $'.$parameter->name.' must use '.$template.' model '.$expected->getId().'.', new CodeLocation($event->getStatementsSource(), $event->getStmt())));
                    }
                }
            }
        }
        if ([] === array_intersect(array_map(strtolower(...), self::PORTS), $implemented)) {
            return null;
        }
        foreach ($storage->methods as $name => $method) {
            $expected = match ($name) {
                'ask' => Query::class, 'dispatch' => Command::class,
                'publish' => in_array(strtolower(DomainEventPublisherInterface::class), $implemented, true) ? DomainEvent::class : IntegrationEvent::class,
                default => null,
            };
            if (null === $expected) {
                continue;
            }
            foreach ($method->params as $index => $parameter) {
                if (0 !== $index) {
                    continue;
                }
                if (null === $parameter->type || $parameter->type->isMixed()) {
                    continue;
                }
                if (!$parameter->type->isObjectType() || !$event->getCodebase()->classExtendsOrImplements($parameter->type->getId(), $expected)) {
                    IssueBuffer::maybeAdd(new PortMethodIssue($storage->name.'::'.$name.'() parameter $'.$parameter->name.' must use '.$expected.' or a subtype.', new CodeLocation($event->getStatementsSource(), $event->getStmt())));
                }
            }
        }

        return null;
    }
}
