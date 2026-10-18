<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temp = sys_get_temp_dir().'/hexagonal-read-port-mixed-'.bin2hex(random_bytes(6));
mkdir($temp);

/** @param list<string> $command */
function runMixed(array $command): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    assert(is_resource($process));
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    assert(0 !== $exit, $stdout.$stderr);

    return [$exit, json_decode($stdout, true, 512, JSON_THROW_ON_ERROR)];
}

$base = [PHP_BINARY, $root.'/vendor/bin/phpstan', 'analyse', '--no-progress', '--level=8', '--autoload-file='.$root.'/vendor/autoload.php', '-c', $root.'/phpstan-extension.neon', $root.'/tests/fixtures/read-port-invalid.php'];
[$stockExit, $stock] = runMixed([...$base, '--error-format=json']);
[$customExit, $custom] = runMixed([...$base, '--error-format=hexagonalJson']);
assert($stockExit === $customExit);
assert([15, 16] === array_column($custom['warnings'], 'line'));
unset($custom['warnings']);
assert($stock === $custom, json_encode([$stock, $custom]));
$messages = $stock['files'][$root.'/tests/fixtures/read-port-invalid.php']['messages'];
assert(in_array('generics.notSubtype', array_column($messages, 'identifier'), true));
assert(in_array('hexagonal.handler', array_column($messages, 'identifier'), true));
echo "PHPStan mixed generic/handler errors preserve stock JSON.\n";

$config = file_get_contents($root.'/psalm-read-port.xml');
$config = str_replace('name="tests/fixtures/read-port-references.php"', 'name="'.$root.'/tests/fixtures/read-port-invalid.php"', $config);
$config = str_replace('errorLevel="7"', 'errorLevel="1" autoloader="'.$root.'/vendor/autoload.php"', $config);
file_put_contents($temp.'/psalm.xml', $config);
[$exit, $issues] = runMixed([PHP_BINARY, $root.'/vendor/bin/psalm', '--no-cache', '--threads=1', '--show-info=true', '--output-format=json', '-c', $temp.'/psalm.xml']);
assert(2 === $exit);
$warnings = array_values(array_filter($issues, static fn (array $issue): bool => 'DeprecatedReadPort' === $issue['type']));
assert([15, 16] === array_column($warnings, 'line_from'), json_encode($issues));
foreach ($warnings as $warning) {
    assert('info' === $warning['severity']);
}
$errors = array_values(array_filter($issues, static fn (array $issue): bool => 'error' === $issue['severity']));
assert(in_array('InvalidTemplateParam', array_column($errors, 'type'), true), json_encode($issues));
assert(in_array('HandlerIssue', array_column($errors, 'type'), true), json_encode($issues));
echo "Psalm mixed generic/handler errors remain errors beside info.\n";

mkdir($temp.'/src/App/Domain', 0777, true);
$portFile = $temp.'/src/App/Domain/Port.php';
file_put_contents($portFile, <<<'PHP'
<?php
namespace MixedArchitecture\Domain;
/** @extends \Hexagonal\Application\Port\Outbound\ReadPortInterface<\Hexagonal\Application\Query\Query<\Hexagonal\Application\DTO\DTO>> */
interface Port extends \Hexagonal\Application\Port\Outbound\ReadPortInterface {}
PHP);
file_put_contents($temp.'/hexagonal.json', json_encode(['search_paths' => ['src'], 'context_roots' => ['src/App'], 'test_paths' => []], JSON_THROW_ON_ERROR));
file_put_contents($temp.'/phpstan.neon', "includes:
    - $root/phpstan-extension.neon
parameters:
    level: 8
    tmpDir: $temp/phpstan-cache
    paths:
        - $temp/src
    hexagonalArchitecture: $temp/hexagonal.json
");
$base = [PHP_BINARY, $root.'/vendor/bin/phpstan', 'analyse', '--no-progress', '--autoload-file='.$root.'/vendor/autoload.php', '-c', $temp.'/phpstan.neon'];
[$stockExit, $stock] = runMixed([...$base, '--error-format=json']);
[$customExit, $custom] = runMixed([...$base, '--error-format=hexagonalJson']);
assert($stockExit === $customExit);
assert([3, 4] === array_column($custom['warnings'], 'line'));
unset($custom['warnings']);
assert($stock === $custom);
assert(in_array('hexagonal.incompatibleLayer', array_column($custom['files'][realpath($portFile)]['messages'], 'identifier'), true), json_encode($custom));

file_put_contents($temp.'/psalm.xml', '<?xml version="1.0"?><psalm errorLevel="1" findUnusedCode="false" autoloader="'.$root.'/vendor/autoload.php"><projectFiles><directory name="'.$temp.'/src"/></projectFiles><plugins><pluginClass class="Hexagonal\StaticAnalysis\Psalm\HandlerPlugin"><architecture path="'.$temp.'/hexagonal.json"/></pluginClass></plugins></psalm>');
[$exit, $issues] = runMixed([PHP_BINARY, $root.'/vendor/bin/psalm', '--no-cache', '--threads=1', '--show-info=true', '--output-format=json', '-c', $temp.'/psalm.xml']);
$warnings = array_values(array_filter($issues, static fn (array $issue): bool => 'DeprecatedReadPort' === $issue['type']));
assert([3, 4] === array_column($warnings, 'line_from'), json_encode($issues));
foreach ($warnings as $warning) {
    assert('info' === $warning['severity']);
}
$architectureErrors = array_values(array_filter($issues, static fn (array $issue): bool => 'ArchitectureIssue' === $issue['type']));
assert(1 === count($architectureErrors), json_encode($issues));
assert('error' === $architectureErrors[0]['severity']);
assert(str_contains($architectureErrors[0]['message'], 'hexagonal.incompatibleLayer'));
echo 'Mixed architecture errors preserve nonfatal deprecation reporting.
';

file_put_contents($temp.'/phpstan.neon', file_get_contents($temp.'/phpstan.neon')."    errorFormat: json\n");
[$configuredExit, $configured] = runMixed($base);
assert($stockExit === $configuredExit);
assert($stock === $configured, json_encode($configured));
echo "Explicit consumer formatter override preserved.\n";
