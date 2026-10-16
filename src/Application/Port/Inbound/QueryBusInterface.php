<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Inbound;

use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Query\Query;

interface QueryBusInterface
{
    /**
     * @template TResult of DTO
     *
     * @param Query<TResult> $query
     *
     * @return TResult
     */
    public function ask(Query $query): DTO;
}
