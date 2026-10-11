<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\Application\Query\Query;

final readonly class InvalidCommand extends Command {}

/** @extends Query<string> */
final readonly class InvalidQuery extends Query {}

/** @extends ReadPortInterface<InvalidCommand> */
interface InvalidReadPort extends ReadPortInterface {}
