<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Inbound;

use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Query\Query;

interface QueryBusInterface
{
    public function ask(Query $query): DTO;
}
