<?php

declare(strict_types=1);

namespace Hexagonal\Application\Query;

use Hexagonal\Application\DTO\DTO;

/**
 * @template-covariant TResult of DTO
 */
abstract readonly class Query
{
}
