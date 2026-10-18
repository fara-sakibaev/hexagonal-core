<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

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
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

use function array_map;
use function in_array;
use function is_string;
use function strtolower;

/** @implements Rule<Node\Stmt\Interface_> */
final readonly class PortMethodRule implements Rule
{
    private const PORTS = [CommandBusInterface::class, QueryBusInterface::class, DomainEventPublisherInterface::class, IntegrationEventPublisherInterface::class, RepositoryInterface::class, ReadPortInterface::class];

    public function __construct(private ReflectionProvider $provider)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Node\Stmt\Interface_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!isset($node->namespacedName)) {
            return [];
        }
        $class = $this->provider->getClass($node->namespacedName->toString());
        $port = false;
        foreach (self::PORTS as $candidate) {
            $port = $port || $class->implementsInterface($candidate);
        }
        if (!$port) {
            return [];
        }
        $errors = [];
        foreach ([RepositoryInterface::class => ['TAggregate', AggregateRoot::class], ReadPortInterface::class => ['TInput', Query::class]] as $genericPort => [$template, $base]) {
            $ancestor = $class->getAncestorWithClassName($genericPort);
            if (null === $ancestor) {
                continue;
            }
            $expected = $ancestor->getActiveTemplateTypeMap()->getType($template);
            if (null === $expected) {
                continue;
            }
            foreach ($node->stmts as $method) {
                if (!$method instanceof Node\Stmt\ClassMethod || !$method->isPublic()) {
                    continue;
                }
                foreach ($method->params as $parameter) {
                    if ($parameter->type instanceof Node\Identifier && in_array(strtolower($parameter->type->toString()), ['array', 'iterable', 'callable'], true)) {
                        $actualName = null;
                    } elseif ($parameter->type instanceof Node\Name) {
                        $actualName = $scope->resolveName($parameter->type);
                    } else {
                        continue;
                    }
                    if (null === $actualName) {
                        $parameterName = $parameter->var instanceof Node\Expr\Variable && is_string($parameter->var->name) ? $parameter->var->name : 'parameter';
                        $errors[] = $class->getName().'::'.$method->name->toString().'() parameter $'.$parameterName.' must use '.$template.' ('.$expected->describe(VerbosityLevel::typeOnly()).' or subtype).';
                        continue;
                    }
                    if (in_array(strtolower($actualName), ['int', 'string', 'float', 'bool', 'mixed'], true)) {
                        continue;
                    }
                    $actual = new ObjectType($actualName);
                    if (!$expected->isSuperTypeOf($actual)->yes() || !new ObjectType($base)->isSuperTypeOf($actual)->yes()) {
                        $parameterName = $parameter->var instanceof Node\Expr\Variable && is_string($parameter->var->name) ? $parameter->var->name : 'parameter';
                        $errors[] = $class->getName().'::'.$method->name->toString().'() parameter $'.$parameterName.' must use '.$template.' ('.$expected->describe(VerbosityLevel::typeOnly()).' or subtype).';
                    }
                }
            }
        }
        foreach ($node->stmts as $statement) {
            if (!$statement instanceof Node\Stmt\ClassMethod) {
                continue;
            }
            foreach ($statement->params as $index => $parameter) {
                $methodName = $statement->name->toString();
                $expected = match ($methodName) {
                    'ask' => Query::class, 'dispatch' => Command::class,
                    'publish' => $class->implementsInterface(DomainEventPublisherInterface::class) ? DomainEvent::class : IntegrationEvent::class,
                    default => null,
                };
                if (null !== $expected && 0 === $index && $parameter->type instanceof Node\Name) {
                    $actual = new ObjectType($scope->resolveName($parameter->type));
                    if (!$actual->isSuperTypeOf(new ObjectType($expected))->yes()) {
                        $parameterName = $parameter->var instanceof Node\Expr\Variable && is_string($parameter->var->name) ? $parameter->var->name : 'parameter';
                        $errors[] = $class->getName().'::'.$methodName.'() parameter $'.$parameterName.' must accept '.$expected.' or a subtype.';
                    }
                }
            }
        }

        return array_map(static fn (string $message) => RuleErrorBuilder::message($message)->identifier('hexagonal.portParameter')->build(), $errors);
    }
}
