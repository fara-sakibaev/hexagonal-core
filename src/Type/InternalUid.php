<?php

declare(strict_types=1);

namespace Hexagonal\Type;

use Symfony\Component\Uid\Uuid;

final class InternalUid extends Uuid implements InternalIdInterface
{
    public function __toString(): string
    {
        return $this->toRfc4122();
    }
}
