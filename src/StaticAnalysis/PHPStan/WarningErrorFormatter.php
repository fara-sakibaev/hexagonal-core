<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;
use Override;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;

use function sprintf;

final readonly class WarningErrorFormatter implements ErrorFormatter
{
    public function __construct(private ErrorFormatter $delegate)
    {
    }

    #[Override]
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $exit = $this->delegate->formatErrors($analysisResult, $output);
        foreach (ReadPortWarnings::fromResult($analysisResult) as $warning) {
            $output->writeRaw(sprintf("\nWarning: %s:%d [%s] %s\n", $warning->file, $warning->line, ReadPortWarning::IDENTIFIER, ReadPortWarning::MESSAGE));
        }

        return $exit;
    }
}
