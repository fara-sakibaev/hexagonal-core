<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Hexagonal\Application\Port\Outbound\ReadPortInterface;
use Hexagonal\StaticAnalysis\Deprecation\ReadPortWarning;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
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
use Psalm\Codebase;
use Psalm\CodeLocation\Raw;
use Psalm\IssueBuffer;

use function array_map;
use function array_push;
use function array_slice;
use function array_values;
use function explode;
use function get_object_vars;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function ltrim;
use function str_starts_with;
use function strcasecmp;
use function strlen;

final class ReadPortVisitor extends NodeVisitorAbstract
{
    /** @var array<int, true> */
    private array $reported = [];

    public function __construct(private readonly Codebase $codebase, private readonly string $file, private readonly NameResolver $resolver)
    {
    }

    #[Override]
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassLike && isset($node->namespacedName)) {
            $name = $node->namespacedName->toString();
            if (ReadPortInterface::class !== $name && $this->matches($name)) {
                $this->report($node->getStartLine(), $node->getStartFilePos());
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
                $resolved = $this->resolver->getNameContext()->getResolvedClassName($name);
                if ($this->matches($resolved->toString())) {
                    $this->report($name->getStartLine(), $name->getStartFilePos());
                }
            }
        }
        $doc = $node->getDocComment();
        if (null !== $doc) {
            $config = new ParserConfig(['lines' => true]);
            $constants = new ConstExprParser($config);
            $parser = new PhpDocParser($config, new TypeParser($config, $constants), $constants);
            $parsed = $parser->parse(new TokenIterator(new Lexer($config)->tokenize($doc->getText())));
            foreach ($this->identifiers($parsed) as $identifier) {
                $name = str_starts_with($identifier->name, '\\')
                    ? new Name\FullyQualified(ltrim($identifier->name, '\\')) : new Name($identifier->name);
                $resolved = $this->resolver->getNameContext()->getResolvedClassName($name);
                if ($this->matches($resolved->toString())) {
                    $relative = $identifier->getAttribute('startLine');
                    $relative = is_int($relative) ? $relative - 1 : 0;
                    $prefix = implode("\n", array_slice(explode("\n", $doc->getText()), 0, $relative));
                    $offset = $doc->getStartFilePos() + strlen($prefix) + ($relative > 0 ? 1 : 0);
                    $this->report($doc->getStartLine() + $relative, $offset);
                }
            }
        }

        return null;
    }

    private function matches(string $name): bool
    {
        if (0 === strcasecmp($name, ReadPortInterface::class)) {
            return true;
        }
        if (!$this->codebase->classlike_storage_provider->has($name)) {
            return false;
        }
        $storage = $this->codebase->classlike_storage_provider->get($name);
        $ancestors = [...$storage->parent_classes, ...$storage->parent_interfaces, ...$storage->class_implements];

        return in_array(\strtolower(ReadPortInterface::class), array_map(strtolower(...), array_values($ancestors)), true);
    }

    private function report(int $line, int $offset): void
    {
        if (isset($this->reported[$line])) {
            return;
        }
        $this->reported[$line] = true;
        IssueBuffer::maybeAdd(new DeprecatedReadPort(
            '['.ReadPortWarning::IDENTIFIER.'] '.ReadPortWarning::MESSAGE,
            new Raw($this->codebase->getFileContents($this->file), $this->file, $this->file, $offset, $offset),
        ));
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
