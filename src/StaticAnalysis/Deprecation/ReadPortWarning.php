<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Deprecation;

use function array_values;
use function serialize;
use function strcmp;
use function usort;

final readonly class ReadPortWarning
{
    public const string IDENTIFIER = 'hexagonal.deprecatedReadPort';
    public const string MESSAGE = 'ReadPortInterface is deprecated. Consider using RepositoryInterface for aggregate persistence or QueryHandlerInterface for application queries. ReadPortInterface will be removed in the next breaking release; remaining usages will cause analysis errors.';

    public function __construct(public string $file, public int $line)
    {
    }

    /** @return array{identifier: string, message: string, file: string, line: int} */
    public function toArray(): array
    {
        return ['identifier' => self::IDENTIFIER, 'message' => self::MESSAGE, 'file' => $this->file, 'line' => $this->line];
    }

    /**
     * @param list<self> $warnings
     *
     * @return list<self>
     */
    public static function normalize(array $warnings): array
    {
        $unique = [];
        foreach ($warnings as $warning) {
            $unique[serialize([$warning->file, $warning->line])] = $warning;
        }
        $result = array_values($unique);
        usort($result, static fn (self $a, self $b): int => strcmp($a->file, $b->file) ?: $a->line <=> $b->line);

        return $result;
    }
}
