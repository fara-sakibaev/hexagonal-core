<?php

declare(strict_types=1);

namespace Hexagonal\Domain;

abstract class AggregateRoot
{
    public private(set) array $pendingEvents = [];

    public function __construct(
    )
    {
    }

    public function recordThat(DomainEvent $event): self
    {
        $this->pendingEvents[] = $event;

        return $this;
    }

    /** @return list<DomainEvent> */
    public function releaseEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }
}
