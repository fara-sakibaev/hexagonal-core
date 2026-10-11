<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

interface OutboxInterface
{
    public function append(OutboxRecord $record): void;

    /** @return iterable<OutboxRecord> */
    public function unpublished(): iterable;

    public function markPublished(string $eventId): void;
}
