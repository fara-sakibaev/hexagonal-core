<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\Application\Port\Outbound\RepositoryInterface;
use Hexagonal\Application\Query\Query;
use Hexagonal\Domain\AggregateRoot;
use Override;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use Psalm\Codebase;
use Psalm\CodeLocation\Raw;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;

use function array_map;
use function in_array;

final class GenericPortMethodCheck implements AfterCodebasePopulatedInterface
{
    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        $codebase = $event->getCodebase();
        foreach ($codebase->file_storage_provider->getAll() as $fileStorage) {
            $file = $fileStorage->file_path;
            if (!$codebase->config->isInProjectDirs($file) || !$codebase->analyzer->canReportIssues($file)) {
                continue;
            }
            $resolver = new NameResolver();
            new NodeTraverser(new CloningVisitor(), $resolver, new class($codebase, $file) extends NodeVisitorAbstract {
                public function __construct(private readonly Codebase $codebase, private readonly string $file)
                {
                }

                #[Override]
                public function enterNode(Node $node): ?Node
                {
                    if (!$node instanceof Node\Stmt\Interface_ || !isset($node->namespacedName)) {
                        return null;
                    }
                    $name = $node->namespacedName->toString();
                    if (!$this->codebase->classlike_storage_provider->has($name)) {
                        return null;
                    }
                    $file = $this->file;
                    $storage = $this->codebase->classlike_storage_provider->get($name);
                    $parents = array_map(strtolower(...), [...$storage->parent_interfaces, ...$storage->class_implements]);
                    $maps = $storage->template_extended_params ?? [];
                    foreach ([RepositoryInterface::class => ['TAggregate', AggregateRoot::class], ReadPortInterface::class => ['TInput', Query::class]] as $port => [$template, $base]) {
                        $map = $maps[\strtolower($port)] ?? $maps[$port] ?? null;
                        $expected = $map[$template] ?? null;
                        if (!in_array(\strtolower($port), $parents, true) || null === $expected) {
                            continue;
                        }
                        foreach ($storage->methods as $method) {
                            foreach ($method->params as $parameter) {
                                if (null === $parameter->type || $parameter->type->isMixed()) {
                                    continue;
                                }
                                $invalidModel = false;
                                foreach ($parameter->type->getAtomicTypes() as $actual) {
                                    if ($actual instanceof TArray || $actual instanceof TKeyedArray) {
                                        $invalidModel = true;
                                        continue;
                                    }
                                    if (!$actual instanceof TNamedObject) {
                                        continue;
                                    }
                                    if (!$this->codebase->classExtendsOrImplements($actual->getId(), $base)) {
                                        $invalidModel = true;
                                        continue;
                                    }
                                    $matchesModel = false;
                                    foreach ($expected->getAtomicTypes() as $model) {
                                        if ($model instanceof TNamedObject && $this->codebase->classExtendsOrImplements($actual->getId(), $model->getId())) {
                                            $matchesModel = true;
                                        }
                                    }
                                    $invalidModel = $invalidModel || !$matchesModel;
                                }
                                if ($invalidModel) {
                                    $location = new Raw($this->codebase->getFileContents($file), $file, $file, $node->getStartFilePos(), $node->getEndFilePos());
                                    IssueBuffer::maybeAdd(new PortMethodIssue($storage->name.'::'.$method->cased_name.'() parameter $'.$parameter->name.' must use '.$template.' model '.$expected->getId().'.', $location));
                                }
                            }
                        }
                    }

                    return null;
                }
            }
            )->traverse($codebase->getStatementsForFile($file));
        }
    }
}
