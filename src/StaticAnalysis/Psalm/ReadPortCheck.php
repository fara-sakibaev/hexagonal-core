<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Override;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

final class ReadPortCheck implements AfterCodebasePopulatedInterface
{
    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        $codebase = $event->getCodebase();
        foreach ($codebase->file_storage_provider->getAll() as $storage) {
            $file = $storage->file_path;
            if (!$codebase->config->isInProjectDirs($file) || !$codebase->analyzer->canReportIssues($file)) {
                continue;
            }
            $resolver = new NameResolver();
            new NodeTraverser(
                new CloningVisitor(),
                $resolver,
                new ReadPortVisitor($codebase, $file, $resolver),
            )->traverse($codebase->getStatementsForFile($file));
        }
    }
}
