<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Query\Query;

/**
 * @template TInput of Query<DTO>
 */
interface ReadPortInterface
{
}
