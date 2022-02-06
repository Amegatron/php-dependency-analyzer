<?php

namespace PhpDep\Contracts;

use PhpParser\NodeVisitor;

interface ReferenceCollectingVisitorInterface extends NodeVisitor
{
    public function getClassName(): ?string;

    /**
     * @return array<string>
     */
    public function getReferences(): array;
}
