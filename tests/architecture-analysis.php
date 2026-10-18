<?php

declare(strict_types=1);

$project = dirname(__DIR__);
$base = sys_get_temp_dir().'/hexagonal-analysis-'.bin2hex(random_bytes(5));
foreach (['src/App/Application', 'src/App/Domain', 'src/App/Infrastructure', 'src/App/tests', 'packages/Orders/src/Domain'] as $directory) {
    mkdir($base.'/'.$directory, 0777, true);
}
$base = realpath($base);
assert(false !== $base);
file_put_contents($base.'/src/App/Application/Commands.php', <<<'PHP'
<?php
namespace Consumer\Application;
abstract readonly class ParentCommand extends \Hexagonal\Application\Command\Command {}
final readonly class ChildCommand extends ParentCommand {}
interface Input extends \Hexagonal\Validation\ValidationInputInterface {}
PHP);
file_put_contents($base.'/src/App/Domain/Types.php', <<<'PHP'
<?php
namespace Consumer\Domain;
final readonly class Amount extends \Hexagonal\Domain\ValueObject {}
interface Internal extends \Hexagonal\Type\InternalIdInterface {}
enum Policy implements \Hexagonal\Domain\PolicyInterface { case DefaultPolicy; }
PHP);
file_put_contents($base.'/src/App/Infrastructure/Helper.php', '<?php namespace Consumer\Infrastructure; final class Helper {}');
file_put_contents($base.'/packages/Orders/src/Domain/Other.php', '<?php namespace Orders\Domain; final readonly class Other extends \Hexagonal\Domain\ValueObject {}');
file_put_contents($base.'/src/App/tests/Fixture.php', '<?php namespace Consumer\Tests; final readonly class Both extends \Hexagonal\Application\Command\Command implements \Hexagonal\Validation\ValidationInputInterface {} function fixture(): object { return new class {}; }');
$architecture = ['search_paths' => ['src', 'packages'], 'context_roots' => ['src/App', 'packages/Orders/src'], 'test_paths' => ['src/App/tests']];
file_put_contents($base.'/hexagonal.json', json_encode($architecture, JSON_THROW_ON_ERROR));
file_put_contents($base.'/phpstan.neon', "includes:\n    - $project/phpstan-extension.neon\nparameters:\n    level: 0\n    tmpDir: $base/phpstan-cache\n    paths:\n        - $base/src\n        - $base/packages\n    hexagonalArchitecture: $base/hexagonal.json\n");
file_put_contents($base.'/psalm.xml', '<?xml version="1.0"?><psalm errorLevel="7" findUnusedCode="false" cacheDirectory="'.$base.'/psalm-cache" autoloader="'.$project.'/vendor/autoload.php"><projectFiles><directory name="src"/><directory name="packages"/></projectFiles><plugins><pluginClass class="Hexagonal\StaticAnalysis\Psalm\HandlerPlugin"><architecture path="hexagonal.json"/></pluginClass></plugins></psalm>');

