<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Architecture;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_fill_keys;
use function array_map;
use function array_unique;
use function array_values;
use function dirname;
use function in_array;
use function realpath;
use function sort;
use function strtolower;

final readonly class ContextDiscovery
{
    /** @return list<string> */
    public function candidates(ScopeConfiguration $scope): array
    {
        $roots = [];
        foreach ($this->entries($scope) as $entry) {
            if ($entry->isDir() && in_array($entry->getFilename(), ['Application', 'Domain', 'Infrastructure'], true)) {
                $roots[] = dirname($entry->getPathname());
            }
        }
        $roots = array_values(array_unique($roots));
        sort($roots);

        return $roots;
    }

    /**
     * @param list<string> $analyzedFiles the analyzer's effective file set, after exclusions
     *
     * @return list<Diagnostic>
     */
    public function diagnostics(ScopeConfiguration $scope, array $analyzedFiles): array
    {
        $errors = [];
        foreach ($this->candidates($scope) as $root) {
            if (!in_array($root, $scope->contextRoots, true)) {
                $errors[] = new Diagnostic('hexagonal.undeclaredContext', 'Declare discovered context root: '.$root, $root);
            }
        }
        $files = array_fill_keys(array_map(static fn (string $file): string => realpath($file) ?: $file, $analyzedFiles), true);
        foreach ($this->entries($scope) as $entry) {
            $file = $entry->getPathname();
            if ($entry->isFile() && 'php' === strtolower($entry->getExtension()) && null !== $scope->context($file) && !isset($files[$file])) {
                $errors[] = new Diagnostic('hexagonal.incompleteScope', 'Context PHP file is excluded from analyzer coverage: '.$file, $file);
            }
        }

        return $errors;
    }

    /** @return iterable<SplFileInfo> */
    private function entries(ScopeConfiguration $scope): iterable
    {
        foreach ($scope->searchPaths as $path) {
            yield new SplFileInfo($path);
            $filter = new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), static fn (SplFileInfo $entry): bool => !$entry->isLink() && !in_array($entry->getFilename(), ['vendor', '.git'], true) && !$scope->isTest($entry->getPathname()));
            yield from new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
        }
    }
}
