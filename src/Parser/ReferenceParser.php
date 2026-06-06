<?php

namespace PhpDep\Parser;

use PhpDep\Contracts\ReferenceParserInterface;
use PhpDep\Dto\ClassReferencesInfo;
use PhpDep\Dto\ReferencesInfo;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

class ReferenceParser implements ReferenceParserInterface
{
    public function __construct(protected Parser $parser)
    {
    }

    public function parse(string $sourcesCode): ReferencesInfo
    {
        $traverser = new NodeTraverser();
        $visitor = new ReferenceCollectingVisitor();

        $traverser->addVisitor($visitor);
        $traverser->traverse($this->parser->parse($sourcesCode));

        if ($visitor->getClassName() === null) {
            return new ReferencesInfo($visitor->getReferences());
        }

        return new ClassReferencesInfo($visitor->getClassName(), $visitor->getReferences());
    }
}
