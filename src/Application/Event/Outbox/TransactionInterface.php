<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

interface TransactionInterface
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed;
}
