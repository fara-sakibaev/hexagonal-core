<?php

declare(strict_types=1);

$binary = dirname(__DIR__).'/bin/hexagonal-setup';
$base = sys_get_temp_dir().'/hexagonal-setup-'.bin2hex(random_bytes(5));
mkdir($base);
function runSetup(string $binary, string $cwd, string $args): array
{
    $process = proc_open([PHP_BINARY, $binary, ...explode(' ', $args)], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);

    return [proc_close($process), $output];
}

mkdir($base.'/starter');
[$code, $preview] = runSetup($binary, $base.'/starter', '--analyzer phpstan --dry-run');
assert(0 === $code && str_contains($preview, 'Proposed diff'));
assert(!file_exists($base.'/starter/phpstan.neon'));
[$code] = runSetup($binary, $base.'/starter', '--analyzer phpstan --apply');
assert(0 === $code);
$first = file_get_contents($base.'/starter/phpstan.neon');
assert(!str_contains($first, 'phpstan-legacy-validation.neon'));
[$code, $again] = runSetup($binary, $base.'/starter', '--apply');
assert(0 === $code && str_contains($again, 'no changes'));
assert(file_get_contents($base.'/starter/phpstan.neon') === $first);

mkdir($base.'/custom');
file_put_contents($base.'/custom/quality.neon', "parameters:\n    level: 8\n");
file_put_contents($base.'/custom/composer.json', json_encode(['scripts' => ['analyse' => 'phpstan analyse -c quality.neon']]));
[$code, $output] = runSetup($binary, $base.'/custom', '');
assert(0 === $code && str_contains($output, 'quality.neon'));

mkdir($base.'/ambiguous');
file_put_contents($base.'/ambiguous/phpstan.neon', 'parameters: {}');
file_put_contents($base.'/ambiguous/psalm.xml', '<psalm/>');
[$code, $output] = runSetup($binary, $base.'/ambiguous', '');
assert(2 === $code && str_contains($output, 'Ambiguous'));

mkdir($base.'/psalm');
[$code] = runSetup($binary, $base.'/psalm', '--analyzer psalm --apply');
assert(0 === $code);
assert(!str_contains(file_get_contents($base.'/psalm/psalm.xml'), 'LegacyValidationPlugin'));
[$code, $output] = runSetup($binary, $base.'/psalm', '--apply');
assert(0 === $code && str_contains($output, 'no changes'));
