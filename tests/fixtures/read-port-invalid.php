<?php

declare(strict_types=1);

namespace ReadPortInvalid;

use Hexagonal\Application\Command\Command;
use Hexagonal\Application\Command\CommandHandlerInterface;
use Hexagonal\Application\Port\Outbound\ReadPortInterface;

final readonly class WrongInput extends Command
{
}

/** @extends ReadPortInterface<WrongInput> */
interface InvalidPort extends ReadPortInterface
{
}

/** @implements CommandHandlerInterface<WrongInput> */
final class MissingHandler implements CommandHandlerInterface
{
}
