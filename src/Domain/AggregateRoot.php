<?php

declare(strict_types=1);

namespace Hexagonal\Domain;

use Hexagonal\Type\InternalIdInterface;

use function array_search;

abstract class AggregateRoot
{
    abstract public InternalIdInterface $id { get; }

    /** @var list<DomainEvent> */
    public private(set) array $pendingEvents = [];

    public function __construct(
    ) {
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

    /** @return list<DomainEvent> */
    public function pendingEvents(): array
    {
        return $this->pendingEvents;
    }

    /** @param list<DomainEvent> $events */
    public function acknowledgeEvents(array $events): void
    {
        $remaining = [];
        foreach ($this->pendingEvents as $pending) {
            $index = array_search($pending, $events, true);
            if (false === $index) {
                $remaining[] = $pending;
            } else {
                unset($events[$index]);
            }
        }
        $this->pendingEvents = $remaining;
    }
}
