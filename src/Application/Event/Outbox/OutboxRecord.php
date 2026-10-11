<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

use Hexagonal\Domain\DomainEvent;

/** Event ID must remain unchanged across publication retries. */
final readonly class OutboxRecord
{
    public function __construct(
        public string $eventId,
        public DomainEvent $event,
    ) {
    }
}
