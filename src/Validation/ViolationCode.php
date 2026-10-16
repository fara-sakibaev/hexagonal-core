<?php

declare(strict_types=1);

namespace Hexagonal\Validation;

use InvalidArgumentException;

final readonly class ViolationCode
{
    public function __construct(public string $value)
    {
        if ('' === $value) {
            throw new InvalidArgumentException('Violation code must not be empty.');
        }
    }
}
