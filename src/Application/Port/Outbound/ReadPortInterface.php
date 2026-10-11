<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Application\Query\Query;

/**
 * @template TInput of Query<\Hexagonal\Application\DTO\DTO>
 */
interface ReadPortInterface
{
}
