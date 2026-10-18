<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Architecture;

use InvalidArgumentException;
use JsonException;

use function array_diff;
use function array_is_list;
use function array_keys;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_string;
use function json_decode;
use function realpath;
use function rtrim;
use function sort;
use function str_starts_with;

final readonly class ScopeConfiguration
{
    /** @var list<string> */
    public array $searchPaths;
    /** @var list<string> */
    public array $contextRoots;
    /** @var list<string> */
    public array $testPaths;

    /**
     * @param list<string> $searchPaths
     * @param list<string> $contextRoots
     * @param list<string> $testPaths
     */
    public function __construct(string $base, array $searchPaths, array $contextRoots, array $testPaths = [])
    {
        $this->searchPaths = self::paths($base, $searchPaths);
        $this->contextRoots = self::paths($base, $contextRoots);
        $this->testPaths = self::paths($base, $testPaths);
        if ([] === $this->searchPaths) {
            throw new InvalidArgumentException('search_paths must not be empty.');
        }
        foreach ($this->contextRoots as $root) {
            if (!self::withinAny($root, $this->searchPaths)) {
                throw new InvalidArgumentException('Context root is outside search_paths: '.$root);
            }
            if (self::withinAny($root, $this->testPaths)) {
                throw new InvalidArgumentException('Context root is excluded by test_paths: '.$root);
            }
        }
    }

    /** @throws InvalidArgumentException */
    public static function fromFile(string $file): self
    {
        $contents = @file_get_contents($file);
        if (false === $contents) {
            throw new InvalidArgumentException('Cannot read architecture configuration: '.$file);
        }
        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid architecture JSON: '.$file, 0, $exception);
        }
        if (!is_array($data) || array_diff(array_keys($data), ['search_paths', 'context_roots', 'test_paths'])) {
            throw new InvalidArgumentException('Expected search_paths, context_roots and optional test_paths.');
        }
        $lists = [];
        foreach (['search_paths', 'context_roots', 'test_paths'] as $key) {
            $value = $data[$key] ?? ('test_paths' === $key ? [] : null);
            if (!is_array($value) || !array_is_list($value)) {
                throw new InvalidArgumentException($key.' must be a list of directories.');
            }
            foreach ($value as $path) {
                if (!is_string($path)) {
                    throw new InvalidArgumentException($key.' must contain only strings.');
                }
            }
            $lists[$key] = $value;
        }

        return new self(dirname($file), $lists['search_paths'], $lists['context_roots'], $lists['test_paths']);
    }

    public static function contains(string $directory, string $path): bool
    {
        return $path === $directory || str_starts_with($path, rtrim($directory, '/').'/');
    }

    /** @param list<string> $directories */
    public static function withinAny(string $path, array $directories): bool
    {
        foreach ($directories as $directory) {
            if (self::contains($directory, $path)) {
                return true;
            }
        }

        return false;
    }

    public function isTest(string $file): bool
    {
        return self::withinAny(realpath($file) ?: $file, $this->testPaths);
    }

    public function context(string $file): ?string
    {
        $file = realpath($file) ?: $file;
        if ($this->isTest($file)) {
            return null;
        }
        foreach ($this->contextRoots as $root) {
            if (self::contains($root, $file)) {
                return $root;
            }
        }

        return null;
    }

    /** @param list<string> $paths
     * @return list<string>
     */
    private static function paths(string $base, array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            $absolute = realpath(str_starts_with($path, '/') ? $path : $base.'/'.$path);
            if ('' === $path || false === $absolute || !is_dir($absolute)) {
                throw new InvalidArgumentException('Directory does not exist: '.$path);
            }
            foreach ($result as $existing) {
                if (self::contains($existing, $absolute) || self::contains($absolute, $existing)) {
                    throw new InvalidArgumentException('Duplicate or overlapping paths: '.$existing.' and '.$absolute);
                }
            }
            $result[] = $absolute;
        }
        sort($result);

        return $result;
    }
}
