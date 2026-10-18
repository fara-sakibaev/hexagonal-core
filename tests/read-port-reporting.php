<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temp = sys_get_temp_dir().'/hexagonal-read-port-reporting-'.bin2hex(random_bytes(6));
mkdir($temp);
file_put_contents($temp.'/Unused.php', '<?php namespace UnusedReadPortImport; use Hexagonal\Application\Port\Outbound\ReadPortInterface;');
$source = file_get_contents($root.'/psalm-read-port.xml');
$source = str_replace('name="tests/', 'name="'.$root.'/tests/', $source);
$source = str_replace('errorLevel="7"', 'errorLevel="7" autoloader="'.$root.'/vendor/autoload.php"', $source);
$source = str_replace('</projectFiles>', '<file name="'.$temp.'/Unused.php" /></projectFiles>', $source);
foreach (['default', 'hidden', 'suppress', 'error'] as $mode) {
    $config = $source;
    if (in_array($mode, ['suppress', 'error'], true)) {
        $config = str_replace('</psalm>', '<issueHandlers><PluginIssue name="DeprecatedReadPort" errorLevel="'.$mode.'" /></issueHandlers></psalm>', $config);
    }
    file_put_contents($temp.'/psalm.xml', $config);
    $command = [PHP_BINARY, $root.'/vendor/bin/psalm', '--no-cache', '--threads=1', '--output-format=json', '--show-info='.('hidden' === $mode ? 'false' : 'true'), '-c', $temp.'/psalm.xml'];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    assert(is_resource($process));
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    assert(('error' === $mode ? 2 : 0) === $exit, $stdout.$stderr);
    $issues = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
    if (in_array($mode, ['hidden', 'suppress'], true)) {
        assert([] === $issues, $stdout);
    } else {
        assert(14 === count($issues), $stdout);
        foreach ($issues as $issue) {
            assert('DeprecatedReadPort' === $issue['type']);
            assert(('error' === $mode ? 'error' : 'info') === $issue['severity']);
        }
    }
    echo 'Psalm '.$mode." reporting passed.\n";
}
