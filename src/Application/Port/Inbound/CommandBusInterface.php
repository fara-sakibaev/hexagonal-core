<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Inbound;

use Hexagonal\Application\Command\Command;

interface CommandBusInterface
{
    public function dispatch(Command $command): void;
}
