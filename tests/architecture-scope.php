<?php

declare(strict_types=1);

use Hexagonal\Application\Command\Command;
use Hexagonal\Domain\ValueObject;
use Hexagonal\StaticAnalysis\Architecture\ContextDiscovery;
use Hexagonal\StaticAnalysis\Architecture\DeclarationPolicy;
use Hexagonal\StaticAnalysis\Architecture\ScopeConfiguration;

require dirname(__DIR__).'/vendor/autoload.php';

$base = sys_get_temp_dir().'/hexagonal-scope-'.bin2hex(random_bytes(5));
foreach (['src/App/Domain', 'src/App/Application', 'src/App/Infrastructure', 'src/App/tests/Domain', 'packages/Orders/src/Domain', 'packages/vendor/Ignore/Domain'] as $directory) {
    mkdir($base.'/'.$directory, 0777, true);
}
$base = realpath($base);
assert(false !== $base);
$scope = new ScopeConfiguration($base, ['src', 'packages'], ['src/App', 'packages/Orders/src'], ['src/App/tests']);
$discovery = new ContextDiscovery();
$candidates = $discovery->candidates($scope);
assert([$base.'/packages/Orders/src', $base.'/src/App'] === $candidates);
$file = $base.'/src/App/Domain/Amount.php';
file_put_contents($file, '<?php');
assert([] === $discovery->diagnostics($scope, [$file]));
assert('hexagonal.incompleteScope' === $discovery->diagnostics($scope, [])[0]->identifier);
mkdir($base.'/packages/New/src/Infrastructure', 0777, true);
assert('hexagonal.undeclaredContext' === $discovery->diagnostics($scope, [$file])[0]->identifier);

$policy = new DeclarationPolicy($scope);
assert([] === $policy->check($file, 3, 'Amount', [ValueObject::class]));
assert([] === $policy->check($file, 3, 'DomainFailure', ['Throwable']));
assert('hexagonal.unknownRole' === $policy->check($file, 3, 'Unknown')[0]->identifier);
assert('hexagonal.incompatibleLayer' === $policy->check($file, 3, 'Command', [Command::class])[0]->identifier);
assert([] === $policy->check($base.'/src/App/Infrastructure/Helper.php', 1, 'Helper'));
assert('hexagonal.outOfLayer' === $policy->check($base.'/src/App/Helper.php', 1, 'Helper')[0]->identifier);
assert('hexagonal.anonymousClass' === $policy->check($file, 7, null)[0]->identifier);
assert(7 === $policy->check($file, 7, null)[0]->line);
assert([] === $policy->check($file, 1, 'ReusableTrait', [], true));
assert([] === $policy->check($base.'/src/App/tests/Domain/Fixture.php', 1, null));
assert([] === $policy->check($base.'/src/App/tests/Fixture.php', 1, 'Both', [Command::class, ValueObject::class]));
assert('hexagonal.anonymousClass' === $policy->check($base.'/src/App/Application/ExampleTest.php', 1, null)[0]->identifier);
assert([] === $policy->check($base.'/src/AppSibling/Domain/Other.php', 1, 'Other'));
assert(!ScopeConfiguration::contains($base.'/src/App', $base.'/src/Application'));

foreach ([
    [[], ['src/App'], []],
    [['missing'], [], []],
    [['src', 'src/App'], ['src/App'], []],
    [['src'], ['src/App', 'src/App/Domain'], []],
    [['src'], ['packages/Orders/src'], []],
    [['src'], ['src/App'], ['src/App']],
] as [$search, $roots, $tests]) {
    try {
        new ScopeConfiguration($base, $search, $roots, $tests);
        throw new RuntimeException('Invalid scope accepted.');
    } catch (InvalidArgumentException) {
    }
}
file_put_contents($base.'/architecture.json', json_encode(['search_paths' => ['src'], 'context_roots' => ['src/App'], 'test_paths' => ['src/App/tests']], JSON_THROW_ON_ERROR));
assert([$base.'/src/App'] === ScopeConfiguration::fromFile($base.'/architecture.json')->contextRoots);
foreach (['{', '{"search_paths": [1], "context_roots": []}', '{"search_paths": ["src"], "context_rooots": []}'] as $invalid) {
    file_put_contents($base.'/invalid.json', $invalid);
    try {
        ScopeConfiguration::fromFile($base.'/invalid.json');
        throw new RuntimeException('Invalid JSON configuration accepted.');
    } catch (InvalidArgumentException) {
    }
}
// Clean only this test's isolated generated tree.
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iterator as $entry) {
    $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
}
rmdir($base);
echo "Architecture scope tests passed.\n";
