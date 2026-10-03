<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Domain\DomainEvent;

/**
 * @template TEvent of DomainEvent
 */
interface DomainEventPublisherInterface
{
    public function publish(DomainEvent ...$event): void;
}
