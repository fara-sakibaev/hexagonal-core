<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Architecture;

use Hexagonal\StaticAnalysis\AbstractionRole\Layer;
use Hexagonal\StaticAnalysis\AbstractionRole\RoleMapper;

use function array_map;
use function explode;
use function implode;
use function realpath;
use function strlen;
use function substr;

final readonly class DeclarationPolicy
{
    public function __construct(private ScopeConfiguration $scope)
    {
    }

    /** @param list<string> $ancestors
     * @return list<Diagnostic>
     */
    public function check(string $file, int $line, ?string $name, array $ancestors = [], bool $trait = false): array
    {
        $file = realpath($file) ?: $file;
        $root = $this->scope->context($file);
        if (null === $root || $trait) {
            return [];
        }
        if (null === $name) {
            return [new Diagnostic('hexagonal.anonymousClass', 'Anonymous classes are forbidden in contexts; use a named class.', $file, $line)];
        }
        $relative = substr($file, strlen($root) + 1);
        $layer = Layer::tryFrom(explode('/', $relative)[0]);
        if (null === $layer) {
            return [new Diagnostic('hexagonal.outOfLayer', $name.' must be declared inside Application, Domain or Infrastructure.', $file, $line)];
        }
        $resolution = new RoleMapper()->resolve($name, $ancestors);
        $violation = $layer->violation($resolution);
        if (null === $violation) {
            return [];
        }
        $roles = array_map(static fn ($role): string => $role->name, $resolution->roles);

        return [new Diagnostic($violation, $name.' in '.$layer->value.' has '.([] === $roles ? 'no recognized role' : 'role(s): '.implode(', ', $roles)).'.', $file, $line)];
    }
}
