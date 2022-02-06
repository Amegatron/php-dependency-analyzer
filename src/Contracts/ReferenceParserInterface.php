<?php

namespace PhpDep\Contracts;

use PhpDep\Dto\ReferencesInfo;

interface ReferenceParserInterface
{
    public function parse(string $sourcesCode): ReferencesInfo;
}
