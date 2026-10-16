<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\Event\DomainEventHandlerInterface;
use Hexagonal\Application\Event\IntegrationEventHandlerInterface;
use Hexagonal\Application\Query\QueryHandlerInterface;
use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\ViolationList;
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
            ValidatorInterface::class => ['TInput', true],
            RuleInterface::class => ['TInput', true],
        ] as $interface => [$inputTemplate, $hasResult]) {
            $ancestor = $class->getAncestorWithClassName($interface);
            if ($ancestor === null) {
                continue;
            }
            $templates = $ancestor->getActiveTemplateTypeMap();
            $input = $templates->getType($inputTemplate);
            $validation = $interface === ValidatorInterface::class || $interface === RuleInterface::class;
            $result = $validation ? new \PHPStan\Type\ObjectType(ViolationList::class) : ($hasResult ? $templates->getType('TResult') : null);
            $methodName = $validation ? 'validate' : 'handle';
            $label = $class->getName().' implements '.$interface;
            if (!$class->hasMethod($methodName)) {
                $errors[] = $label.' must declare public '.$methodName.'().';
                continue;
            }
            $method = $class->getMethod($methodName, $scope);
            if (!$method->isPublic()) {
                $errors[] = $label.' must expose public '.$methodName.'().';
                continue;
            }
            $variant = $method->getVariants()[0];
            $parameters = $variant->getParameters();
            if (count($parameters) !== 1 || $input === null || ($validation
                ? !$parameters[0]->getType()->equals($input)
                : !$parameters[0]->getType()->isSuperTypeOf($input)->yes())) {
                $errors[] = $label.' '.$methodName.'() must accept its declared '.$inputTemplate.' type.';
            }
            if ($hasResult && ($result === null || ($validation
                ? !$variant->getReturnType()->equals($result)
                : !$result->isSuperTypeOf($variant->getReturnType())->yes()))) {
                $errors[] = $label.' '.$methodName.'() must return '.($validation ? ViolationList::class : 'its declared TResult type').'.';
            }
            if (!$hasResult && !$variant->getReturnType()->isVoid()->yes()) {
                $errors[] = $label.' handle() must return void.';
            }
        }
        return array_map(static fn (string $message) => \PHPStan\Rules\RuleErrorBuilder::message($message)->identifier('hexagonal.handler')->build(), $errors);
    }
}
