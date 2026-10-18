<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\PHPStan;

use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDocParser\Ast\Node as DocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\FileTypeMapper;

use function array_push;
use function array_unique;
use function array_values;
use function get_object_vars;
use function is_array;
use function is_int;
use function ltrim;
use function str_starts_with;
use function strcasecmp;

/** @implements Collector<Node, list<int>> */
final readonly class ReadPortCollector implements Collector
{
    public function __construct(private ReflectionProvider $reflectionProvider, private FileTypeMapper $fileTypeMapper)
    {
    }

    #[Override]
    public function getNodeType(): string
    {
        return Node::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): ?array
    {
        if ($node instanceof VirtualNode) {
            return null;
        }
        $lines = [];
        if ($node instanceof ClassLike && isset($node->namespacedName)) {
            $name = $node->namespacedName->toString();
            if (ReadPortInterface::class !== $name && $this->matches($name)) {
                $lines[] = $node->getStartLine();
            }
        }
        $types = [];
        if ($node instanceof Node\Param || $node instanceof Node\Stmt\Property) {
            $types[] = $node->type;
        }
        if ($node instanceof Node\FunctionLike) {
            $types[] = $node->getReturnType();
        }
        foreach ($types as $type) {
            if (null === $type) {
                continue;
            }
            foreach (new NodeFinder()->findInstanceOf([$type], Name::class) as $name) {
                if ($this->matches($scope->resolveName($name))) {
                    $lines[] = $name->getStartLine();
                }
            }
        }
        $doc = $node->getDocComment();
        if (null !== $doc) {
            $config = new ParserConfig(['lines' => true]);
            $constants = new ConstExprParser($config);
            $parser = new PhpDocParser($config, new TypeParser($config, $constants), $constants);
            $className = $node instanceof ClassLike && isset($node->namespacedName)
                ? $node->namespacedName->toString() : $scope->getClassReflection()?->getName();
            $resolved = $this->fileTypeMapper->getResolvedPhpDoc($scope->getFile(), $className, $scope->getTraitReflection()?->getName(), $scope->getFunctionName(), $doc->getText());
            $nameScope = $resolved->getNullableNameScope();
            $parsed = $parser->parse(new TokenIterator(new Lexer($config)->tokenize($doc->getText())));
            foreach ($this->identifiers($parsed) as $identifier) {
                $name = str_starts_with($identifier->name, '\\')
                    ? new Name\FullyQualified(ltrim($identifier->name, '\\'))
                    : new Name($identifier->name);
                if ($this->matches($nameScope?->resolveStringName($identifier->name) ?? $scope->resolveName($name))) {
                    $line = $identifier->getAttribute('startLine');
                    $lines[] = $doc->getStartLine() + (is_int($line) ? $line - 1 : 0);
                }
            }
        }

        return [] === $lines ? null : array_values(array_unique($lines));
    }

    private function matches(string $name): bool
    {
        if (0 === strcasecmp(ReadPortInterface::class, $name)) {
            return true;
        }

        return $this->reflectionProvider->hasClass($name)
            && $this->reflectionProvider->getClass($name)->is(ReadPortInterface::class);
    }

    /** @return list<IdentifierTypeNode> */
    private function identifiers(DocNode $node): array
    {
        if ($node instanceof IdentifierTypeNode) {
            return [$node];
        }
        $result = [];
        foreach (get_object_vars($node) as $field => $value) {
            if ('keyName' === $field && ($node instanceof ArrayShapeItemNode || $node instanceof ObjectShapeItemNode)) {
                continue;
            }
            foreach (is_array($value) ? $value : [$value] as $child) {
                if ($child instanceof DocNode) {
                    array_push($result, ...$this->identifiers($child));
                }
            }
        }

        return $result;
    }
}
