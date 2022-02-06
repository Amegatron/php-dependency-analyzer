<?php

namespace PhpDep\Parser;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\DocBlock\Tags;

class DocBlockReferenceCollector
{
    public function __construct(protected DocBlockFactory $factory)
    {
    }

    /**
     * @return array<string>
     */
    public function getReferencesFromComment(string $comment, ?Context $context = null): array
    {
        $docBlock = $this->factory->create($comment, $context);

        $references = [];

        foreach ($docBlock->getTags() as $tag) {
            if (
                $tag instanceof Tags\Var_
                || $tag instanceof Tags\Throws
                || $tag instanceof Tags\Return_
                || $tag instanceof Tags\Param
            ) {
                $references = array_merge($references, $this->getReferencesFromType($tag->getType()));
            }
        }

        return $references;
    }

    /**
     * @return array<string>
     */
    protected function getReferencesFromType(Type $type): array
    {
        $references = [];

        if ($type instanceof Types\AbstractList) {
            $references = array_merge(
                $references,
                $this->getReferencesFromType($type->getKeyType()),
                $this->getReferencesFromType($type->getValueType()),
            );
        } elseif ($type instanceof Types\Compound) {
            foreach (iterator_to_array($type->getIterator()) as $subType) {
                $references = array_merge($references, $this->getReferencesFromType($subType));
            }
        } elseif ($type instanceof Types\Object_) {
            $references[] = (string) $type;
        } elseif ($type instanceof Types\Nullable) {
            $references = array_merge($references, $this->getReferencesFromType($type->getActualType()));
        }

        return $references;
    }
}
