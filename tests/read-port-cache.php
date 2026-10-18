<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temp = sys_get_temp_dir().'/hexagonal-read-port-'.bin2hex(random_bytes(6));
mkdir($temp);
mkdir($temp.'/src');
$fixture = $temp.'/src/Port.php';
file_put_contents($fixture, file_get_contents(__DIR__.'/fixtures/read-port-references.php'));
file_put_contents($temp.'/src/Keep.php', "<?php\nnamespace CacheFixture;\nfinal class Keep {}\n");
file_put_contents($temp.'/phpstan.neon', "includes:\n    - ".$root."/phpstan-extension.neon\nparameters:\n    level: 8\n    tmpDir: ".$temp."/phpstan-cache\n    paths:\n        - ".$temp."/src\n");
file_put_contents($temp.'/psalm.xml', '<?xml version="1.0"?><psalm errorLevel="7" findUnusedCode="false" cacheDirectory="'.$temp.'/psalm-cache" autoloader="'.$root.'/vendor/autoload.php" xmlns="https://getpsalm.org/schema/config"><projectFiles><directory name="'.$temp.'/src" /></projectFiles><plugins><pluginClass class="Hexagonal\StaticAnalysis\Psalm\HandlerPlugin" /></plugins></psalm>');

/** @param list<string> $command */
function runCachedAnalyzer(array $command): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    assert(is_resource($process));
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    assert(in_array(proc_close($process), [0, 1, 2], true), $stdout.$stderr);

    return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
}

$commands = [
    'phpstan' => [PHP_BINARY, $root.'/vendor/bin/phpstan', 'analyse', '--no-progress', '--autoload-file='.$root.'/vendor/autoload.php', '--error-format=hexagonalJson', '-c', $temp.'/phpstan.neon'],
    'psalm' => [PHP_BINARY, $root.'/vendor/bin/psalm', '--threads=1', '--show-info=true', '--output-format=json', '-c', $temp.'/psalm.xml'],
];
$expected = [20, 21, 24, 27, 31, 32, 36, 37, 44, 45, 48, 50, 57, 62];
foreach (['cold', 'warm', 'edited', 'restored', 'deleted'] as $phase) {
    if ('edited' === $phase) {
        $phaseExpected = [];
        file_put_contents($fixture, "<?php\nnamespace CacheFixture;\ninterface Migrated {}\n");
    } elseif ('restored' === $phase) {
        $phaseExpected = $expected;
        file_put_contents($fixture, file_get_contents(__DIR__.'/fixtures/read-port-references.php'));
    } elseif ('deleted' === $phase) {
        $phaseExpected = [];
        unlink($fixture);
    } else {
        $phaseExpected = $expected;
    }
    foreach ($commands as $analyzer => $command) {
        if ('phpstan' === $analyzer && 'warm' === $phase) {
            $command[] = '--fail-without-result-cache';
        }
        $output = runCachedAnalyzer($command);
        if ('phpstan' === $analyzer) {
            $messages = [];
            foreach ($output['files'] as $file) {
                array_push($messages, ...$file['messages']);
            }
            $readPortErrors = array_values(array_filter($messages, static fn (array $message): bool => 'hexagonal.deprecatedReadPort' === $message['identifier']));
            assert(count($phaseExpected) === count($readPortErrors), json_encode([$phase, $readPortErrors, $output]));
            assert(count($phaseExpected) === $output['totals']['file_errors']);
            $lines = array_column($readPortErrors, 'line');
        } else {
            $warnings = array_values(array_filter($output, static fn (array $issue): bool => 'DeprecatedReadPort' === $issue['type']));
            foreach ($warnings as $warning) {
                assert('error' === $warning['severity']);
            }
            $lines = array_column($warnings, 'line_from');
        }
        assert($phaseExpected === $lines, $analyzer.' '.$phase.': '.json_encode($output));
        echo $analyzer.' '.$phase." passed.\n";
    }
}
echo 'Cache artifacts: '.$temp."\n";
