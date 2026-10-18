<?php

declare(strict_types=1);

namespace Hexagonal\Application\Port\Outbound;

use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Query\Query;

/**
 * Deprecated contract, retained for the migration period.
 *
 * Use RepositoryInterface for aggregate persistence or QueryHandlerInterface
 * for application queries. Removal is planned for the next breaking release.
 * The analyzer integrations own the nonfatal deprecation diagnostic.
 *
 * @template TInput of Query<DTO>
 */
interface ReadPortInterface
{
}
