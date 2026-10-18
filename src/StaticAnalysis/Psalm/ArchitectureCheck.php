<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\StaticAnalysis\Architecture\ContextDiscovery;
use Hexagonal\StaticAnalysis\Architecture\DeclarationPolicy;
use Hexagonal\StaticAnalysis\Architecture\Diagnostic;
use Hexagonal\StaticAnalysis\Architecture\ScopeConfiguration;
use InvalidArgumentException;
use Override;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use Psalm\Codebase;
use Psalm\CodeLocation\Raw;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

use function array_values;
use function is_file;
use function str_starts_with;

final class ArchitectureCheck implements AfterCodebasePopulatedInterface
{
    public static string $configuration = '';

    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        $codebase = $event->getCodebase();
        $files = [];
        foreach ($codebase->file_storage_provider->getAll() as $storage) {
            if ($codebase->config->isInProjectDirs($storage->file_path) && $codebase->analyzer->canReportIssues($storage->file_path)) {
                $files[] = $storage->file_path;
            }
        }
        $anchor = $files[0] ?? $codebase->config->source_filename;
        if (null === $anchor) {
            throw new InvalidArgumentException('Architecture checks require an analyzer source file.');
        }
        $path = self::$configuration;
        if (!str_starts_with($path, '/')) {
            $path = $codebase->config->base_dir.'/'.$path;
        }
        try {
            $configuration = ScopeConfiguration::fromFile($path);
        } catch (InvalidArgumentException $exception) {
            self::report($codebase, new Diagnostic('hexagonal.invalidScope', $exception->getMessage(), $anchor), $anchor);

            return;
        }
        foreach (new ContextDiscovery()->diagnostics($configuration, $files) as $diagnostic) {
            self::report($codebase, $diagnostic, $anchor);
        }
        $policy = new DeclarationPolicy($configuration);
        foreach ($files as $file) {
            if (null === $configuration->context($file)) {
                continue;
            }
            $nodes = new NodeTraverser(new CloningVisitor(), new NameResolver())->traverse($codebase->getStatementsForFile($file));
            foreach (new NodeFinder()->findInstanceOf($nodes, ClassLike::class) as $class) {
                $name = isset($class->namespacedName) ? $class->namespacedName->toString() : null;
                $ancestors = [];
                if (null !== $name && $codebase->classlike_storage_provider->has($name)) {
                    $storage = $codebase->classlike_storage_provider->get($name);
                    $ancestors = array_values([...$storage->parent_classes, ...$storage->parent_interfaces, ...$storage->class_implements]);
                }
                foreach ($policy->check($file, $class->getStartLine(), $name, $ancestors, $class instanceof Trait_) as $diagnostic) {
                    self::report($codebase, $diagnostic, $anchor, $class->getStartFilePos(), $class->getEndFilePos());
                }
            }
        }
    }

    private static function report(Codebase $codebase, Diagnostic $diagnostic, string $anchor, int $start = 0, int $end = 0): void
    {
        $file = is_file($diagnostic->file) && $codebase->analyzer->canReportIssues($diagnostic->file) ? $diagnostic->file : $anchor;
        $contents = $codebase->getFileContents($file);
        IssueBuffer::maybeAdd(new ArchitectureIssue('['.$diagnostic->identifier.'] '.$diagnostic->message, new Raw($contents, $file, $file, $start, $end)));
    }
}
