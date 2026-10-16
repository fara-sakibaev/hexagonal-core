<?php

declare(strict_types=1);

namespace Hexagonal\SymfonyValidator\Tests\Example;

use Hexagonal\Validation\ValidationInputInterface;

#[DifferentNames]
final readonly class TransferInput implements ValidationInputInterface
{
    public function __construct(public string $first, public string $second) {}
}
