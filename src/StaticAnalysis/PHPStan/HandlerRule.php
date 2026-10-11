<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Query\QueryHandlerInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/** @implements Rule<Node\Stmt\Class_> */
final class HandlerRule implements Rule
{
    public function __construct(private \PHPStan\Reflection\ReflectionProvider $reflectionProvider)
    {
    }

    #[\Override]
    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    #[\Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null || $node->isAbstract()) {
            return [];
        }
        $name = ($scope->getNamespace() !== null ? $scope->getNamespace().'\\' : '').$node->name->toString();
        $class = $this->reflectionProvider->getClass($name);

        $errors = [];
        foreach ([
            CommandHandlerInterface::class => ['TCommand', false],
            QueryHandlerInterface::class => ['TQuery', true],
            DomainEventHandlerInterface::class => ['TEvent', false],
            IntegrationEventHandlerInterface::class => ['TEvent', false],
        ] as $interface => [$inputTemplate, $hasResult]) {
            $ancestor = $class->getAncestorWithClassName($interface);
            if ($ancestor === null) {
                continue;
            }
            $templates = $ancestor->getActiveTemplateTypeMap();
            $input = $templates->getType($inputTemplate);
            $result = $hasResult ? $templates->getType('TResult') : null;
            $label = $class->getName().' implements '.$interface;
            if (!$class->hasMethod('handle')) {
                $errors[] = $label.' must declare public handle().';
                continue;
            }
            $method = $class->getMethod('handle', $scope);
            if (!$method->isPublic()) {
                $errors[] = $label.' must expose public handle().';
                continue;
            }
            $variant = $method->getVariants()[0];
            $parameters = $variant->getParameters();
            if (count($parameters) !== 1 || $input === null || !$parameters[0]->getType()->isSuperTypeOf($input)->yes()) {
                $errors[] = $label.' handle() must accept its declared '.$inputTemplate.' type.';
            }
            if ($hasResult && ($result === null || !$result->isSuperTypeOf($variant->getReturnType())->yes())) {
                $errors[] = $label.' handle() must return its declared TResult type.';
            }
            if (!$hasResult && !$variant->getReturnType()->isVoid()->yes()) {
                $errors[] = $label.' handle() must return void.';
            }
        }
        return array_map(static fn (string $message) => \PHPStan\Rules\RuleErrorBuilder::message($message)->identifier('hexagonal.handler')->build(), $errors);
    }
}
