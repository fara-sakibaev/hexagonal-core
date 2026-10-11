<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Psalm\CodeLocation;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;

final class HandlerCheck implements AfterClassLikeAnalysisInterface
{
    #[\Override]
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        $storage = $event->getClasslikeStorage();
        if ($storage->abstract) {
            return null;
        }
        $templates = $storage->template_extended_params ?? [];
        foreach ([
            CommandHandlerInterface::class => ['TCommand', false],
            QueryHandlerInterface::class => ['TQuery', true],
            DomainEventHandlerInterface::class => ['TEvent', false],
            IntegrationEventHandlerInterface::class => ['TEvent', false],
        ] as $interface => [$inputName, $hasResult]) {
            $types = $templates[strtolower($interface)] ?? $templates[$interface] ?? null;
            if ($types === null) {
                continue;
            }
            $input = $types[$inputName] ?? null;
            $result = $types['TResult'] ?? null;
            $method = $storage->methods['handle'] ?? null;
            $message = null;
            if ($method === null || $method->visibility !== 1) {
                $message = 'Must declare public handle() for '.$interface;
            } elseif (count($method->params) !== 1 || $method->params[0]->type === null || $input === null
                || !UnionTypeComparator::isContainedBy($event->getCodebase(), $input, $method->params[0]->type)) {
                $message = 'handle() must accept declared '.$inputName.' for '.$interface;
            } elseif ($hasResult && ($result === null || $method->return_type === null
                || !UnionTypeComparator::isContainedBy($event->getCodebase(), $method->return_type, $result))) {
                $message = 'handle() must return declared TResult for '.$interface;
            } elseif (!$hasResult && ($method->return_type === null || !$method->return_type->isVoid())) {
                $message = 'handle() must return void for '.$interface;
            }
            if ($message !== null) {
                IssueBuffer::maybeAdd(new HandlerIssue($message, new CodeLocation($event->getStatementsSource(), $event->getStmt())));
            }
        }
        return null;
    }
}
