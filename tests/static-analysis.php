<?php

declare(strict_types=1);

/** @param list<string> $command */
function analyse(array $command): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    if (!is_resource($process)) throw new RuntimeException('Analyzer did not start.');
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    return [proc_close($process), $output];
}

$phpstan = ['vendor/bin/phpstan', 'analyse', '--debug', '--no-progress', '--level', '8', '--autoload-file', 'vendor/autoload.php'];
[$code, $output] = analyse([...$phpstan, '-c', 'phpstan-extension.neon', 'src', 'tests/fixtures/valid-contracts.php', 'tests/fixtures/validation-example.php']);
assert($code === 0, $output);
[$code, $output] = analyse([...$phpstan, '-c', 'phpstan-extension.neon', 'tests/fixtures/invalid-validation.php']);
assert($code !== 0 && substr_count($output, 'hexagonal.handler') === 5, $output);
foreach (['MissingValidatorMethod', 'WrongValidatorInput', 'BroadValidatorInput', 'WrongRuleReturn', 'MissingRuleMethod'] as $name) assert(str_contains($output, $name), $output);
[$code, $output] = analyse([...$phpstan, '-c', 'phpstan-extension.neon', 'tests/fixtures/invalid-handlers.php']);
assert($code !== 0 && str_contains($output, 'hexagonal.handler'), $output);
[$code, $output] = analyse([...$phpstan, '-c', 'phpstan-legacy-validation.neon', 'tests/fixtures/legacy-validation.php']);
assert($code !== 0 && substr_count($output, 'hexagonal.legacyValidation') === 1, $output);
assert(str_contains($output, 'LegacyDto::validateShape'), $output);
foreach (['AllowedValidator::', 'AllowedPolicy::', 'RequiredExternalOverride::'] as $name) assert(!str_contains($output, $name), $output);

$psalm = ['vendor/bin/psalm', '--no-cache', '--threads=1'];
[$code, $output] = analyse([...$psalm, '-c', 'psalm.xml']);
assert($code === 0, $output);
[$code, $output] = analyse([...$psalm, '-c', 'psalm-validation-invalid.xml']);
assert($code !== 0 && substr_count($output, 'ERROR: HandlerIssue') === 5, $output);
[$code, $output] = analyse([...$psalm, '-c', 'psalm-invalid.xml']);
assert($code !== 0 && str_contains($output, 'ERROR: HandlerIssue'), $output);
[$code, $output] = analyse([...$psalm, '-c', 'psalm-legacy-validation.xml']);
assert($code !== 0 && substr_count($output, 'ERROR: LegacyValidationIssue') === 1, $output);
assert(str_contains($output, 'LegacyDto::validateshape'), $output);
foreach (['AllowedValidator::', 'AllowedPolicy::', 'RequiredExternalOverride::'] as $name) assert(!str_contains($output, $name), $output);
