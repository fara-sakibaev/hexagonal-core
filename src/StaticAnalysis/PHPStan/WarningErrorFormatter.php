<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;
use Override;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;

use function array_map;

final readonly class WarningErrorFormatter implements ErrorFormatter
{
    public function __construct(private ErrorFormatter $delegate)
    {
    }

    #[Override]
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $errors = array_map(static fn (ReadPortWarning $warning): Error => Error::decode([
            'message' => '['.ReadPortWarning::IDENTIFIER.'] '.ReadPortWarning::MESSAGE,
            'file' => $warning->file,
            'line' => $warning->line,
            'canBeIgnored' => true,
            'filePath' => null,
            'traitFilePath' => null,
            'tip' => null,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => ReadPortWarning::IDENTIFIER,
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
            'traitContexts' => [],
        ]), ReadPortWarnings::fromResult($analysisResult));
        if ([] === $errors) {
            return $this->delegate->formatErrors($analysisResult, $output);
        }

        $result = $analysisResult->withFileSpecificErrors([
            ...$analysisResult->getFileSpecificErrors(),
            ...$errors,
        ]);

        return $this->delegate->formatErrors($result, $output);
    }
}
