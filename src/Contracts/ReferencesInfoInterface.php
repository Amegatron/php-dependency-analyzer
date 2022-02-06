<?php

namespace PhpDep\Contracts;

interface ReferencesInfoInterface
{
    /**
     * @return array<string>
     */
    public function getReferences(): array;
}
