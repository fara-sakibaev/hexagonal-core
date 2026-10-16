<?php

declare(strict_types=1);

namespace Hexagonal\Type;

interface IdInterface
{
    public function __toString(): string;
}
