<?php

namespace PhpDep\Parser;

use PhpDep\Contracts\ReferenceParserInterface;
use PhpDep\Dto\ClassReferencesInfo;
use PhpDep\Dto\ReferencesInfo;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;

class ReferenceParser implements ReferenceParserInterface
{
    public function __construct(
        protected Parser $parser,
        protected NodeTraverserInterface $traverser,
    ) {
    }

    public function parse(string $sourcesCode): ReferencesInfo
    {
        $visitor = new ReferenceCollectingVisitor();
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($this->parser->parse($sourcesCode));

        if ($visitor->getClassName() === null) {
            return new ReferencesInfo($visitor->getReferences());
        }

        return new ClassReferencesInfo($visitor->getClassName(), $visitor->getReferences());
    }
}
