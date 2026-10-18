<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;
use Override;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;

use function array_map;
use function count;
use function json_encode;
use function strip_tags;

final class WarningJsonErrorFormatter implements ErrorFormatter
{
    #[Override]
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $files = [];
        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $file = $error->getFile();
            $files[$file] ??= ['errors' => 0, 'messages' => []];
            ++$files[$file]['errors'];
            $message = [
                'message' => $error->getMessage(),
                'line' => $error->getLine(),
                'ignorable' => $error->canBeIgnored(),
            ];
            if (null !== $error->getTip()) {
                $message['tip'] = strip_tags($error->getTip());
            }
            if (null !== $error->getIdentifier()) {
                $message['identifier'] = $error->getIdentifier();
            }
            $files[$file]['messages'][] = $message;
        }
        $output->writeRaw(json_encode([
            'totals' => [
                'errors' => count($analysisResult->getNotFileSpecificErrors()),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
            ],
            'files' => (object) $files,
            'errors' => $analysisResult->getNotFileSpecificErrors(),
            'warnings' => array_map(static fn (ReadPortWarning $warning): array => $warning->toArray(), ReadPortWarnings::fromResult($analysisResult)),
        ], JSON_THROW_ON_ERROR));

        return $analysisResult->hasErrors() ? 1 : 0;
    }
}
