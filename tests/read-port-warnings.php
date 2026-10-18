<?php

declare(strict_types=1);

use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;

require dirname(__DIR__).'/vendor/autoload.php';

$warnings = [
    new ReadPortWarning('/b.php', 1),
    new ReadPortWarning('/a.php', 10),
    new ReadPortWarning('/a.php', 2),
    new ReadPortWarning('/a.php', 2),
];
$normalized = ReadPortWarning::normalize($warnings);
assert([['/a.php', 2], ['/a.php', 10], ['/b.php', 1]] === array_map(static fn (ReadPortWarning $warning): array => [$warning->file, $warning->line], $normalized));
assert($normalized === ReadPortWarning::normalize($normalized));
assert([] === ReadPortWarning::normalize([]));
assert($normalized === ReadPortWarning::normalize(array_reverse($normalized)));
assert(['identifier' => ReadPortWarning::IDENTIFIER, 'message' => ReadPortWarning::MESSAGE, 'file' => '/a.php', 'line' => 2] === $normalized[0]->toArray());
echo "Read-port warning contract passed.\n";

$command = [PHP_BINARY, 'vendor/bin/phpstan', 'analyse', '--debug', '--no-progress', '--level=8', '--autoload-file=vendor/autoload.php', '-c', 'phpstan-extension.neon', 'tests/fixtures/valid-contracts.php'];
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
assert(is_resource($process));
$output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
assert(0 === proc_close($process), $output);
assert(2 === substr_count($output, '[hexagonal.deprecatedReadPort]'), $output);
assert(str_contains($output, 'valid-contracts.php:67 '), $output);
assert(str_contains($output, 'valid-contracts.php:68 '), $output);
echo "PHPStan nonfatal human output passed.\n";

$command[array_key_last($command)] = 'tests/fixtures/read-port-references.php';
$unusedImport = sys_get_temp_dir().'/read-port-unused-'.bin2hex(random_bytes(6)).'.php';
file_put_contents($unusedImport, '<?php namespace UnusedReadPortImport; use Hexagonal\Application\Port\Outbound\ReadPortInterface;');
$command[] = $unusedImport;
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
assert(is_resource($process));
$output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
assert(0 === proc_close($process), $output);
preg_match_all('/read-port-references\.php:(\d+) \[hexagonal\.deprecatedReadPort\]/', $output, $matches);
assert([20, 21, 24, 27, 31, 32, 36, 37, 44, 45, 48, 50, 57, 62] === array_map(intval(...), $matches[1]), $output);
assert(!str_contains($output, $unusedImport.':'), $output);
unlink($unusedImport);
echo "PHPStan reference locations passed.\n";

$command = [PHP_BINARY, 'vendor/bin/psalm', '--no-cache', '--threads=1', '--show-info=true', '--output-format=json', '-c', 'psalm-read-port.xml'];
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
assert(is_resource($process));
$json = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
assert(0 === proc_close($process), $json.$stderr);
$issues = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
assert([20, 21, 24, 27, 31, 32, 36, 37, 44, 45, 48, 50, 57, 62] === array_column($issues, 'line_from'), $json);
foreach ($issues as $issue) {
    assert('DeprecatedReadPort' === $issue['type'], $json);
    assert('info' === $issue['severity'], $json);
    assert('['.ReadPortWarning::IDENTIFIER.'] '.ReadPortWarning::MESSAGE === $issue['message'], $json);
}
echo "Psalm info severity and reference parity passed.\n";
