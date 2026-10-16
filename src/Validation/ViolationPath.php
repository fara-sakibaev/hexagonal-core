<?php

declare(strict_types=1);

namespace Hexagonal\Validation;

final readonly class ViolationPath
{
    public function __construct(public string $value)
    {
    }

    public static function root(): self
    {
        return new self('');
    }
}