/** @return array{int, string} */
function architectureRun(array $command, string $cwd): array
{
    $errorFile = $cwd.'/stderr.log';
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['file', $errorFile, 'w']], $pipes, $cwd);
    assert(is_resource($process));
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);

    return [$code, $output."\n".file_get_contents($errorFile)];
}
$commands = [
    'PHPStan' => [PHP_BINARY, $project.'/vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=json', '--autoload-file', $project.'/vendor/autoload.php', '-c', $base.'/phpstan.neon'],
    'Psalm' => [PHP_BINARY, $project.'/vendor/bin/psalm', '--threads=1', '--output-format=json', '-c', $base.'/psalm.xml'],
];
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 === $code, $analyzer.' valid fixture: '.$output);
}
file_put_contents($base.'/src/App/Application/Invalid.php', <<<'PHP'
<?php
namespace Consumer\Application;
final class Unknown {}
final readonly class Both extends \Hexagonal\Application\Command\Command implements \Hexagonal\Validation\ValidationInputInterface {}
trait ExampleTest { public function example(): object { return new class {}; } }
PHP);
file_put_contents($base.'/src/App/Domain/Wrong.php', '<?php namespace Consumer\Domain; final readonly class Wrong extends \Hexagonal\Application\Command\Command {} interface ConflictingId extends \Hexagonal\Type\InternalIdInterface, \Hexagonal\Type\ExternalIdInterface {}');
file_put_contents($base.'/src/App/Outside.php', '<?php namespace Consumer; final class Outside {}');
file_put_contents($base.'/src/App/Infrastructure/Invalid.php', '<?php namespace Consumer\Infrastructure; final readonly class Wrong extends \Hexagonal\Application\Command\Command {} interface TwoPorts extends \Hexagonal\Application\Port\Inbound\CommandBusInterface, \Hexagonal\Application\Port\Inbound\QueryBusInterface {}');
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 !== $code, $analyzer.' accepted invalid declarations');
    foreach (['unknownRole', 'ambiguousRole', 'anonymousClass', 'incompatibleLayer', 'outOfLayer'] as $category) {
        assert(str_contains($output, 'hexagonal.'.$category), $analyzer.' missing '.$category.': '.$output);
    }
    assert(!str_contains($output, 'Internal error'), $output);
    $report = json_decode(explode("\n", $output)[0], true, 512, JSON_THROW_ON_ERROR);
    $issues = 'PHPStan' === $analyzer ? array_merge(...array_map(static fn (array $file): array => $file['messages'], array_values($report['files']))) : $report;
    $counts = [];
    foreach ($issues as $issue) {
        if (preg_match('/hexagonal\.(\w+)/', $issue['identifier'] ?? $issue['message'], $match)) {
            $counts[$match[1]] = ($counts[$match[1]] ?? 0) + 1;
            assert(($issue['line'] ?? $issue['line_from']) > 0);
        }
    }
    $expected = ['unknownRole' => 1, 'ambiguousRole' => 3, 'anonymousClass' => 1, 'incompatibleLayer' => 2, 'outOfLayer' => 1];
    ksort($counts);
    ksort($expected);
    assert($counts === $expected, $analyzer.' diagnostic parity: '.json_encode($counts)."\n".$output);
}

file_put_contents($base.'/src/App/Infrastructure/Unresolved.php', '<?php namespace Consumer\\Infrastructure; class Unresolved extends MissingParent {}');
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 !== $code && str_contains($output, 'MissingParent'), $analyzer.': '.$output);
    assert(str_contains($output, 'class.notFound') || str_contains($output, 'UndefinedClass'), $analyzer.': '.$output);
}
unlink($base.'/src/App/Infrastructure/Unresolved.php');
// Changes outside analyzed paths must still be discovered on a cached run.
mkdir($base.'/unscanned/New/Domain', 0777, true);
$architecture['search_paths'][] = 'unscanned';
file_put_contents($base.'/hexagonal.json', json_encode($architecture, JSON_THROW_ON_ERROR));
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 !== $code && str_contains($output, 'hexagonal.undeclaredContext'), $analyzer.': '.$output);
}
$architecture['context_roots'][] = 'unscanned/New';
file_put_contents($base.'/unscanned/New/Domain/Missing.php', '<?php namespace Missing; final class Missing {}');
file_put_contents($base.'/hexagonal.json', json_encode($architecture, JSON_THROW_ON_ERROR));
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 !== $code && str_contains($output, 'hexagonal.incompleteScope'), $analyzer.': '.$output);
}
file_put_contents($base.'/hexagonal.json', '{');
foreach ($commands as $analyzer => $command) {
    [$code, $output] = architectureRun($command, $base);
    assert(0 !== $code && str_contains($output, 'hexagonal.invalidScope'), $analyzer.': '.$output);
}
echo "Architecture analyzer fixtures passed: $base\n";
