<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

final readonly class OutboxRelay
{
    public function __construct(private OutboxInterface $outbox, private EventPublisherInterface $publisher)
    {
    }

    public function publishPending(): void
    {
        foreach ($this->outbox->unpublished() as $record) {
            $this->publisher->publish($record);
            $this->outbox->markPublished($record->eventId);
        }
    }
}
