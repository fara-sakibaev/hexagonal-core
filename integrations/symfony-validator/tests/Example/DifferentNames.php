<?php

declare(strict_types=1);

namespace Hexagonal\SymfonyValidator\Tests\Example;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DifferentNames extends Constraint
{
    public function getTargets(): string|array { return self::CLASS_CONSTRAINT; }
}
