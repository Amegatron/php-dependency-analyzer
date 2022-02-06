<?php

namespace PhpDep\Parser;

use PhpDep\Contracts\ReferenceParserInterface;
use PhpDep\Contracts\ReferenceCollectingVisitorInterface;
use PhpDep\Dto\ClassReferencesInfo;
use PhpDep\Dto\ReferencesInfo;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use phpDocumentor\Reflection\DocBlockFactory;

class ReferenceParser implements ReferenceParserInterface
{
    public function __construct(
        protected Parser $parser,
        protected NodeTraverserInterface $traverser,
        protected ReferenceCollectingVisitorInterface $referenceCollectingVisitor,
    ) {
    }

    public function parse(string $sourcesCode): ReferencesInfo
    {
        $visitor = new ReferenceCollectingVisitor(new DocBlockReferenceCollector(DocBlockFactory::createInstance()));
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($this->parser->parse($sourcesCode));

        if ($visitor->getClassName() === null) {
            return new ReferencesInfo($visitor->getReferences());
        }

        return new ClassReferencesInfo($visitor->getClassName(), $visitor->getReferences());
    }
}
