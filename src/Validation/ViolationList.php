<?php

declare(strict_types=1);

namespace Hexagonal\Validation;

/** @implements \IteratorAggregate<int, Violation> */
final readonly class ViolationList implements \Countable, \IteratorAggregate
{
    /** @var list<Violation> */
    private array $violations;

    public function __construct(Violation ...$violations)
    {
        $this->violations = array_values($violations);
    }

    public function isEmpty(): bool
    {
        return $this->violations === [];
    }

    #[\Override]
    public function count(): int
    {
        return count($this->violations);
    }

    /** @return \Traversable<int, Violation> */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->violations);
    }

    public function merge(self $other): self
    {
        return new self(...$this->violations, ...$other->violations);
    }
}
