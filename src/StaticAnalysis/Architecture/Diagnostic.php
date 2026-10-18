<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Architecture;

final readonly class Diagnostic
{
    public function __construct(public string $identifier, public string $message, public string $file, public int $line = 1)
    {
    }
}
