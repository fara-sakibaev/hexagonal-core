<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event;

use Hexagonal\Domain\DomainEvent;

/**
 * @template TEvent of DomainEvent
 */
interface DomainEventHandlerInterface
{
}