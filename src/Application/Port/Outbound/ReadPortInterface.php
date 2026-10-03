<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Query\Query;

/**
 * @template TInput of Command|Query
 */
interface ReadPortInterface
{
}