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
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

use function array_map;
use function count;

/** @implements Rule<Node\Stmt\Class_> */
final readonly class HandlerRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $node->name || $node->isAbstract()) {
            return [];
        }
        $name = (null !== $scope->getNamespace() ? $scope->getNamespace().'\\' : '').$node->name->toString();
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
            if (null === $ancestor) {
                continue;
            }
            $templates = $ancestor->getActiveTemplateTypeMap();
            $input = $templates->getType($inputTemplate);
            $validation = ValidatorInterface::class === $interface || RuleInterface::class === $interface;
            $result = $validation ? new ObjectType(ViolationList::class) : ($hasResult ? $templates->getType('TResult') : null);
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
            if (1 !== count($parameters) || null === $input || ($validation
                ? !$parameters[0]->getType()->equals($input)
                : !$parameters[0]->getType()->isSuperTypeOf($input)->yes())) {
                $errors[] = $label.' '.$methodName.'() must accept its declared '.$inputTemplate.' type.';
            }
            if ($hasResult && (null === $result || ($validation
                ? !$variant->getReturnType()->equals($result)
                : !$result->isSuperTypeOf($variant->getReturnType())->yes()))) {
                $errors[] = $label.' '.$methodName.'() must return '.($validation ? ViolationList::class : 'its declared TResult type').'.';
            }
            if (!$hasResult && !$variant->getReturnType()->isVoid()->yes()) {
                $errors[] = $label.' handle() must return void.';
            }
        }

        return array_map(static fn (string $message) => RuleErrorBuilder::message($message)->identifier('hexagonal.handler')->build(), $errors);
    }
}
