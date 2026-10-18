<?php

declare(strict_types=1);

namespace ReadPortReferences;

use Hexagonal\Application\DTO\DTO;
use Hexagonal\Application\Port\Outbound\ReadPortInterface as Legacy;
use Hexagonal\Application\Query\Query;
use Stringable;

final readonly class Result extends DTO
{
}
/** @extends Query<Result> */
final readonly class Request extends Query
{
}

/** @extends Legacy<Request> */
interface Direct extends Legacy
{
}
interface Indirect extends Direct
{
}
final class Adapter implements Indirect
{
}

/** @param Legacy<Request> $port */
function nativeParameter(Legacy $port): void
{
}

/** @return Legacy<Request> */
function nativeReturn(Direct $port): Legacy
{
    return $port;
}

final class Types
{
    /** @var Legacy<Request>|null */
    public ?Legacy $port = null;

    /**
     * @param array<string, list<Legacy<Request>|null>> $ports
     *
     * @return array<string, list<Legacy<Request>|null>>
     */
    public function nested(array $ports): array
    {
        return $ports;
    }

    public function intersection(Direct&Stringable $port): Direct&Stringable
    {
        return $port;
    }

    public function union(Direct|Indirect $port): Direct|Indirect
    {
        return $port;
    }
}

interface ReadPortInterface
{
}
function unrelated(ReadPortInterface $port): ReadPortInterface
{
    return $port;
}
const TEXT = 'Hexagonal\Application\Port\Outbound\ReadPortInterface';

/** @param array{Legacy: int} $value */
function shapeKey(array $value): void
{
}
