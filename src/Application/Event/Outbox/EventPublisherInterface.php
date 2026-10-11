<?php

declare(strict_types=1);

namespace Hexagonal\Application\Event\Outbox;

interface EventPublisherInterface
{
    public function publish(OutboxRecord $record): void;
}
