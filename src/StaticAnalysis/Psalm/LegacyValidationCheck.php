<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Domain\PolicyInterface;
use Hexagonal\Validation\ValidatorInterface;
use Override;
use Psalm\CodeLocation;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;

use function str_starts_with;
use function strtolower;

final class LegacyValidationCheck implements AfterClassLikeAnalysisInterface
{
    #[Override]
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        $storage = $event->getClasslikeStorage();
        $interfaces = $storage->class_implements;
        if (isset($interfaces[strtolower(ValidatorInterface::class)])
            || isset($interfaces[strtolower(PolicyInterface::class)])) {
            return null;
        }
        $codebase = $event->getCodebase();
        foreach ($storage->methods as $name => $method) {
            if (!str_starts_with(strtolower($name), 'validate') || $method->defining_fqcln !== $storage->name) {
                continue;
            }
            $required = false;
            foreach ($interfaces as $interface) {
                $ancestor = $codebase->classlike_storage_provider->get((string) $interface);
                if (isset($ancestor->methods[$name])) {
                    $required = true;
                    break;
                }
            }
            $parentName = $storage->parent_class;
            while (!$required && null !== $parentName) {
                $parent = $codebase->classlike_storage_provider->get($parentName);
                $required = isset($parent->methods[$name]) && $parent->methods[$name]->abstract;
                $parentName = $parent->parent_class;
            }
            if (!$required) {
                IssueBuffer::maybeAdd(new LegacyValidationIssue($storage->name.'::'.$name.'() is a legacy validation method; extract a named validator or domain policy.', new CodeLocation($event->getStatementsSource(), $event->getStmt())));
            }
        }

        return null;
    }
}
