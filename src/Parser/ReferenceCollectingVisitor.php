<?php

namespace PhpDep\Parser;

use PhpDep\Contracts\ReferenceCollectingVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;

class ReferenceCollectingVisitor implements ReferenceCollectingVisitorInterface
{
    /** @var string[] */
    protected array $references;

    /** @var array<Node> */
    protected array $nodeStack;

    /** @var array<string> */
    protected array $uses;

    protected string $namespace;

    protected ?string $className;

    /** @var array<string> */
    protected array $comments;

    public function __construct(protected DocBlockReferenceCollector $docBlockReferenceCollector)
    {
    }

    public function beforeTraverse(array $nodes)
    {
        $this->references = [];
        $this->nodeStack = [];
        $this->namespace = '';
        $this->uses = [];
        $this->comments = [];
        $this->className = null;
        $this->docBlockReferenceCollector = new DocBlockReferenceCollector(DocBlockFactory::createInstance());
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Name) {
            $reference = implode("\\", $node->parts);
            $parentNode = $this->nodeStack[count($this->nodeStack) - 1];

            if ($parentNode instanceof Node\NullableType || $parentNode instanceof Node\UnionType) {
                $parentNode = $this->nodeStack[count($this->nodeStack) - 2];
            }

            if ($parentNode instanceof Stmt\UseUse) {
                if ($parentNode->alias) {
                    $alias = $parentNode->alias->name;
                } else {
                    $parts = explode('\\', $reference);
                    $alias = $parts[count($parts) - 1];
                }

                $this->uses[$alias] = $reference;
            } elseif ($parentNode instanceof Stmt\Namespace_) {
                $this->namespace = $reference;
            } elseif (
                $parentNode instanceof Node\Param
                || $parentNode instanceof Stmt\ClassMethod
                || $parentNode instanceof Expr\New_
                || $parentNode instanceof Expr\ClassConstFetch
                || $parentNode instanceof Stmt\Catch_
                || $parentNode instanceof Expr\StaticCall
                || $parentNode instanceof Stmt\Class_
                || $parentNode instanceof Expr\Instanceof_
                || $parentNode instanceof Stmt\TraitUse
            ) {
                if (!in_array($reference, ['parent', 'static', 'self'])) {
                    if ($node instanceof Node\Name\FullyQualified) {
                        $reference = '\\' . $reference;
                    }

                    $this->references[$reference] = true;
                }
            }
        } elseif (
            $node instanceof Stmt\Property
            || $node instanceof Stmt\ClassMethod
            || $node instanceof Expr\Variable
            || $node instanceof Expr\MethodCall
            || $node instanceof Stmt\Expression
        ) {
            $comment = $node->getDocComment();

            if ($comment) {
                $this->comments[] = $comment->getText();
            }
        } elseif (
            $node instanceof Stmt\Class_
            || $node instanceof Stmt\Interface_
            || $node instanceof Stmt\Trait_
        ) {
            if (!isset($this->className)) {
                $this->className = $node->name->name;
            }
        }

        $this->nodeStack[] = $node;
    }

    public function leaveNode(Node $node)
    {
        array_pop($this->nodeStack);
    }

    public function afterTraverse(array $nodes)
    {
        $this->nodeStack = [];
        $this->className = $this->namespace . '\\' . $this->className;
    }

    /**
     * @return string[]
     */
    public function getReferences(): array
    {
        $this->parseDocReferences($this->comments);

        $referencesExploded = array_map(
            static function (string $item): array {
                return explode("\\", $item);
            },
            array_keys($this->references),
        );

        $references = [];

        foreach ($referencesExploded as $refParts) {
            // If this is already FQN
            if (empty($refParts[0])) {
                $references[] = implode('\\', array_slice($refParts, 1));

                continue;
            }

            // Otherwise, prepend either with namespace or according import
            if (isset($this->uses[$refParts[0]])) {
                $ref = $this->uses[$refParts[0]];

                if (count($refParts) > 1) {
                    $ref .= '\\' . implode("\\", array_slice($refParts, 1));
                }

                $references[] = $ref;
            } else {
                $references[] = $this->namespace . '\\' . implode("\\", $refParts);
            }
        }

        $result = array_unique($references);
        sort($result);

        return $result;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param array<string> $comments
     */
    protected function parseDocReferences(array $comments): void
    {
        $context = new Context($this->namespace, $this->uses);

        foreach ($comments as $comment) {
            $references = $this->docBlockReferenceCollector->getReferencesFromComment($comment, $context);

            foreach ($references as $reference) {
                $this->references[$reference] = true;
            }
        }
    }
}
