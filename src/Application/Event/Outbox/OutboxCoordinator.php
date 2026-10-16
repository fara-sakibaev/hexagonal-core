<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

use Hexagonal\Domain\AggregateRoot;

final readonly class OutboxCoordinator
{
    public function __construct(
        private TransactionInterface $transaction,
        private OutboxInterface $outbox,
        private EventIdFactoryInterface $ids,
    ) {
    }

    /**
     * The transaction adapter must cover both the aggregate store and outbox.
     *
     * @param callable(AggregateRoot): void $persist
     */
    public function persist(AggregateRoot $aggregate, callable $persist): void
    {
        $events = $aggregate->pendingEvents();
        $this->transaction->run(function () use ($aggregate, $persist, $events): void {
            $persist($aggregate);
            foreach ($events as $event) {
                $this->outbox->append(new OutboxRecord($this->ids->nextId(), $event));
            }
        });
        $aggregate->acknowledgeEvents($events);
    }
}
