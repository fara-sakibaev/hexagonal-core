<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;

use function array_keys;

/**
 * @psalm-type Declaration array{name: ?string, line: int, ancestors: list<string>, trait: bool}
 *
 * @implements Collector<FileNode, list<Declaration>>
 */
final readonly class ArchitectureCollector implements Collector
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $declarations = [];
        foreach (new NodeFinder()->findInstanceOf($node->getNodes(), ClassLike::class) as $class) {
            $name = isset($class->namespacedName) ? $class->namespacedName->toString() : null;
            $ancestors = [];
            if (null !== $name && $this->reflectionProvider->hasClass($name)) {
                $ancestors = array_keys($this->reflectionProvider->getClass($name)->getAncestors());
            }
            $declarations[] = ['name' => $name, 'line' => $class->getStartLine(), 'ancestors' => $ancestors, 'trait' => $class instanceof Trait_];
        }

        return $declarations;
    }
}
