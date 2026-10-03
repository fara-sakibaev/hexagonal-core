<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Application\Event\IntegrationEvent;

/**
 * @template TEvent of IntegrationEvent
 */
interface IntegrationEventPublisherInterface
{
    public function publish(IntegrationEvent ...$event): void;
}