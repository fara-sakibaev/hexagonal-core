<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Hexagonal\Application\Event\Outbox\EventIdFactoryInterface;
use Hexagonal\Application\Event\Outbox\EventPublisherInterface;
use Hexagonal\Application\Event\Outbox\OutboxCoordinator;
use Hexagonal\Application\Event\Outbox\OutboxInterface;
use Hexagonal\Application\Event\Outbox\OutboxRecord;
use Hexagonal\Application\Event\Outbox\OutboxRelay;
use Hexagonal\Application\Event\Outbox\TransactionInterface;
use Hexagonal\Domain\AggregateRoot;
use Hexagonal\Domain\DomainEvent;
use Hexagonal\Type\InternalIdInterface;

final readonly class TestId implements InternalIdInterface
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

final readonly class Changed extends DomainEvent
{
}

final class TestAggregate extends AggregateRoot
{
    public function __construct(public readonly TestId $id)
    {
    }
}

final class MemoryOutbox implements OutboxInterface
{
    /** @var array<string, OutboxRecord> */
    public array $records = [];
    /** @var array<string, true> */
    public array $published = [];

    public function append(OutboxRecord $record): void
    {
        $this->records[$record->eventId] = $record;
    }

    public function unpublished(): iterable
    {
        foreach ($this->records as $id => $record) {
            if (!isset($this->published[$id])) {
                yield $record;
            }
        }
    }

    public function markPublished(string $eventId): void
    {
        $this->published[$eventId] = true;
    }
}

final class MemoryTransaction implements TransactionInterface
{
    public function __construct(private MemoryOutbox $outbox)
    {
    }

    public function run(callable $operation): mixed
    {
        $before = $this->outbox->records;
        try {
            return $operation();
        } catch (Throwable $error) {
            $this->outbox->records = $before;
            throw $error;
        }
    }
}

final class SequentialIds implements EventIdFactoryInterface
{
    private int $next = 0;

    public function nextId(): string
    {
        return (string) ++$this->next;
    }
}

final class MemoryPublisher implements EventPublisherInterface
{
    /** @var list<string> */
    public array $deliveries = [];
    /** @var array<string, true> */
    public array $processed = [];
    public bool $fail = false;

    public function publish(OutboxRecord $record): void
    {
        $this->deliveries[] = $record->eventId;
        $this->processed[$record->eventId] = true;
        if ($this->fail) {
            throw new RuntimeException('Crash after publish');
        }
    }
}

$outbox = new MemoryOutbox();
$aggregate = new TestAggregate(new TestId('a'));
$aggregate->recordThat(new Changed());
$coordinator = new OutboxCoordinator(new MemoryTransaction($outbox), $outbox, new SequentialIds());
try {
    $coordinator->persist($aggregate, static function (): void { throw new RuntimeException('Persistence failed'); });
    throw new LogicException('Expected failure');
} catch (RuntimeException $error) {
    assert('Persistence failed' === $error->getMessage());
}
assert([] === $outbox->records);
assert(1 === count($aggregate->pendingEvents()));
$coordinator->persist($aggregate, static function (): void {});
assert(1 === count($outbox->records));
assert([] === $aggregate->pendingEvents());

$publisher = new MemoryPublisher();
$relay = new OutboxRelay($outbox, $publisher);
$publisher->fail = true;
try {
    $relay->publishPending();
} catch (RuntimeException) {
}
assert([] === $outbox->published);
$publisher->fail = false;
$relay->publishPending();
assert($publisher->deliveries === ['1', '1']);
assert(1 === count($publisher->processed));
assert(isset($outbox->published['1']));
