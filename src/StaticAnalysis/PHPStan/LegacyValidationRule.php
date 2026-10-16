<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\Domain\PolicyInterface;
use Hexagonal\Validation\ValidatorInterface;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function str_starts_with;
use function strtolower;

/** @implements Rule<Node\Stmt\Class_> */
final class LegacyValidationRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $node->name) {
            return [];
        }
        $name = (null !== $scope->getNamespace() ? $scope->getNamespace().'\\' : '').$node->name->toString();
        $class = $this->reflectionProvider->getClass($name);
        if ($class->isSubclassOf(ValidatorInterface::class) || $class->isSubclassOf(PolicyInterface::class)) {
            return [];
        }
        $errors = [];
        foreach ($node->getMethods() as $method) {
            $methodName = $method->name->toString();
            if (!str_starts_with(strtolower($methodName), 'validate')) {
                continue;
            }
            $required = false;
            foreach ($class->getInterfaces() as $interface) {
                if ($interface->hasMethod($methodName)) {
                    $required = true;
                    break;
                }
            }
            if (!$required) {
                $parent = $class->getParentClass();
                while (null !== $parent) {
                    if ($parent->hasNativeMethod($methodName) && $parent->getNativeMethod($methodName)->isAbstract()) {
                        $required = true;
                        break;
                    }
                    $parent = $parent->getParentClass();
                }
            }
            if (!$required) {
                $errors[] = RuleErrorBuilder::message($name.'::'.$methodName.'() is a legacy validation method; extract a named validator or domain policy.')->identifier('hexagonal.legacyValidation')->line($method->getStartLine())->build();
            }
        }

        return $errors;
    }
}
