<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\ViolationList;
use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\Type;

use function count;
use function strtolower;

final class HandlerCheck implements AfterClassLikeAnalysisInterface
{
    #[Override]
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
            ValidatorInterface::class => ['TInput', true],
            RuleInterface::class => ['TInput', true],
        ] as $interface => [$inputName, $hasResult]) {
            $types = $templates[strtolower($interface)] ?? $templates[$interface] ?? null;
            if (null === $types) {
                continue;
            }
            $input = $types[$inputName] ?? null;
            $validation = ValidatorInterface::class === $interface || RuleInterface::class === $interface;
            $result = $validation ? Type::parseString(ViolationList::class) : ($types['TResult'] ?? null);
            $methodName = $validation ? 'validate' : 'handle';
            $method = $storage->methods[$methodName] ?? null;
            $message = null;
            if (null === $method || 1 !== $method->visibility) {
                $message = 'Must declare public '.$methodName.'() for '.$interface;
            } elseif (1 !== count($method->params) || null === $method->params[0]->type || null === $input
                || !UnionTypeComparator::isContainedBy($event->getCodebase(), $input, $method->params[0]->type)
                || ($validation && !UnionTypeComparator::isContainedBy($event->getCodebase(), $method->params[0]->type, $input))) {
                $message = $methodName.'() must accept declared '.$inputName.' for '.$interface;
            } elseif ($hasResult && (null === $result || null === $method->return_type
                || !UnionTypeComparator::isContainedBy($event->getCodebase(), $method->return_type, $result)
                || ($validation && !UnionTypeComparator::isContainedBy($event->getCodebase(), $result, $method->return_type)))) {
                $message = $methodName.'() must return '.($validation ? ViolationList::class : 'declared TResult').' for '.$interface;
            } elseif (!$hasResult && (null === $method->return_type || !$method->return_type->isVoid())) {
                $message = 'handle() must return void for '.$interface;
            }
            if (null !== $message) {
                IssueBuffer::maybeAdd(new HandlerIssue($message, new CodeLocation($event->getStatementsSource(), $event->getStmt())));
            }
        }

        return null;
    }
}
