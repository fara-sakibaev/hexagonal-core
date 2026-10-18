<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\StaticAnalysis\Architecture\ContextDiscovery;
use Hexagonal\StaticAnalysis\Architecture\DeclarationPolicy;
use Hexagonal\StaticAnalysis\Architecture\Diagnostic;
use Hexagonal\StaticAnalysis\Architecture\ScopeConfiguration;
use InvalidArgumentException;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_keys;
use function array_map;
use function array_push;
use function is_file;

/** @implements Rule<CollectedDataNode> */
final readonly class ArchitectureRule implements Rule
{
    public function __construct(private ?string $configuration = null)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $this->configuration) {
            return [];
        }
        try {
            $configuration = ScopeConfiguration::fromFile($this->configuration);
        } catch (InvalidArgumentException $exception) {
            return [RuleErrorBuilder::message($exception->getMessage())->identifier('hexagonal.invalidScope')->build()];
        }
        /** @var array<string, list<list<array{name: ?string, line: int, ancestors: list<string>, trait: bool}>>> $data */
        $data = $node->get(ArchitectureCollector::class);
        $diagnostics = new ContextDiscovery()->diagnostics($configuration, array_keys($data));
        $policy = new DeclarationPolicy($configuration);
        foreach ($data as $file => $batches) {
            foreach ($batches as $declarations) {
                foreach ($declarations as $declaration) {
                    array_push($diagnostics, ...$policy->check($file, $declaration['line'], $declaration['name'], $declaration['ancestors'], $declaration['trait']));
                }
            }
        }

        return array_map(static function (Diagnostic $diagnostic) {
            $builder = RuleErrorBuilder::message($diagnostic->message)->identifier($diagnostic->identifier);
            if (is_file($diagnostic->file)) {
                return $builder->file($diagnostic->file)->line($diagnostic->line)->build();
            }

            return RuleErrorBuilder::message('['.$diagnostic->identifier.'] '.$diagnostic->message)->identifier($diagnostic->identifier)->build();
        }, $diagnostics);
    }
}
