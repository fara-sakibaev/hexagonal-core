<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

interface EventIdFactoryInterface
{
    public function nextId(): string;
}
