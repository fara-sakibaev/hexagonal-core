<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;
use PHPStan\Command\AnalysisResult;

use function is_array;
use function is_int;

final class ReadPortWarnings
{
    /** @return list<ReadPortWarning> */
    public static function fromResult(AnalysisResult $result): array
    {
        $warnings = [];
        foreach ($result->getCollectedData() as $data) {
            if (ReadPortCollector::class !== $data->getCollectorType()) {
                continue;
            }
            $lines = $data->getData();
            if (!is_array($lines)) {
                continue;
            }
            foreach ($lines as $batch) {
                if (!is_array($batch)) {
                    continue;
                }
                foreach ($batch as $line) {
                    if (is_int($line)) {
                        $warnings[] = new ReadPortWarning($data->getFilePath(), $line);
                    }
                }
            }
        }

        return ReadPortWarning::normalize($warnings);
    }
}
