<?php

declare(strict_types=1);

namespace Hexagonal\Validation;

final readonly class Violation
{
    public function __construct(public ViolationCode $code, public ViolationPath $path)
    {
    }
}
